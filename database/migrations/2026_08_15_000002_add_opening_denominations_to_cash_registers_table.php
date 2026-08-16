<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->json('opening_denominations')->nullable()->after('location_id');
        });
    }

    public function down()
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropColumn('opening_denominations');
        });
    }
};
