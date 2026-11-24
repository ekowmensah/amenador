<?php

namespace Tests\Feature;

use App\Business;
use App\Product;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\Woocommerce\Http\Controllers\WoocommerceWebhookController;
use Modules\Woocommerce\Utils\ProductImportUtil;
use Modules\Woocommerce\Utils\WoocommerceUtil;
use Tests\TestCase;

class WoocommerceProductSyncTest extends TestCase
{
    private $woo;
    private $settings;
    private $business;
    private $imageDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'woo_test', 'database.connections.woo_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        $this->imageDirectory = 'woo_test_'.bin2hex(random_bytes(5));
        config(['constants.product_img_path' => $this->imageDirectory]);
        Schema::create('business', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('owner_id');
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id');
            $table->integer('woocommerce_product_id');
            $table->boolean('woocommerce_disable_sync')->default(false);
            $table->integer('woocommerce_media_id')->nullable();
            $table->integer('tax')->nullable();
            $table->string('name');
            $table->string('sku');
            $table->string('type')->default('single');
            $table->string('image')->nullable();
            $table->text('product_description')->nullable();
            $table->timestamps();
        });
        Schema::create('variations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id');
            $table->decimal('default_sell_price', 22, 4)->default(10);
            $table->decimal('sell_price_inc_tax', 22, 4)->default(10);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('amount', 10, 4);
            $table->softDeletes();
        });
        Schema::create('variation_group_prices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('variation_id');
            $table->integer('price_group_id');
            $table->decimal('price_inc_tax', 22, 4);
            $table->string('price_type');
            $table->timestamps();
        });
        $this->business = Business::create(['owner_id' => 1]);
        Product::create(['business_id' => $this->business->id, 'woocommerce_product_id' => 42, 'name' => 'Old', 'sku' => 'A']);
        DB::table('variations')->insert(['product_id' => 1, 'default_sell_price' => 10, 'sell_price_inc_tax' => 10]);
        $this->settings = (object) [
            'woocommerce_app_url' => 'https://shop.example',
            'product_webhook_secret' => 'test-secret',
            'product_fields_for_import' => ['name', 'description', 'image', 'price'],
            'product_tax_type' => 'inc',
        ];
        $this->woo = Mockery::mock(WoocommerceUtil::class);
        $this->woo->shouldReceive('get_api_settings')->andReturn($this->settings);
        $this->woo->shouldReceive('createSyncLog')->byDefault();
    }

    protected function tearDown(): void
    {
        $directory = public_path('uploads/'.$this->imageDirectory);
        foreach (glob($directory.'/*') ?: [] as $file) {
            unlink($file);
        }
        if (is_dir($directory)) {
            rmdir($directory);
        }
        DB::disconnect('woo_test');
        parent::tearDown();
    }

    private function remote(array $data): ProductImportUtil
    {
        $client = Mockery::mock();
        $client->shouldReceive('get')->with('products/42')->andReturn((object) $data);
        $this->woo->shouldReceive('woo_client')->andReturn($client);

        return new ProductImportUtil($this->woo);
    }

    public function test_imports_primary_image_details_and_price_and_repeated_delivery_is_unchanged(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        Http::fake(['https://shop.example/image.png' => function () use ($png) {
            return Http::response($png);
        }]);
        $service = $this->remote(['name' => 'From WordPress', 'description' => 'Updated', 'regular_price' => '25.50',
            'images' => [(object) ['id' => 90, 'src' => 'https://shop.example/image.png']]]);
        $this->assertTrue($service->sync($this->business, 42));
        $product = Product::first();
        $this->assertSame('From WordPress', $product->name);
        $this->assertSame('Updated', $product->product_description);
        $this->assertEquals(90, $product->woocommerce_media_id);
        $this->assertFileExists($product->image_path);
        $this->assertEquals(25.5, DB::table('variations')->value('sell_price_inc_tax'));
        $this->assertFalse($service->sync($this->business, 42));
    }

    public function test_image_removal_clears_local_image_and_media_id(): void
    {
        Product::first()->update(['image' => 'old.png', 'woocommerce_media_id' => 90]);
        $this->assertTrue($this->remote(['images' => []])->sync($this->business, 42));
        $this->assertNull(Product::first()->image);
        $this->assertNull(Product::first()->woocommerce_media_id);
    }

    public function test_does_not_import_disabled_unlinked_or_other_business_products(): void
    {
        $this->woo->shouldNotReceive('woo_client');
        $service = new ProductImportUtil($this->woo);
        $this->assertFalse($service->sync($this->business, 99));
        $other = Business::create(['owner_id' => 2]);
        $this->assertFalse($service->sync($other, 42));
        Product::first()->update(['woocommerce_disable_sync' => 1]);
        $this->assertFalse($service->sync($this->business, 42));
    }

    public function test_image_failure_rolls_back_product_changes(): void
    {
        Http::fake(['*' => Http::response('<html>Not an image</html>')]);
        $service = $this->remote(['name' => 'Must roll back', 'images' => [(object) ['id' => 3, 'src' => 'https://shop.example/bad.png']]]);
        try {
            $service->sync($this->business, 42);
            $this->fail('Invalid image was accepted');
        } catch (\RuntimeException $e) {
            $this->assertSame('Old', Product::first()->name);
        }
    }

    public function test_rejects_image_from_another_host_without_fetching_it(): void
    {
        Http::fake();
        $service = $this->remote(['images' => [(object) ['id' => 3, 'src' => 'https://other.example/image.png']]]);
        try {
            $service->sync($this->business, 42);
            $this->fail('External host was accepted');
        } catch (\RuntimeException $e) {
            Http::assertNothingSent();
        }
    }

    public function test_webhook_requires_valid_nonempty_secret_and_accepts_signed_activation_ping(): void
    {
        $controller = new WoocommerceWebhookController($this->woo, Mockery::mock(ModuleUtil::class), Mockery::mock(TransactionUtil::class), Mockery::mock(ProductUtil::class));
        $request = Request::create('/', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"id":42}');
        $this->assertSame(401, $controller->productUpdated($request, $this->business->id)->getStatusCode());
        $this->settings->product_webhook_secret = '';
        $request->headers->set('x-wc-webhook-signature', base64_encode(hash_hmac('sha256', $request->getContent(), '', true)));
        $this->assertSame(401, $controller->productUpdated($request, $this->business->id)->getStatusCode());
        $this->settings->product_webhook_secret = 'test-secret';
        $ping = Request::create('/', 'POST', ['webhook_id' => 1], [], [], [], 'webhook_id=1');
        $ping->headers->set('x-wc-webhook-signature', base64_encode(hash_hmac('sha256', $ping->getContent(), 'test-secret', true)));
        $this->assertSame(200, $controller->productUpdated($ping, $this->business->id)->getStatusCode());
    }

    public function test_outgoing_validator_accepts_webp(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'woo').'.webp';
        file_put_contents($file, 'fixture');
        try {
            $util = new WoocommerceUtil(Mockery::mock(TransactionUtil::class), Mockery::mock(ProductUtil::class));
            $this->assertTrue($util->isValidImage($file));
        } finally {
            unlink($file);
            unlink(substr($file, 0, -5));
        }
    }

    public function test_respects_selected_fields(): void
    {
        $this->settings->product_fields_for_import = ['description'];
        Http::fake();
        $service = $this->remote(['name' => 'Ignored', 'description' => 'Received', 'regular_price' => '50',
            'images' => [(object) ['id' => 3, 'src' => 'https://other.example/image.png']]]);
        $this->assertTrue($service->sync($this->business, 42));
        $this->assertSame('Old', Product::first()->name);
        $this->assertSame('Received', Product::first()->product_description);
        $this->assertEquals(10, DB::table('variations')->value('sell_price_inc_tax'));
        Http::assertNothingSent();
    }

    public function test_converts_tax_exclusive_price_into_selected_price_group(): void
    {
        DB::table('tax_rates')->insert(['id' => 1, 'amount' => 20]);
        Product::first()->update(['tax' => 1]);
        $this->settings->product_tax_type = 'exc';
        $this->settings->default_selling_price_group = 7;
        $service = $this->remote(['regular_price' => '25']);
        $this->assertTrue($service->sync($this->business, 42));
        $this->assertEquals(30, DB::table('variation_group_prices')->value('price_inc_tax'));
        $this->assertEquals(7, DB::table('variation_group_prices')->value('price_group_id'));
        $this->assertEquals(10, DB::table('variations')->value('sell_price_inc_tax'));
        $this->assertFalse($service->sync($this->business, 42));
    }

    public function test_signed_product_webhook_dispatches_import_and_route_is_registered(): void
    {
        $route = app('router')->getRoutes()->match(Request::create('/webhook/product-updated/1', 'POST'));
        $this->assertSame(WoocommerceWebhookController::class.'@productUpdated', $route->getActionName());
        $import = Mockery::mock(ProductImportUtil::class);
        $import->shouldReceive('sync')->once()->with(Mockery::type(Business::class), 42)->andReturn(true);
        $this->app->instance(ProductImportUtil::class, $import);
        $controller = new WoocommerceWebhookController($this->woo, Mockery::mock(ModuleUtil::class), Mockery::mock(TransactionUtil::class), Mockery::mock(ProductUtil::class));
        $request = Request::create('/', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"id":42}');
        $request->headers->set('x-wc-webhook-signature', base64_encode(hash_hmac('sha256', $request->getContent(), 'test-secret', true)));
        $response = $controller->productUpdated($request, $this->business->id);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['updated' => true], $response->getData(true));
    }

    public function test_manual_sync_receives_newer_wordpress_changes_without_a_webhook(): void
    {
        Product::first()->update(['updated_at' => '2020-01-01 00:00:00']);
        $service = $this->remote(['name' => 'Manual pull', 'date_modified_gmt' => '2025-01-01T00:00:00']);
        $this->assertSame(1, $service->receiveNewerProducts($this->business, Product::all()));
        $this->assertSame('Manual pull', Product::first()->name);
        $this->assertSame(0, $service->receiveNewerProducts($this->business, Product::all()));
    }

    public function test_manual_sync_preserves_a_newer_local_edit(): void
    {
        Product::first()->update(['updated_at' => '2026-01-01 00:00:00']);
        $service = $this->remote(['name' => 'Older WordPress edit', 'date_modified_gmt' => '2025-01-01T00:00:00']);
        $this->assertSame(0, $service->receiveNewerProducts($this->business, Product::all()));
        $this->assertSame('Old', Product::first()->name);
    }

    public function test_wordpress_placeholder_does_not_get_imported_as_a_real_product_image(): void
    {
        Http::fake();
        $service = $this->remote(['images' => [(object) ['id' => 0, 'src' => 'https://shop.example/placeholder.webp']]]);
        $this->assertFalse($service->sync($this->business, 42));
        Http::assertNothingSent();
    }

    public function test_outgoing_products_without_uploaded_photos_send_the_displayed_default_image(): void
    {
        $util = new WoocommerceUtil(Mockery::mock(TransactionUtil::class), Mockery::mock(ProductUtil::class));
        $product = Product::first();
        $this->assertSame([['src' => $product->image_url]], $util->productImages($product));
        $product->woocommerce_media_id = 123;
        $this->assertSame([['id' => 123]], $util->productImages($product));
    }

    public function test_woocommerce_batch_image_errors_are_reported(): void
    {
        $client = Mockery::mock();
        $client->shouldReceive('post')->once()->andReturn((object) ['update' => [(object) [
            'id' => 0, 'error' => (object) ['message' => 'Image download failed'],
        ]]]);
        $util = Mockery::mock(WoocommerceUtil::class, [Mockery::mock(TransactionUtil::class), Mockery::mock(ProductUtil::class)])->makePartial();
        $util->shouldReceive('woo_client')->andReturn($client);
        $this->expectException(\Modules\Woocommerce\Exceptions\WooCommerceError::class);
        $this->expectExceptionMessage('Image download failed');
        $util->syncProd(1, [['id' => 42]], 'update', [Product::first()]);
    }
}
