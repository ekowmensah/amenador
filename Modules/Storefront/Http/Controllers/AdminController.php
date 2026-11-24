<?php

namespace Modules\Storefront\Http\Controllers;

use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Modules\Storefront\Entities\StoreSetting;
use Modules\Storefront\Entities\StoreOrder;
use Modules\Storefront\Services\Catalog;
use Modules\Storefront\Services\Checkout;
use Modules\Storefront\Services\PaystackGateway;

class AdminController extends Controller
{
    protected function businessId(): int
    {
        $id = (int) (session('business.id') ?: session('user.business_id'));
        abort_unless($id && (auth()->user()->can('superadmin') || auth()->user()->hasRole('Admin#'.$id) || auth()->user()->can('storefront.manage')), 403);
        return $id;
    }

    protected function store(): StoreSetting
    {
        $id = $this->businessId();
        abort_unless(Schema::hasTable('storefront_settings'), 503, 'Install the Storefront module first.');
        return StoreSetting::where('business_id', $id)->firstOrFail();
    }

    public function settings(PaystackGateway $gateway)
    {
        $store = $this->store();
        $locations = BusinessLocation::where('business_id', $store->business_id)->where('is_active', 1)->get();
        $configured = $gateway->available($store);
        return view('storefront::settings', compact('store', 'locations', 'configured'));
    }

    public function save(Request $request)
    {
        $store = $this->store();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('storefront_settings')->ignore($store->id)],
            'location_id' => ['required', Rule::exists('business_locations', 'id')->where('business_id', $store->business_id)->where('is_active', 1)],
            'delivery_fee' => 'nullable|numeric|min:0|max:1000000',
            'delivery_area' => 'nullable|string|max:190',
            'payment_account_id' => ['nullable', Rule::exists('accounts', 'id')->where('business_id', $store->business_id)->whereNull('deleted_at')],
        ]);
        foreach (['enabled', 'allow_cash', 'enable_paystack', 'delivery_enabled'] as $field) { $data[$field] = $request->boolean($field); }
        if ($data['delivery_enabled'] && ! isset($data['delivery_fee'])) { return back()->withInput()->withErrors(['delivery_fee' => 'Enter a delivery charge in the system currency. Use 0 for free delivery.']); }
        $store->update($data);
        return back()->with('status', ['success' => 1, 'msg' => 'Storefront settings saved.']);
    }

    public function orders(Catalog $catalog)
    {
        $store = $this->store();
        $orders = StoreOrder::where('business_id', $store->business_id)->latest()->paginate(25);
        return view('storefront::orders', compact('store', 'orders', 'catalog'));
    }

    public function updateOrder(Request $request, int $id, Checkout $checkout)
    {
        $store = $this->store();
        $order = StoreOrder::where('business_id', $store->business_id)->findOrFail($id);
        if ($request->input('action') === 'retry' && $order->verified_at && ! $order->transaction_id) {
            $checkout->fulfilPaid($order);
        } else {
            $data = $request->validate(['fulfilment_status' => 'required|in:received,preparing,ready,out_for_delivery,completed,cancelled']);
            abort_unless($order->transaction_id, 422, 'Confirm payment and POS sale creation first.');
            abort_if($order->fulfilment === 'pickup' && $data['fulfilment_status'] === 'out_for_delivery', 422);
            $order->update($data);
        }
        return back()->with('status', ['success' => 1, 'msg' => 'Order updated.']);
    }
}
