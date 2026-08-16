<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(
                ['business_id', 'type', 'status', 'sub_type', 'shipping_status', 'transaction_date'],
                'idx_dashboard_shipments'
            );
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_dashboard_shipments');
        });
    }
};
