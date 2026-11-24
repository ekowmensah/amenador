<?php

namespace Modules\Storefront\Services;

use App\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Storefront\Entities\StoreOrder;
use Modules\Storefront\Entities\StoreSetting;

class Checkout
{
    public function place(StoreSetting $store, array $cart, array $input): StoreOrder
    {
        return DB::transaction(function () use ($store, $cart, $input) {
            Business::where('id', $store->business_id)->lockForUpdate()->firstOrFail();
            $existing = StoreOrder::where('business_id', $store->business_id)->where('checkout_key', $input['checkout_key'])->first();
            if ($existing) { return $existing; }
            if ($input['fulfilment'] === 'delivery' && ! $store->deliveryAvailable()) {
                throw ValidationException::withMessages(['fulfilment' => 'Delivery is not available yet. Please choose pickup.']);
            }
            $method = $input['payment_method'];
            if (($method === 'cash' && ! $store->allow_cash) || ($method === 'paystack' && ! app(PaystackGateway::class)->available($store))) {
                throw ValidationException::withMessages(['payment_method' => 'This payment method is not available.']);
            }
            $quote = app(Catalog::class)->quote($store, $cart, true);
            if (isset($input['quote_hash']) && ! hash_equals($input['quote_hash'], app(Catalog::class)->fingerprint($store, $quote))) {
                throw ValidationException::withMessages(['cart' => 'Prices or delivery charges have changed. Please review the updated checkout total and place your order again.']);
            }
            $delivery = $input['fulfilment'] === 'delivery' ? Catalog::minor($store->delivery_fee) : 0;
            if ($quote['subtotal_minor'] + $delivery < 1) {
                throw ValidationException::withMessages(['cart' => 'Order total must be greater than zero.']);
            }
            $order = StoreOrder::create([
                'business_id' => $store->business_id, 'location_id' => $store->location_id,
                'token' => Str::random(48), 'reference' => 'SF-'.Str::ulid(),
                'checkout_key' => $input['checkout_key'], 'payment_method' => $method,
                'status' => 'awaiting_payment', 'fulfilment' => $input['fulfilment'],
                'currency' => $store->business->currency->code,
                'subtotal_minor' => $quote['subtotal_minor'], 'delivery_minor' => $delivery,
                'total_minor' => $quote['subtotal_minor'] + $delivery,
                'items' => $quote['items'], 'customer' => array_intersect_key($input, array_flip(['name', 'email', 'phone', 'address', 'city'])),
                'notes' => $input['notes'] ?? null,
            ]);
            if ($method === 'cash') {
                app(SaleWriter::class)->create($order, $store, false);
            }
            return $order;
        }, 3);
    }

    public function confirm(StoreOrder $order): StoreOrder
    {
        if ($order->payment_method !== 'paystack' || $order->transaction_id) { return $order; }
        $verified = app(PaystackGateway::class)->verify($order);
        if (! $verified['paid']) {
            return $order;
        }
        DB::transaction(function () use ($order, $verified) {
            $locked = StoreOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();
            if ($locked->transaction_id) { return; }
            $locked->provider_transaction_id = $verified['id'];
            $locked->verified_at = $locked->verified_at ?? now();
            $locked->status = 'paid_review';
            $locked->save();
        });
        return $this->fulfilPaid($order->fresh());
    }

    public function fulfilPaid(StoreOrder $order): StoreOrder
    {
        try {
            DB::transaction(function () use ($order) {
                Business::where('id', $order->business_id)->lockForUpdate()->firstOrFail();
                $locked = StoreOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();
                if ($locked->transaction_id || ! $locked->verified_at) { return; }
                $store = StoreSetting::where('business_id', $locked->business_id)->firstOrFail();
                // Preserve the location agreed at payment even if the administrator changes the storefront later.
                $store->location_id = $locked->location_id;
                app(SaleWriter::class)->create($locked, $store, true);
            }, 3);
        } catch (\Throwable $error) {
            // Keep verified funds recorded even if inventory or accounting needs staff attention.
            StoreOrder::where('id', $order->id)->whereNull('transaction_id')->update(['status' => 'paid_review', 'review_reason' => 'Stock or POS accounting needs review. See application log.']);
            Log::error('Storefront paid order requires review', ['reference' => $order->reference, 'error' => $error->getMessage()]);
        }
        return $order->fresh();
    }
}
