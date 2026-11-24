<?php

namespace Modules\Storefront\Services;

use App\Transaction;
use Modules\Storefront\Entities\StoreOrder;

class SaleSync
{
    public function saleDeleted(Transaction $sale): void
    {
        if ($sale->source !== 'storefront') { return; }
        StoreOrder::where('business_id', $sale->business_id)->where('transaction_id', $sale->id)
            ->whereNull('sale_deleted_at')->update(['sale_deleted_at' => now(), 'fulfilment_status' => 'cancelled']);
    }

    public function reconcileDeletedSales(int $businessId): void
    {
        // Also cover deletions made before this release or while the module was disabled.
        StoreOrder::where('business_id', $businessId)->whereNotNull('transaction_id')->whereNull('sale_deleted_at')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('transactions')
                    ->whereColumn('transactions.id', 'storefront_orders.transaction_id')
                    ->whereColumn('transactions.business_id', 'storefront_orders.business_id');
            })->update(['sale_deleted_at' => now(), 'fulfilment_status' => 'cancelled']);
    }

    public function refreshDeletion(StoreOrder $order): void
    {
        if (! $order->transaction_id || $order->sale_deleted_at) { return; }
        if (! Transaction::where('business_id', $order->business_id)->where('id', $order->transaction_id)->exists()) {
            $this->reconcileDeletedSales($order->business_id);
            $order->refresh();
        }
    }

    public function fromSale(Transaction $sale): void
    {
        if ($sale->source !== 'storefront' || ! $sale->wasChanged(['shipping_status', 'payment_status'])) { return; }
        $order = StoreOrder::where('business_id', $sale->business_id)->where('transaction_id', $sale->id)->first();
        if (! $order || $order->sale_deleted_at) { return; }
        if ($sale->wasChanged('shipping_status')) {
            $map = ['ordered' => 'received', 'packed' => 'ready', 'shipped' => $order->fulfilment === 'delivery' ? 'out_for_delivery' : 'ready', 'delivered' => 'completed', 'cancelled' => 'cancelled'];
            if (isset($map[$sale->shipping_status])) { $order->fulfilment_status = $map[$sale->shipping_status]; }
        }
        if ($order->payment_method === 'cash' && $sale->wasChanged('payment_status')) {
            $order->status = $sale->payment_status === 'paid' ? 'paid' : 'placed';
        }
        if ($order->isDirty()) { $order->saveQuietly(); }
    }

    public function fromOrder(StoreOrder $order): void
    {
        if (! $order->transaction_id || $order->sale_deleted_at || ! $order->wasChanged('fulfilment_status')) { return; }
        $map = ['received' => 'ordered', 'preparing' => 'ordered', 'ready' => 'packed', 'out_for_delivery' => 'shipped', 'completed' => 'delivered', 'cancelled' => 'cancelled'];
        if (! isset($map[$order->fulfilment_status])) { return; }
        $sale = Transaction::where('business_id', $order->business_id)->where('source', 'storefront')->find($order->transaction_id);
        if ($sale) { $sale->shipping_status = $map[$order->fulfilment_status]; $sale->saveQuietly(); }
    }
}
