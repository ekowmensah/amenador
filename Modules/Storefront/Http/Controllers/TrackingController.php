<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Storefront\Entities\StoreOrder;
use Modules\Storefront\Entities\StoreSetting;
use Modules\Storefront\Services\Tracking;

class TrackingController extends Controller
{
    protected function authorizedOrder(Request $request, StoreSetting $store): ?StoreOrder
    {
        $grant = $request->session()->get('storefront.tracking.'.$store->id);
        if (! is_array($grant) || ($grant['expires'] ?? 0) < time()) { return null; }
        return StoreOrder::where('business_id', $store->business_id)->find($grant['id'] ?? 0);
    }

    public function index(Request $request, StoreSetting $store, Tracking $tracking)
    {
        $order = $this->authorizedOrder($request, $store);
        $progress = $order ? $tracking->status($order) : null;
        return response()->view('storefront::tracking', compact('store', 'order', 'progress'))
            ->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function lookup(Request $request, StoreSetting $store, Tracking $tracking)
    {
        $input = $request->validate(['code' => 'required|string|max:60', 'phone' => 'required|string|max:40']);
        $order = $tracking->find($store, $input['code'], $input['phone']);
        if (! $order) {
            $request->session()->forget('storefront.tracking.'.$store->id);
            return redirect()->route('storefront.track', $store)->withInput(['code' => $input['code']])
                ->withErrors(['tracking' => 'We couldn’t find an order with those details. Check the code and the full phone number used at checkout.']);
        }
        $request->session()->put('storefront.tracking.'.$store->id, ['id' => $order->id, 'expires' => time() + 3600]);
        return redirect()->route('storefront.track', $store);
    }

    public function status(Request $request, StoreSetting $store, Tracking $tracking)
    {
        $order = $this->authorizedOrder($request, $store);
        if (! $order) { return response()->json(['message' => 'Enter your code and phone number again to continue tracking.'], 401)->header('Cache-Control', 'no-store, private'); }
        return response()->json($tracking->status($order))->header('Cache-Control', 'no-store, private');
    }
}
