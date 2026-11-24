<?php

namespace Modules\Storefront\Tests;

use App\Business;
use App\BusinessLocation;
use App\Currency;
use App\Product;
use App\Variation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mockery;
use Modules\Storefront\Entities\StoreSetting;
use Modules\Storefront\Entities\StoreOrder;
use Modules\Storefront\Services\Catalog;
use Modules\Storefront\Services\Checkout;
use Modules\Storefront\Services\PaystackGateway;
use Modules\Storefront\Services\SaleWriter;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    private $store;
    private $variation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->register(\Modules\Storefront\Providers\StorefrontServiceProvider::class);
        if (! \Illuminate\Support\Facades\Route::has('storefront.index')) {
            require __DIR__.'/../Routes/web.php';
            app('router')->getRoutes()->refreshNameLookups();
            app('router')->getRoutes()->refreshActionLookups();
        }
        config(['database.default' => 'storefront_test', 'database.connections.storefront_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'storefront.paystack_secret' => 'sk_test_fixture', 'storefront.paystack_webhook_secret' => '']);
        Schema::create('business', function (Blueprint $table) {
            $table->increments('id'); $table->string('name'); $table->integer('currency_id');
            $table->string('currency_symbol_placement')->default('before'); $table->text('common_settings')->nullable(); $table->timestamps();
        });
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id'); $table->integer('business_id'); $table->string('source')->nullable();
            $table->string('shipping_status')->nullable(); $table->string('payment_status')->default('due'); $table->timestamps();
        });
        Schema::create('system', function (Blueprint $table) {
            $table->increments('id'); $table->string('key'); $table->text('value')->nullable();
        });
        Schema::create('currencies', function (Blueprint $table) {
            $table->increments('id'); $table->string('code'); $table->string('symbol');
            $table->string('decimal_separator')->default('.'); $table->string('thousand_separator')->default(',');
        });
        Schema::create('business_locations', function (Blueprint $table) {
            $table->increments('id'); $table->integer('business_id'); $table->string('name');
            $table->boolean('is_active')->default(true); $table->string('city')->nullable(); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table) { $table->increments('id'); $table->string('name'); $table->softDeletes(); });
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id'); $table->integer('business_id'); $table->string('name');
            $table->string('type')->default('single'); $table->boolean('is_inactive')->default(false);
            $table->boolean('not_for_selling')->default(false); $table->boolean('enable_stock')->default(true);
            $table->integer('category_id')->nullable(); $table->integer('sub_category_id')->nullable();
            $table->integer('tax')->nullable(); $table->string('image')->nullable(); $table->timestamps();
        });
        Schema::create('product_locations', function (Blueprint $table) { $table->integer('product_id'); $table->integer('location_id'); });
        Schema::create('variations', function (Blueprint $table) {
            $table->increments('id'); $table->integer('product_id'); $table->string('name')->default('DUMMY');
            $table->decimal('sell_price_inc_tax', 22, 4); $table->decimal('default_sell_price', 22, 4);
            $table->softDeletes(); $table->timestamps();
        });
        Schema::create('variation_location_details', function (Blueprint $table) {
            $table->increments('id'); $table->integer('variation_id'); $table->integer('location_id');
            $table->decimal('qty_available', 22, 4);
        });
        (require __DIR__.'/../Database/Migrations/2026_11_24_100000_create_storefront_tables.php')->up();
        (require __DIR__.'/../Database/Migrations/2026_11_24_110000_add_storefront_tracking_codes.php')->up();
        DB::table('currencies')->insert(['id' => 1, 'code' => 'GHS', 'symbol' => 'GHS']);
        $business = Business::create(['name' => 'Test shop', 'currency_id' => 1]);
        $location = BusinessLocation::create(['business_id' => $business->id, 'name' => 'Store', 'is_active' => 1]);
        $this->store = StoreSetting::create(['business_id' => $business->id, 'location_id' => $location->id, 'name' => 'Test shop', 'slug' => 'test-shop', 'enabled' => true]);
        $this->store->refresh();
        $product = Product::create(['business_id' => $business->id, 'name' => 'Test item']);
        DB::table('product_locations')->insert(['product_id' => $product->id, 'location_id' => $location->id]);
        $this->variation = Variation::create(['product_id' => $product->id, 'sell_price_inc_tax' => 12.50, 'default_sell_price' => 10]);
        DB::table('variation_location_details')->insert(['variation_id' => $this->variation->id, 'location_id' => $location->id, 'qty_available' => 5]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('storefront_test');
        parent::tearDown();
    }

    private function input(array $overrides = []): array
    {
        return array_merge(['checkout_key' => (string) Str::uuid(), 'payment_method' => 'paystack', 'fulfilment' => 'pickup', 'name' => 'Customer', 'email' => 'customer@example.invalid', 'phone' => '0240000000'], $overrides);
    }

    public function test_storefront_uses_each_business_public_layout_and_matching_preset(): void
    {
        session(['business.common_settings' => ['public_layout' => 'hardware']]);
        foreach (array_keys(config('public_page_layouts')) as $layout) {
            $business = $this->store->business;
            $business->common_settings = ['public_layout' => 'follow_pos', 'pos_layout' => $layout];
            $this->store->setRelation('business', $business);
            $appearance = app(\Modules\Storefront\Services\PublicAppearance::class)->data($this->store);
            $this->assertSame($layout, $appearance['pageLayout']);
            $this->assertSame(config('public_page_layouts.'.$layout), $appearance['pagePreset']);
            $html = view('storefront::index', ['store' => $this->store, 'products' => collect(), 'categories' => [], 'catalog' => app(Catalog::class)])->render();
            $this->assertStringContainsString('data-public-layout="'.$layout.'"', $html);
            $this->assertStringContainsString(e($appearance['pagePreset']['title']), $html);
            $this->assertStringContainsString('appearance.css', $html);
        }
    }

    public function test_public_layout_override_and_portable_fallback(): void
    {
        $business = $this->store->business;
        $business->common_settings = ['public_layout' => 'pharmacy', 'pos_layout' => 'hardware'];
        $this->store->setRelation('business', $business);
        $service = app(\Modules\Storefront\Services\PublicAppearance::class);
        $this->assertSame('pharmacy', $service->data($this->store)['pageLayout']);
        config(['public_page_layouts' => null]);
        $this->assertSame('pharmacy', $service->data($this->store)['pageLayout']);
        foreach (config('storefront_presets') as $preset) {
            $this->assertFileExists(__DIR__.'/../Resources/assets/art/'.basename($preset['image']));
        }
        $business->common_settings = ['public_layout' => 'unknown'];
        $this->assertSame('restaurant', $service->data($this->store)['pageLayout']);
    }

    private function order(): StoreOrder
    {
        return app(Checkout::class)->place($this->store, [$this->variation->id => 2], $this->input());
    }

    public function test_catalog_uses_only_this_business_active_assigned_products(): void
    {
        Product::create(['business_id' => 99, 'name' => 'Private other business product']);
        $catalog = app(Catalog::class);
        $this->assertCount(1, $catalog->products($this->store));
        $this->assertSame(1250, $catalog->products($this->store)->first()['price_minor']);
        Product::first()->update(['not_for_selling' => true]);
        $this->assertCount(0, $catalog->products($this->store));
    }

    public function test_stock_is_checked_and_quantities_are_not_trusted(): void
    {
        $this->expectException(ValidationException::class);
        app(Catalog::class)->quote($this->store, [$this->variation->id => 6], true);
    }

    public function test_cart_cannot_use_a_product_from_another_business(): void
    {
        Product::first()->update(['business_id' => 99]);
        $this->expectException(ValidationException::class);
        app(Catalog::class)->quote($this->store, [$this->variation->id => 1]);
    }

    public function test_total_comes_from_pos_prices_and_configured_delivery_fee(): void
    {
        $this->store->update(['delivery_enabled' => true, 'delivery_fee' => '15.00']);
        $order = app(Checkout::class)->place($this->store, [$this->variation->id => 2], $this->input(['fulfilment' => 'delivery', 'address' => 'Test road', 'city' => 'Accra', 'amount' => 1, 'delivery_fee' => 0]));
        $this->assertEquals(4000, $order->total_minor);
        $this->assertEquals(1500, $order->delivery_minor);
        $this->assertSame('GHS', $order->currency);
        $this->assertNull($order->transaction_id);
    }

    public function test_delivery_without_a_configured_fee_is_rejected(): void
    {
        $this->store->update(['delivery_enabled' => true]);
        $this->expectException(ValidationException::class);
        app(Checkout::class)->place($this->store, [$this->variation->id => 1], $this->input(['fulfilment' => 'delivery']));
    }

    public function test_cash_checkout_is_idempotent_and_creates_one_unpaid_sale(): void
    {
        $writer = Mockery::mock(SaleWriter::class);
        $writer->shouldReceive('create')->once()->with(Mockery::type(StoreOrder::class), Mockery::type(StoreSetting::class), false)->andReturnUsing(function ($order) {
            $order->update(['transaction_id' => 123, 'status' => 'placed']);
        });
        $this->app->instance(SaleWriter::class, $writer);
        $input = $this->input(['payment_method' => 'cash']);
        $first = app(Checkout::class)->place($this->store, [$this->variation->id => 1], $input);
        $again = app(Checkout::class)->place($this->store, [], $input);
        $this->assertSame($first->id, $again->id);
        $this->assertSame('placed', $again->status);
        $this->assertEquals(1, StoreOrder::count());
    }

    public function test_unconfigured_paystack_is_unavailable(): void
    {
        config(['storefront.paystack_secret' => '']);
        $this->assertFalse(app(PaystackGateway::class)->available($this->store));
        $this->expectException(ValidationException::class);
        $this->order();
    }

    public function test_paystack_receives_server_calculated_minor_units_and_currency(): void
    {
        Http::fake(['*' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/test-payment']])]);
        $order = $this->order();
        $this->assertSame('https://checkout.paystack.com/test-payment', app(PaystackGateway::class)->initialize($order));
        Http::assertSent(function ($request) use ($order) {
            return $request->url() === 'https://api.paystack.co/transaction/initialize' && $request['amount'] === '2500' && $request['currency'] === 'GHS' && $request['reference'] === $order->reference;
        });
    }

    public function test_payment_cannot_be_confirmed_with_wrong_amount_currency_reference_or_customer(): void
    {
        $order = $this->order();
        $valid = ['status' => 'success', 'reference' => $order->reference, 'amount' => 2500, 'currency' => 'GHS', 'id' => 51, 'customer' => ['email' => $order->customer['email']], 'domain' => 'test'];
        foreach ([['amount' => 1], ['currency' => 'USD'], ['reference' => 'wrong'], ['customer' => ['email' => 'wrong@example.invalid']], ['domain' => 'live']] as $wrong) {
            Http::fake(['*' => Http::response(['status' => true, 'data' => array_merge($valid, $wrong)])]);
            try { app(PaystackGateway::class)->verify($order); $this->fail('Mismatched payment was accepted'); }
            catch (\RuntimeException $error) { $this->assertStringContainsString('did not match', $error->getMessage()); }
        }
    }

    public function test_repeated_payment_confirmation_creates_one_paid_sale(): void
    {
        $order = $this->order();
        $gateway = Mockery::mock(PaystackGateway::class);
        $gateway->shouldReceive('verify')->andReturn(['paid' => true, 'id' => '999']);
        $this->app->instance(PaystackGateway::class, $gateway);
        $writer = Mockery::mock(SaleWriter::class);
        $writer->shouldReceive('create')->once()->with(Mockery::type(StoreOrder::class), Mockery::type(StoreSetting::class), true)->andReturnUsing(function ($order) { $order->update(['transaction_id' => 456, 'status' => 'paid']); });
        $this->app->instance(SaleWriter::class, $writer);
        $checkout = app(Checkout::class);
        $this->assertSame('paid', $checkout->confirm($order)->status);
        $this->assertSame('paid', $checkout->confirm($order)->status);
        $this->assertEquals(456, $order->fresh()->transaction_id);
    }

    public function test_verified_funds_are_kept_when_stock_or_accounting_prevents_sale_creation(): void
    {
        $order = $this->order();
        $gateway = Mockery::mock(PaystackGateway::class);
        $gateway->shouldReceive('verify')->andReturn(['paid' => true, 'id' => '1000']);
        $this->app->instance(PaystackGateway::class, $gateway);
        $writer = Mockery::mock(SaleWriter::class);
        $writer->shouldReceive('create')->once()->andThrow(new \RuntimeException('Stock changed'));
        $this->app->instance(SaleWriter::class, $writer);
        $confirmed = app(Checkout::class)->confirm($order);
        $this->assertSame('paid_review', $confirmed->status);
        $this->assertNotNull($confirmed->verified_at);
        $this->assertNull($confirmed->transaction_id);
    }

    public function test_pending_payment_does_not_create_a_sale(): void
    {
        $order = $this->order();
        Http::fake(['*' => Http::response(['status' => true, 'data' => ['status' => 'pending']])]);
        $this->assertSame('awaiting_payment', app(Checkout::class)->confirm($order)->status);
        $this->assertNull($order->fresh()->transaction_id);
    }

    public function test_webhook_signature_supports_legacy_and_timestamped_signatures_without_downgrade(): void
    {
        $gateway = new PaystackGateway();
        $body = '{"event":"charge.success"}';
        $legacy = hash_hmac('sha512', $body, 'sk_test_fixture');
        $this->assertTrue($gateway->validSignature($body, null, $legacy));
        $this->assertFalse($gateway->validSignature($body.' ', null, $legacy));
        config(['storefront.paystack_webhook_secret' => 'whsec_fixture']);
        $time = time();
        $signature = hash_hmac('sha512', $time.'.'.$body, 'whsec_fixture');
        $this->assertTrue($gateway->validSignature($body, 't='.$time.',v1=whk_test:'.$signature, null));
        $this->assertFalse($gateway->validSignature($body, null, $legacy));
        $old = $time - 1000;
        $this->assertFalse($gateway->validSignature($body, 't='.$old.',v1=whk_test:'.hash_hmac('sha512', $old.'.'.$body, 'whsec_fixture'), null));
    }

    public function test_checkout_rejects_a_price_change_after_the_customer_reviewed_totals(): void
    {
        $catalog = app(Catalog::class);
        $cart = [$this->variation->id => 1];
        $fingerprint = $catalog->fingerprint($this->store, $catalog->quote($this->store, $cart));
        $this->variation->update(['sell_price_inc_tax' => 50]);
        $this->expectException(ValidationException::class);
        app(Checkout::class)->place($this->store, $cart, $this->input(['quote_hash' => $fingerprint]));
    }

    public function test_orders_get_short_unique_tracking_codes_and_can_use_the_original_reference(): void
    {
        $first = $this->order();
        $second = $this->order();
        $this->assertMatchesRegularExpression('/^SF-[2-9A-HJ-NP-Z]{4}-[2-9A-HJ-NP-Z]{4}$/', $first->tracking_code);
        $this->assertNotSame($first->tracking_code, $second->tracking_code);
        $tracking = new \Modules\Storefront\Services\Tracking();
        $this->assertSame($first->id, $tracking->find($this->store, strtolower(str_replace('-', ' ', $first->tracking_code)), '(024) 000-0000')->id);
        $this->assertSame($first->id, $tracking->find($this->store, $first->reference, '0240000000')->id);
    }

    public function test_tracking_rejects_wrong_phone_and_other_business_without_exposing_the_order(): void
    {
        $order = $this->order();
        $tracking = new \Modules\Storefront\Services\Tracking();
        $this->assertNull($tracking->find($this->store, $order->tracking_code, '0000'));
        $this->assertNull($tracking->find($this->store, $order->tracking_code, '0249999999'));
        $other = StoreSetting::create(['business_id' => 99, 'location_id' => 1, 'name' => 'Other store', 'slug' => 'other-store']);
        $this->assertNull($tracking->find($other, $order->tracking_code, '0240000000'));
    }

    public function test_tracking_status_requires_a_verified_session_and_returns_no_customer_details(): void
    {
        $order = $this->order();
        $url = route('storefront.track.status', $this->store);
        $this->getJson($url)->assertStatus(401);
        $this->post(route('storefront.track.lookup', $this->store), ['code' => $order->tracking_code, 'phone' => '0240000000'])->assertRedirect(route('storefront.track', $this->store));
        $this->getJson($url)->assertOk()->assertJsonPath('heading', 'Awaiting payment');
        $order->update(['transaction_id' => 123, 'status' => 'placed', 'fulfilment_status' => 'preparing']);
        $response = $this->getJson($url)->assertOk()->assertJsonPath('heading', 'Preparing your order');
        foreach (['customer', 'phone', 'email', 'address', 'items', 'token'] as $field) { $this->assertArrayNotHasKey($field, $response->json()); }
        $this->withSession(['storefront.tracking.'.$this->store->id => ['id' => $order->id, 'expires' => time() - 10]])->getJson($url)->assertStatus(401);
    }

    public function test_pickup_and_delivery_tracking_have_distinct_completion_steps(): void
    {
        $order = $this->order();
        $order->transaction_id = 123;
        $order->status = 'paid';
        $order->fulfilment_status = 'completed';
        $tracking = new \Modules\Storefront\Services\Tracking();
        $pickup = $tracking->status($order);
        $this->assertSame('Collected', $pickup['heading']);
        $this->assertCount(4, $pickup['steps']);
        $this->assertSame(['done'], array_values(array_unique(array_column($pickup['steps'], 'state'))));
        $order->fulfilment = 'delivery';
        $this->assertSame('Delivered', $tracking->status($order)['heading']);
        $this->assertCount(5, $tracking->status($order)['steps']);
    }

    public function test_sales_shipping_and_cash_payment_updates_reach_tracking_independently(): void
    {
        $order = $this->order();
        $sale = \App\Transaction::create(['business_id' => $order->business_id, 'source' => 'storefront', 'shipping_status' => 'ordered']);
        $order->update(['transaction_id' => $sale->id, 'payment_method' => 'cash', 'status' => 'placed', 'fulfilment' => 'delivery']);
        foreach (['packed' => 'ready', 'shipped' => 'out_for_delivery', 'delivered' => 'completed'] as $shipping => $progress) {
            $sale->update(['shipping_status' => $shipping]);
            $this->assertSame($progress, $order->fresh()->fulfilment_status);
            $this->assertSame('placed', $order->fresh()->status);
        }
        $sale->update(['payment_status' => 'partial']);
        $this->assertSame('placed', $order->fresh()->status);
        $sale->update(['payment_status' => 'paid']);
        $this->assertSame('paid', $order->fresh()->status);
        $sale->update(['payment_status' => 'due']);
        $this->assertSame('placed', $order->fresh()->status);
        $sale->update(['shipping_status' => 'cancelled']);
        $this->assertSame('Order cancelled', app(\Modules\Storefront\Services\Tracking::class)->status($order->fresh())['heading']);
    }

    public function test_storefront_updates_sales_shipping_without_changing_payment_or_losing_preparing(): void
    {
        $order = $this->order();
        $sale = \App\Transaction::create(['business_id' => $order->business_id, 'source' => 'storefront', 'shipping_status' => 'ordered']);
        $order->update(['transaction_id' => $sale->id, 'status' => 'placed']);
        foreach (['preparing' => 'ordered', 'ready' => 'packed', 'completed' => 'delivered'] as $progress => $shipping) {
            $order->update(['fulfilment_status' => $progress]);
            $this->assertSame($shipping, $sale->fresh()->shipping_status);
            $this->assertSame($progress, $order->fresh()->fulfilment_status);
            $this->assertSame('due', $sale->fresh()->payment_status);
        }
        $sale->business_id = 999;
        $sale->saveQuietly();
        $sale->update(['shipping_status' => 'cancelled', 'payment_status' => 'paid']);
        $this->assertSame('completed', $order->fresh()->fulfilment_status);
        $this->assertSame('placed', $order->fresh()->status);
    }

    public function test_sales_badge_is_scoped_to_business_and_escapes_tracking_code(): void
    {
        $order = $this->order();
        $order->update(['transaction_id' => 321, 'payment_method' => 'cash', 'status' => 'placed', 'tracking_code' => '<script>alert(1)</script>']);
        $controller = new \Modules\Storefront\Http\Controllers\DataController();
        $sale = (object) ['id' => 321, 'source' => 'storefront'];
        session(['business.id' => 999]);
        $this->assertSame('', $controller->sell_list_invoice_badges(['transaction' => $sale]));
        session(['business.id' => $order->business_id]);
        $html = $controller->sell_list_invoice_badges(['transaction' => $sale]);
        $this->assertStringContainsString('Pay on pickup', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_upgrade_initializes_existing_pickup_shipping_without_overwriting_sales_updates(): void
    {
        $order = $this->order();
        $sale = \App\Transaction::create(['business_id' => $order->business_id, 'source' => 'storefront']);
        $order->update(['transaction_id' => $sale->id, 'fulfilment_status' => 'ready']);
        $sale->shipping_status = null;
        $sale->saveQuietly();
        $migration = require __DIR__.'/../Database/Migrations/2026_11_24_120000_initialize_storefront_pickup_shipping.php';
        $migration->up();
        $this->assertSame('packed', $sale->fresh()->shipping_status);
        $sale->shipping_status = 'delivered';
        $sale->saveQuietly();
        $migration->up();
        $this->assertSame('delivered', $sale->fresh()->shipping_status);
    }

    public function test_uninstall_disables_module_and_ordering_but_retains_orders_and_settings(): void
    {
        $order = $this->order();
        $user = Mockery::mock(\App\User::class)->makePartial();
        $user->shouldReceive('can')->with('superadmin')->andReturn(true);
        auth()->setUser($user);
        DB::table('system')->insert(['key' => 'storefront_version', 'value' => '1.3.1']);
        $module = Mockery::mock();
        $module->shouldReceive('disable')->once();
        \Module::shouldReceive('findOrFail')->with('Storefront')->once()->andReturn($module);
        $files = Mockery::mock(\Illuminate\Filesystem\Filesystem::class)->makePartial();
        $files->shouldReceive('delete')->with(app()->getCachedRoutesPath())->once()->andReturn(true);
        app()->instance('files', $files);
        $controller = new \Modules\Storefront\Http\Controllers\InstallController();
        $this->assertSame('storefront::uninstall', $controller->uninstall()->name());
        $this->assertTrue($this->store->fresh()->enabled);
        $response = $controller->runUninstall();
        $this->assertFalse($this->store->fresh()->enabled);
        $this->assertNotNull($order->fresh());
        $this->assertSame(0, DB::table('system')->where('key', 'storefront_version')->count());
        $this->assertSame(action([\App\Http\Controllers\Install\ModulesController::class, 'index']), $response->getTargetUrl());
    }

    public function test_uninstall_requires_module_management_permission(): void
    {
        $user = Mockery::mock(\App\User::class)->makePartial();
        $user->shouldReceive('can')->with('superadmin')->andReturn(false);
        $user->shouldReceive('can')->with('manage_modules')->andReturn(false);
        auth()->setUser($user);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new \Modules\Storefront\Http\Controllers\InstallController())->runUninstall();
    }

    public function test_installer_runs_module_migrations_without_console_and_preserves_existing_data(): void
    {
        Schema::drop('storefront_orders');
        Schema::drop('storefront_settings');
        $installer = app(\Modules\Storefront\Services\Installer::class);
        $store = $installer->install(1);
        $this->assertFalse($store->fresh()->enabled);
        $this->assertTrue(Schema::hasColumn('storefront_orders', 'tracking_code'));
        $this->assertSame(3, DB::table('migrations')->count());
        $store->update(['name' => 'Preserved shop name', 'enabled' => true, 'enable_paystack' => true]);
        $this->store = $store;
        $order = $this->order();
        $again = $installer->install(1);
        $this->assertSame($store->id, $again->id);
        $this->assertSame('Preserved shop name', $again->name);
        $this->assertTrue($again->enabled);
        $this->assertNotNull($order->fresh());
        $this->assertSame(3, DB::table('migrations')->count());
        $this->assertSame(config('storefront.module_version'), DB::table('system')->where('key', 'storefront_version')->value('value'));
        $user = Mockery::mock(\App\User::class)->makePartial();
        $user->shouldReceive('can')->with('superadmin')->andReturn(true);
        auth()->setUser($user);
        session(['user.business_id' => 1]);
        $response = (new \Modules\Storefront\Http\Controllers\InstallController())->install();
        $this->assertSame(route('storefront.settings'), $response->getTargetUrl());
    }

    public function test_public_homepage_promotes_the_matching_store_across_all_layouts(): void
    {
        config(['constants.public_catalog_business_id' => $this->store->business_id, 'storefront.default_business_id' => 999]);
        session(['business.id' => 999]);
        $url = route('storefront.index', $this->store);
        foreach (config('public_page_layouts') as $layout => $preset) {
            $request = \Illuminate\Http\Request::create('/');
            $html = (new \Modules\Storefront\Http\Middleware\HostIntegration())->handle($request, fn () => response()->view('welcome', ['pageLayout' => $layout, 'pagePreset' => $preset, 'publicBusinessName' => 'Test shop']))->getContent();
            $this->assertStringContainsString('href="'.$url.'">Shop online', $html);
            $this->assertStringContainsString('href="'.$url.'">Shop your favourites', $html);
            $this->assertStringContainsString('checkout for store pickup', $html);
            $this->assertStringContainsString('Staff sign in', $html);
        }
        $this->store->update(['delivery_enabled' => true, 'delivery_fee' => 5]);
        $this->assertTrue(app(\Modules\Storefront\Services\PublicStoreLink::class)->data()['publicStoreDelivery']);
    }

    public function test_public_homepage_does_not_link_to_unavailable_or_uninstalled_stores(): void
    {
        config(['constants.public_catalog_business_id' => $this->store->business_id]);
        $service = app(\Modules\Storefront\Services\PublicStoreLink::class);
        $this->store->update(['enabled' => false]);
        $this->assertNull($service->data()['publicStoreUrl']);
        $html = view('welcome', ['pageLayout' => 'restaurant', 'pagePreset' => config('public_page_layouts.restaurant'), 'publicBusinessName' => 'Test shop'])->render();
        $this->assertStringNotContainsString('Shop your favourites', $html);
        $this->store->update(['enabled' => true]);
        $this->store->location->update(['is_active' => false]);
        $this->assertNull($service->data()['publicStoreUrl']);
        Schema::drop('storefront_settings');
        $this->assertNull($service->data()['publicStoreUrl']);
    }

    public function test_homepage_integration_supports_other_templates_and_does_not_duplicate_links(): void
    {
        config(['constants.public_catalog_business_id' => $this->store->business_id]);
        $middleware = new \Modules\Storefront\Http\Middleware\HostIntegration();
        $request = \Illuminate\Http\Request::create('/');
        $response = $middleware->handle($request, fn () => response('<html><head></head><body><h1>Original custom homepage</h1></body></html>'));
        $this->assertStringContainsString('Original custom homepage', $response->getContent());
        $this->assertStringContainsString('data-storefront-promo="floating"', $response->getContent());
        $this->assertStringContainsString('homepage.css', $response->getContent());
        $again = $middleware->handle($request, fn () => $response);
        $this->assertSame(1, substr_count($again->getContent(), 'data-storefront-promo="floating"'));
        $other = $middleware->handle(\Illuminate\Http\Request::create('/login'), fn () => response('<html><body>Login</body></html>'));
        $this->assertStringNotContainsString('data-storefront-promo', $other->getContent());
    }

    public function test_sales_integration_adds_badges_without_host_hooks_and_keeps_other_tenants_private(): void
    {
        $order = $this->order();
        $order->update(['transaction_id' => 321, 'status' => 'placed', 'payment_method' => 'cash']);
        $request = \Illuminate\Http\Request::create('/sells');
        $request->setLaravelSession(app('session.store'));
        $route = new \Illuminate\Routing\Route('GET', 'sells', ['uses' => '\\App\\Http\\Controllers\\SellController@index', 'controller' => '\\App\\Http\\Controllers\\SellController@index']);
        $request->setRouteResolver(fn () => $route);
        $payload = ['draw' => 2, 'recordsTotal' => 2, 'recordsFiltered' => 2,
            'data' => [['id' => 321, 'invoice_no' => 'INV-321'], ['id' => 999, 'invoice_no' => 'INV-999']]];
        $middleware = new \Modules\Storefront\Http\Middleware\HostIntegration();
        session(['business.id' => $order->business_id]);
        $response = $middleware->handle($request, fn () => response()->json($payload));
        $result = $response->getData(true);
        $this->assertSame(2, $result['draw']);
        $this->assertStringContainsString($order->tracking_code, $result['data'][0]['invoice_no']);
        $this->assertSame('INV-999', $result['data'][1]['invoice_no']);
        $again = $middleware->handle($request, fn () => $response)->getData(true);
        $this->assertSame(1, substr_count($again['data'][0]['invoice_no'], 'data-storefront-order'));
        session(['business.id' => 999]);
        $private = $middleware->handle($request, fn () => response()->json($payload))->getData(true);
        $this->assertSame('INV-321', $private['data'][0]['invoice_no']);
    }
}
