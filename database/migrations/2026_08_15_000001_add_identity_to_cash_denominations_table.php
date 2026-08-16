<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cash_denominations', function (Blueprint $table) {
            $table->string('denomination_code', 80)->nullable()->after('amount')->index();
            $table->string('denomination_label', 80)->nullable()->after('denomination_code');
            $table->string('denomination_type', 10)->nullable()->after('denomination_label');
        });
    }

    public function down()
    {
        Schema::table('cash_denominations', function (Blueprint $table) {
            $table->dropIndex(['denomination_code']);
            $table->dropColumn(['denomination_code', 'denomination_label', 'denomination_type']);
        });
    }
};
