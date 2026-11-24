<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Storefront\Entities\StoreSetting;
use Modules\Storefront\Entities\StoreOrder;
use Modules\Storefront\Services\Catalog;
use Modules\Storefront\Services\Checkout;
use Modules\Storefront\Services\PaystackGateway;

class StoreController extends Controller
{
    public function asset(string $file)
    {
        $art = ['general-goods-display.svg', 'hardware-display.svg', 'bar-cocktail.svg', 'pharmacy-display.svg', 'supermarket-display.svg'];
        abort_unless(in_array($file, array_merge($art, ['store.css', 'store.js', 'checkout.css', 'checkout.js', 'tracking.js', 'appearance.css', 'public-presets.css', 'homepage.css'])), 404);
        return response()->file(__DIR__.'/../../Resources/assets/'.(in_array($file, $art) ? 'art/' : '').$file, [
            'Content-Type' => in_array($file, $art) ? 'image/svg+xml' : (str_ends_with($file, '.css') ? 'text/css; charset=utf-8' : 'application/javascript; charset=utf-8'),
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function home()
    {
        abort_unless(Schema::hasTable('storefront_settings'), 404);
        $store = StoreSetting::where('business_id', config('storefront.default_business_id'))->where('enabled', true)->firstOrFail();
        return redirect()->route('storefront.index', $store);
    }

    public function ensureOpen(StoreSetting $store): void
    {
        abort_unless($store->enabled && $store->business && $store->location
            && $store->location->business_id == $store->business_id && $store->location->is_active, 404);
    }

    public function index(StoreSetting $store, Catalog $catalog)
    {
        $this->ensureOpen($store);
        $products = $catalog->products($store);
        $categories = $products->filter(function ($product) { return $product['category_id']; })
            ->unique('category_id')->map(function ($product) { return ['id' => $product['category_id'], 'name' => $product['category']]; })->values();
        return response()->view('storefront::index', compact('store', 'products', 'categories', 'catalog'))->header('Cache-Control', 'no-store, private');
    }

    public function cart(Request $request, StoreSetting $store, Catalog $catalog)
    {
        $this->ensureOpen($store);
        $data = $request->validate([
            'operation' => 'required|in:get,add,quantity,remove',
            'product_id' => 'required_if:operation,add|integer|min:1',
            'key' => 'required_if:operation,quantity,remove|integer|min:1',
            'quantity' => 'required_if:operation,quantity|integer|min:0|max:99',
        ]);
        $key = 'storefront.cart.'.$store->id;
        $cart = $request->session()->get($key, []);
        $operation = $data['operation'];
        $notice = null;
        if ($operation === 'get' && $cart) {
            foreach ($cart as $id => $quantity) {
                try { $catalog->quote($store, [$id => $quantity]); }
                catch (ValidationException $error) {
                    unset($cart[$id]);
                    $notice = 'Some items are no longer available and were removed. Please review your order.';
                }
            }
        }
        if ($operation === 'add') {
            $id = $data['product_id'];
            $cart[$id] = ($cart[$id] ?? 0) + 1;
        } elseif ($operation === 'quantity') {
            if (! isset($cart[$data['key']])) { throw ValidationException::withMessages(['cart' => 'This item is no longer in your cart.']); }
            $cart[$data['key']] = (int) $data['quantity'];
        } elseif ($operation === 'remove') {
            unset($cart[$data['key']]);
        }
        $cart = array_filter($cart, function ($quantity) { return $quantity > 0; });
        try {
            $quote = $cart ? $catalog->quote($store, $cart) : ['items' => [], 'subtotal_minor' => 0];
        } catch (ValidationException $error) {
            return response()->json(['success' => false, 'data' => ['message' => collect($error->errors())->flatten()->first()]], 422);
        }
        $request->session()->put($key, $cart);
        if ($operation !== 'get' || $notice) { $request->session()->forget('storefront.checkout.'.$store->id); }
        $items = array_map(function ($item) use ($store, $catalog) {
            return ['key' => (string) $item['variation_id'], 'id' => $item['variation_id'], 'name' => $item['name'],
                'quantity' => $item['quantity'], 'max' => 99, 'image' => $item['image'],
                'price' => e($catalog->money($store, $item['price_minor'] * $item['quantity']))];
        }, $quote['items']);
        $total = e($catalog->money($store, $quote['subtotal_minor']));
        return response()->json(['success' => true, 'data' => ['items' => $items, 'count' => array_sum($cart),
            'subtotal' => $total, 'total' => $total, 'discount' => '', 'notice' => $notice, 'checkout' => route('storefront.checkout', $store)]])->header('Cache-Control', 'no-store, private');
    }

    public function checkout(Request $request, StoreSetting $store, Catalog $catalog)
    {
        $this->ensureOpen($store);
        $cart = $request->session()->get('storefront.cart.'.$store->id, []);
        if (! $cart) { return redirect()->route('storefront.index', $store); }
        try { $quote = $catalog->quote($store, $cart); }
        catch (ValidationException $error) { return redirect()->route('storefront.index', $store)->with('storefront_error', collect($error->errors())->flatten()->first()); }
        $checkoutKey = $request->session()->get('storefront.checkout.'.$store->id) ?? (string) Str::uuid();
        $request->session()->put('storefront.checkout.'.$store->id, $checkoutKey);
        $request->session()->put('storefront.quote.'.$store->id, $catalog->fingerprint($store, $quote));
        $paystack = app(PaystackGateway::class)->available($store);
        return response()->view('storefront::checkout', compact('store', 'quote', 'catalog', 'checkoutKey', 'paystack'))->header('Cache-Control', 'no-store, private');
    }

    public function place(Request $request, StoreSetting $store, Checkout $checkout)
    {
        $this->ensureOpen($store);
        $data = $request->validate([
            'checkout_key' => 'required|uuid', 'name' => 'required|string|max:120',
            'email' => 'required|email|max:190', 'phone' => 'required|string|max:40|regex:/^\+?[0-9\s().-]{7,40}$/',
            'fulfilment' => 'required|in:pickup,delivery', 'payment_method' => 'required|in:cash,paystack',
            'address' => 'nullable|required_if:fulfilment,delivery|string|max:500',
            'city' => 'nullable|required_if:fulfilment,delivery|string|max:120', 'notes' => 'nullable|string|max:1000',
        ]);
        abort_unless(hash_equals((string) $request->session()->get('storefront.checkout.'.$store->id, ''), $data['checkout_key']), 419);
        $data['quote_hash'] = (string) $request->session()->get('storefront.quote.'.$store->id, '');
        $order = $checkout->place($store, $request->session()->get('storefront.cart.'.$store->id, []), $data);
        $request->session()->forget('storefront.cart.'.$store->id);
        return redirect()->route('storefront.order', [$store, $order->token]);
    }

    public function order(StoreSetting $store, string $token, Catalog $catalog)
    {
        // Existing receipts remain available even when new ordering is disabled.
        $order = StoreOrder::where('business_id', $store->business_id)->where('token', $token)->firstOrFail();
        return response()->view('storefront::order', compact('store', 'order', 'catalog'))->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }
}
