<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Storefront\Entities\StoreOrder;

return new class extends Migration
{
    public function up()
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->string('tracking_code', 20)->nullable()->unique();
        });
        DB::table('storefront_orders')->whereNull('tracking_code')->orderBy('id')->chunkById(100, function ($orders) {
            foreach ($orders as $order) {
                DB::table('storefront_orders')->where('id', $order->id)->update(['tracking_code' => StoreOrder::newTrackingCode()]);
            }
        });
    }

    public function down()
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->dropUnique(['tracking_code']);
            $table->dropColumn('tracking_code');
        });
    }
};
