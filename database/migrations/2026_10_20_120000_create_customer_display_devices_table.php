<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customer_display_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id');
            $table->string('register_hash', 64);
            $table->string('token_hash', 64)->unique();
            $table->string('name', 80);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'user_id', 'register_hash'], 'customer_display_register');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_display_devices');
    }
};
