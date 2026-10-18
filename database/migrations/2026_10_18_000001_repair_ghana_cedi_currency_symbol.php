<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('currencies')
            ->where('code', 'GHS')
            ->where('symbol', 'like', '%?%')
            ->update(['symbol' => "\u{00A2}"]);
    }

    public function down()
    {
        // Preserve the corrected symbol when rolling back.
    }
};
