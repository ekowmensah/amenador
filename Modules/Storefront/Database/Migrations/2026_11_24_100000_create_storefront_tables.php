<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('storefront_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->unique();
            $table->unsignedInteger('location_id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->boolean('enabled')->default(false);
            $table->boolean('allow_cash')->default(true);
            $table->boolean('enable_paystack')->default(true);
            $table->boolean('delivery_enabled')->default(false);
            $table->decimal('delivery_fee', 22, 2)->nullable();
            $table->string('delivery_area')->nullable();
            $table->unsignedInteger('payment_account_id')->nullable();
            $table->timestamps();
        });
        Schema::create('storefront_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id');
            $table->string('token', 64)->unique();
            $table->string('reference', 60)->unique();
            $table->uuid('checkout_key');
            $table->unique(['business_id', 'checkout_key']);
            $table->string('payment_method', 30);
            $table->string('status', 30)->default('awaiting_payment')->index();
            $table->string('fulfilment_status', 30)->default('received');
            $table->string('fulfilment', 20);
            $table->string('currency', 10);
            $table->unsignedBigInteger('subtotal_minor');
            $table->unsignedBigInteger('delivery_minor')->default(0);
            $table->unsignedBigInteger('total_minor');
            $table->json('customer');
            $table->json('items');
            $table->text('notes')->nullable();
            $table->text('authorization_url')->nullable();
            $table->string('provider_transaction_id', 100)->nullable()->unique();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedInteger('transaction_id')->nullable()->unique();
            $table->text('review_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('storefront_orders');
        Schema::dropIfExists('storefront_settings');
    }
};
