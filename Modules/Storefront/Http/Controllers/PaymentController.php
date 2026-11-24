<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Storefront\Entities\StoreSetting;
use Modules\Storefront\Entities\StoreOrder;
use Modules\Storefront\Services\Checkout;
use Modules\Storefront\Services\PaystackGateway;

class PaymentController extends Controller
{
    public function start(StoreSetting $store, string $token, PaystackGateway $gateway)
    {
        $order = StoreOrder::where('business_id', $store->business_id)->where('token', $token)->firstOrFail();
        abort_unless($order->payment_method === 'paystack', 404);
        if ($order->verified_at || $order->transaction_id) { return redirect()->route('storefront.order', [$store, $token]); }
        abort_unless($gateway->available($store), 503, 'Online payment is currently unavailable. Please contact the store.');
        try {
            $url = DB::transaction(function () use ($order, $gateway) {
                $locked = StoreOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();
                if (! $locked->authorization_url) {
                    $locked->authorization_url = $gateway->initialize($locked);
                    $locked->save();
                }
                return $locked->authorization_url;
            });
            return redirect()->away($url);
        } catch (\Throwable $error) {
            Log::warning('Storefront payment initialization failed', ['reference' => $order->reference]);
            return back()->with('payment_error', 'Payment could not start. Your order is saved. Please try again or contact the store.');
        }
    }

    public function verify(StoreSetting $store, string $token, Checkout $checkout)
    {
        $order = StoreOrder::where('business_id', $store->business_id)->where('token', $token)->firstOrFail();
        try { $checkout->confirm($order); }
        catch (\Throwable $error) { return back()->with('payment_error', 'Payment has not been confirmed. Please check again shortly or contact the store.'); }
        return redirect()->route('storefront.order', [$store, $token]);
    }

    public function callback(Request $request, Checkout $checkout)
    {
        $data = $request->validate(['reference' => 'required|string|max:60|regex:/^SF-[A-Z0-9]+$/']);
        $order = StoreOrder::where('reference', $data['reference'])->where('payment_method', 'paystack')->firstOrFail();
        $store = StoreSetting::where('business_id', $order->business_id)->firstOrFail();
        try { $checkout->confirm($order); }
        catch (\Throwable $error) {
            return redirect()->route('storefront.order', [$store, $order->token])->with('payment_error', 'We could not confirm payment yet. Use Check payment status to try again.');
        }
        return redirect()->route('storefront.order', [$store, $order->token]);
    }

    public function webhook(Request $request, PaystackGateway $gateway, Checkout $checkout)
    {
        abort_unless($gateway->validSignature($request->getContent(), $request->header('Paystack-Signature'), $request->header('x-paystack-signature')), 401);
        if ($request->input('event') === 'charge.success') {
            $reference = $request->input('data.reference');
            $order = is_string($reference) ? StoreOrder::where('reference', $reference)->where('payment_method', 'paystack')->first() : null;
            if ($order) {
                // Verify with Paystack as well as checking the event signature. Never trust the redirect or payload amount alone.
                try { $checkout->confirm($order); }
                catch (\Throwable $error) {
                    Log::warning('Storefront webhook verification failed', ['reference' => $order->reference]);
                    return response()->json(['received' => false], 503);
                }
            }
        }
        return response()->json(['received' => true]);
    }
}
