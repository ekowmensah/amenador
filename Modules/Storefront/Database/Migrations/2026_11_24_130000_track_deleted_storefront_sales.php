<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->timestamp('sale_deleted_at')->nullable()->index();
        });
        DB::table('storefront_orders')->whereNotNull('transaction_id')->whereNotExists(function ($query) {
            $query->selectRaw('1')->from('transactions')
                ->whereColumn('transactions.id', 'storefront_orders.transaction_id')
                ->whereColumn('transactions.business_id', 'storefront_orders.business_id');
        })->update(['sale_deleted_at' => now(), 'fulfilment_status' => 'cancelled', 'updated_at' => now()]);
    }

    public function down()
    {
        Schema::table('storefront_orders', function (Blueprint $table) { $table->dropIndex(['sale_deleted_at']); });
        Schema::table('storefront_orders', function (Blueprint $table) { $table->dropColumn('sale_deleted_at'); });
    }
};
