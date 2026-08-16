<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->json('payment_confirmations')->nullable()->after('denominations');
        });
    }

    public function down()
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropColumn('payment_confirmations');
        });
    }
};
