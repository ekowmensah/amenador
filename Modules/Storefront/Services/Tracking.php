<?php

namespace Modules\Storefront\Services;

use Modules\Storefront\Entities\StoreOrder;
use Modules\Storefront\Entities\StoreSetting;

class Tracking
{
    public function find(StoreSetting $store, string $code, string $phone): ?StoreOrder
    {
        $input = strtoupper(preg_replace('/\s+/', '', $code));
        $compact = str_replace('-', '', $input);
        $short = preg_match('/^SF[A-Z0-9]{8}$/', $compact)
            ? 'SF-'.substr($compact, 2, 4).'-'.substr($compact, 6, 4) : $input;
        $order = StoreOrder::where('business_id', $store->business_id)
            ->where(function ($query) use ($short, $input) { $query->where('tracking_code', $short)->orWhere('reference', $input); })->first();
        $digits = preg_replace('/\D/', '', $phone);
        $stored = $order ? preg_replace('/\D/', '', $order->customer['phone'] ?? '') : '';
        $matches = hash_equals(hash('sha256', $stored), hash('sha256', $digits));
        return $order && strlen($digits) >= 7 && $matches ? $order : null;
    }

    public function status(StoreOrder $order): array
    {
        $delivery = $order->fulfilment === 'delivery';
        $labels = ['received' => 'Order received', 'preparing' => 'Preparing your order',
            'ready' => $delivery ? 'Ready for dispatch' : 'Ready for pickup'];
        if ($delivery) { $labels['out_for_delivery'] = 'Out for delivery'; }
        $labels['completed'] = $delivery ? 'Delivered' : 'Collected';
        $accepted = (bool) $order->transaction_id;
        $current = array_search($order->fulfilment_status, array_keys($labels), true);
        $current = $current === false ? 0 : $current;
        $steps = [];
        foreach (array_values($labels) as $index => $label) {
            $steps[] = ['label' => $label, 'state' => ! $accepted || $index > $current ? 'pending'
                : ($index < $current || $order->fulfilment_status === 'completed' ? 'done' : 'current')];
        }
        $heading = $labels[$order->fulfilment_status] ?? 'Order received';
        $description = $delivery ? 'We’ll update this page as your order is prepared and sent out.' : 'We’ll update this page when your order is ready to collect.';
        if ($order->status === 'awaiting_payment') {
            $heading = 'Awaiting payment';
            $description = 'Complete payment from your order details to send the order to the store.';
        } elseif ($order->status === 'paid_review') {
            $heading = 'Payment received — confirming your order';
            $description = 'The store is checking your order. Please do not make another payment.';
        } elseif ($order->fulfilment_status === 'completed') {
            $description = $delivery ? 'Your order has been marked as delivered. Thank you for shopping with us.' : 'Your order has been marked as collected. Thank you for shopping with us.';
        } elseif ($order->fulfilment_status === 'ready' && ! $delivery) {
            $description = 'Your order is ready. Please have your tracking code available when collecting.';
        } elseif ($order->fulfilment_status === 'out_for_delivery' && $delivery) {
            $description = 'Your order has left the store and is on its way to your delivery address.';
        }
        if ($order->fulfilment_status === 'cancelled') {
            $heading = 'Order cancelled';
            $description = 'The store has marked this order as cancelled. Contact the store about any payment or refund.';
            foreach ($steps as &$step) { $step['state'] = 'pending'; }
            unset($step);
        }
        return ['code' => $order->tracking_code ?: $order->reference, 'heading' => $heading,
            'description' => $description, 'payment' => in_array($order->status, ['paid', 'paid_review']) ? 'Paid' : ($order->payment_method === 'cash' ? 'Payment due on '.$order->fulfilment : 'Awaiting payment'),
            'fulfilment' => $delivery ? 'Delivery' : 'Store pickup', 'steps' => $steps,
            'updated' => $order->updated_at->toIso8601String()];
    }
}
