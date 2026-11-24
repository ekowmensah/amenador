<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Existing pickup sales need a status so the sales list offers its shipping editor.
        DB::table('storefront_orders')->whereNotNull('transaction_id')->orderBy('id')->chunkById(100, function ($orders) {
            $map = ['received' => 'ordered', 'preparing' => 'ordered', 'ready' => 'packed', 'out_for_delivery' => 'shipped', 'completed' => 'delivered', 'cancelled' => 'cancelled'];
            foreach ($orders as $order) {
                DB::table('transactions')->where('id', $order->transaction_id)->where('business_id', $order->business_id)
                    ->where('source', 'storefront')->whereNull('shipping_status')
                    ->update(['shipping_status' => $map[$order->fulfilment_status] ?? 'ordered']);
            }
        });
    }

    public function down() {}
};
