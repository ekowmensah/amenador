<?php

namespace Modules\Storefront\Services;

use App\Business;
use App\Contact;
use App\Utils\ProductUtil;
use Illuminate\Support\Facades\DB;
use Modules\Storefront\Entities\StoreOrder;
use Modules\Storefront\Entities\StoreSetting;

class SaleWriter
{
    public function create(StoreOrder $order, StoreSetting $store, bool $paid): void
    {
        if ($order->transaction_id) { return; }
        if ($store->business->currency->code !== $order->currency) {
            throw new \RuntimeException('The business currency changed after this order was placed.');
        }
        if (! $store->location || ! $store->location->is_active || $store->location->business_id != $order->business_id) {
            throw new \RuntimeException('The order fulfilment location is no longer available.');
        }
        app(Catalog::class)->assertSnapshotAvailable($store, $order->items);
        $business = Business::where('id', $order->business_id)->lockForUpdate()->firstOrFail();
        $util = new GuestTransactionUtil();
        $util->storeBusinessId = $business->id;
        $customer = $order->customer;
        // Never overwrite a registered customer's profile based on a guest-supplied email.
        $contact = Contact::create([
            'business_id' => $business->id, 'type' => 'customer', 'contact_type' => 'individual',
            'name' => $customer['name'], 'first_name' => $customer['name'],
            'email' => $customer['email'], 'mobile' => $customer['phone'],
            'address_line_1' => $customer['address'] ?? null, 'city' => $customer['city'] ?? null,
            'created_by' => $business->owner_id, 'contact_status' => 'active',
            'contact_id' => $order->reference, 'is_default' => 0,
        ]);
        $base = 0;
        $tax = 0;
        $lines = [];
        foreach ($order->items as $item) {
            $base += $item['base_minor'] * $item['quantity'];
            $tax += ($item['price_minor'] - $item['base_minor']) * $item['quantity'];
            $lines[] = [
                'product_id' => $item['product_id'], 'variation_id' => $item['variation_id'],
                'quantity' => $item['quantity'], 'unit_price' => $item['base_minor'] / 100,
                'unit_price_inc_tax' => $item['price_minor'] / 100,
                'item_tax' => ($item['price_minor'] - $item['base_minor']) / 100,
                'tax_id' => $item['tax_id'], 'enable_stock' => $item['enable_stock'],
            ];
        }
        $input = [
            'status' => 'final', 'contact_id' => $contact->id,
            'location_id' => $order->location_id, 'transaction_date' => now()->toDateTimeString(),
            'final_total' => $order->total_minor / 100, 'discount_amount' => 0,
            'source' => 'storefront', 'is_direct_sale' => 1,
            'sale_note' => 'Online order '.$order->reference.($order->notes ? "\n".$order->notes : ''),
            'shipping_charges' => $order->delivery_minor / 100,
            'shipping_details' => ucfirst($order->fulfilment).' — '.$customer['phone'],
            'shipping_address' => $order->fulfilment === 'delivery' ? ($customer['address'].', '.$customer['city']) : null,
            'shipping_status' => 'ordered',
            'delivered_to' => $customer['name'],
        ];
        // Serialize invoice counters as well as stock changes for this store.
        DB::table('invoice_schemes')->where('business_id', $business->id)->lockForUpdate()->get();
        $sale = $util->createSellTransaction($business->id, $input, ['total_before_tax' => $base / 100, 'tax' => $tax / 100], $business->owner_id, false);
        $util->createOrUpdateSellLines($sale, $lines, $order->location_id, false, null, [], false);
        foreach ($lines as $line) {
            if ($line['enable_stock']) {
                app(ProductUtil::class)->decreaseProductQuantity($line['product_id'], $line['variation_id'], $order->location_id, $line['quantity']);
            }
        }
        // Expiry rules must belong to this business, independently of the browser's staff session.
        $hadBusiness = session()->has('business');
        $previous = session()->get('business');
        session()->put('business', $business->toArray());
        try {
            $util->mapPurchaseSell([
                'id' => $business->id, 'location_id' => $order->location_id,
                'accounting_method' => $business->accounting_method,
                'pos_settings' => ['allow_overselling' => false],
            ], $sale->sell_lines, 'purchase');
        } finally {
            if ($hadBusiness) { session()->put('business', $previous); }
            else { session()->forget('business'); }
        }
        if ($paid) {
            $util->createOrUpdatePaymentLines($sale, [[
                'amount' => $order->total_minor / 100, 'method' => 'card',
                'card_transaction_number' => $order->reference,
                'note' => 'Paystack — '.$order->reference,
                'paid_on' => $order->verified_at->toDateTimeString(),
                'account_id' => $store->payment_account_id,
            ]], $business->id, $business->owner_id, false);
        }
        $sale->payment_status = $paid ? 'paid' : 'due';
        $sale->save();
        $order->transaction_id = $sale->id;
        $order->status = $paid ? 'paid' : 'placed';
        $order->review_reason = null;
        $order->save();
    }
}
