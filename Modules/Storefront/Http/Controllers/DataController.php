<?php

namespace Modules\Storefront\Http\Controllers;

class DataController
{
    public function sell_list_invoice_badges($arguments)
    {
        $sale = $arguments['transaction'];
        if ($sale->source !== 'storefront') { return ''; }
        $businessId = (int) (session('business.id') ?: session('user.business_id'));
        $order = \Modules\Storefront\Entities\StoreOrder::where('business_id', $businessId)
            ->where('transaction_id', $sale->id)->first();
        return $order ? (string) view('storefront::sale_badge', compact('order')) : '';
    }

    public function after_payment_status_updated($arguments)
    {
        $sale = $arguments['transaction'];
        if ($sale->source === 'storefront') {
            \Modules\Storefront\Entities\StoreOrder::where('transaction_id', $sale->id)
                ->where('business_id', $sale->business_id)->where('payment_method', 'cash')
                ->update(['status' => $sale->payment_status === 'paid' ? 'paid' : 'placed']);
        }
    }

    public function user_permissions()
    {
        return [['value' => 'storefront.manage', 'label' => 'Manage storefront settings and customer orders', 'default' => false]];
    }

    public function modifyAdminMenu()
    {
        $businessId = (int) (session('business.id') ?: session('user.business_id'));
        if (auth()->check() && (auth()->user()->can('superadmin') || auth()->user()->hasRole('Admin#'.$businessId) || auth()->user()->can('storefront.manage'))) {
            \Menu::modify('admin-sidebar-menu', function ($menu) {
                $menu->url(route('storefront.orders'), 'Storefront', ['icon' => 'fa fa-shopping-bag', 'active' => request()->segment(1) === 'storefront'])->order(89);
            });
        }
    }
}
