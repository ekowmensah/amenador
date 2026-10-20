<?php

namespace Tests\Feature;

use App\Http\Controllers\CustomerDisplayCompanionController;
use App\Services\CustomerDisplayCompanion;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerDisplayCompanionTest extends TestCase
{
    private $companion;
    private $register;

    protected function setUp(): void
    {
        parent::setUp();
        app('url')->forceRootUrl('http://localhost');
        // Deliberately isolated from the application's live business database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->integer('business_id'); $table->softDeletes(); });
        Schema::create('business', function (Blueprint $table) { $table->id(); $table->string('name'); $table->text('common_settings')->nullable(); });
        (require database_path('migrations/2026_10_20_120000_create_customer_display_devices_table.php'))->up();
        DB::table('users')->insert([['id' => 1, 'business_id' => 1], ['id' => 2, 'business_id' => 2]]);
        DB::table('business')->insert([['id' => 1, 'name' => 'First business', 'common_settings' => '{"pos_layout":"hardware"}'],
            ['id' => 2, 'name' => 'Second business', 'common_settings' => '{}']]);
        Gate::swap(new \Illuminate\Auth\Access\Gate($this->app, function () { return auth()->user(); }));
        Gate::before(function ($user) { return $user->id === 1; });
        Route::middleware(['web', 'auth'])->prefix('_display-test')->group(function () {
            foreach (['code', 'publish', 'status', 'revoke'] as $action) {
                Route::post($action, [CustomerDisplayCompanionController::class, $action]);
            }
        });
        $this->companion = app(CustomerDisplayCompanion::class);
        $this->register = str_repeat('a', 64);
    }

    private function login(): void
    {
        $this->actingAs(User::findOrFail(1))->withSession(['user' => ['business_id' => 1]]);
    }

    private function order(): array
    {
        return ['items' => [['name' => '<b>Item</b>', 'quantity' => '1.25', 'amount' => 'GH₵ 12.50']],
            'location' => 'Bar', 'subtotal' => 'GH₵ 12.50', 'total' => 'GH₵ 12.50', 'discount' => '0', 'tax' => '0',
            'adjustments' => '0', 'has_discount' => false, 'has_tax' => false, 'has_adjustments' => false];
    }

    public function test_pairing_is_expiring_single_use_and_only_hashes_credentials(): void
    {
        $this->login();
        $code = $this->postJson('/_display-test/code', ['register' => $this->register])->assertOk()->json('code');
        $pair = $this->postJson('/api/customer-display-companion/pair', ['code' => $code, 'name' => 'Front screen'])->assertOk()->json();
        $device = DB::table('customer_display_devices')->first();
        $this->assertSame(hash('sha256', $pair['token']), $device->token_hash);
        $this->assertNotSame($pair['token'], $device->token_hash);
        $this->postJson('/api/customer-display-companion/pair', ['code' => $code, 'name' => 'Replay'])->assertStatus(422);
        $expired = $this->companion->code(1, 1, $this->register);
        $this->travel(6)->minutes();
        $this->assertNull($this->companion->pair($expired, 'Expired'));
    }

    public function test_device_state_is_register_scoped_and_becomes_inactive_after_heartbeat_timeout(): void
    {
        $pair = $this->companion->pair($this->companion->code(1, 1, $this->register), 'Screen');
        $device = $this->companion->authenticate($pair['token']);
        $this->companion->publish(2, 2, $this->register, $this->order(), true);
        $this->companion->publish(1, 1, str_repeat('b', 64), $this->order(), true);
        $this->assertFalse($this->companion->state($device)['connected']);
        $this->companion->publish(1, 1, $this->register, $this->order(), true);
        $this->getJson('/api/customer-display-companion/state', ['Authorization' => 'Bearer '.$pair['token']])
            ->assertOk()->assertJsonPath('active', true)->assertJsonPath('order.items.0.quantity', '1.25');
        $this->travel(16)->seconds();
        $this->getJson('/api/customer-display-companion/state', ['Authorization' => 'Bearer '.$pair['token']])
            ->assertOk()->assertJsonPath('active', false)->assertJsonPath('order', null);
    }

    public function test_unauthenticated_and_revoked_devices_cannot_read_the_screen_or_order(): void
    {
        $this->getJson('/api/customer-display-companion/state')->assertUnauthorized();
        $this->getJson('/api/customer-display-companion/screen')->assertUnauthorized();
        $pair = $this->companion->pair($this->companion->code(1, 1, $this->register), 'Screen');
        $this->get('/api/customer-display-companion/screen', ['Authorization' => 'Bearer '.$pair['token']])
            ->assertOk()->assertSee('display-layout-hardware')->assertSee('First business');
        $this->login();
        $this->postJson('/_display-test/revoke', ['register' => str_repeat('b', 64), 'device_id' => $pair['device_id']])->assertNoContent();
        $this->assertNotNull($this->companion->authenticate($pair['token']));
        $this->postJson('/_display-test/revoke', ['register' => $this->register, 'device_id' => $pair['device_id']])->assertNoContent();
        $this->getJson('/api/customer-display-companion/state', ['Authorization' => 'Bearer '.$pair['token']])->assertUnauthorized();
    }

    public function test_pos_validates_orders_and_never_exposes_device_tokens_in_its_list(): void
    {
        $this->login();
        $pair = $this->companion->pair($this->companion->code(1, 1, $this->register), 'Screen');
        $this->postJson('/_display-test/publish', ['register' => $this->register, 'owner' => str_repeat('c', 64), 'active' => true, 'order' => $this->order()])->assertNoContent();
        $this->postJson('/_display-test/publish', ['register' => $this->register, 'owner' => str_repeat('d', 64), 'active' => true, 'order' => $this->order()])->assertStatus(409);
        $this->postJson('/_display-test/status', ['register' => $this->register])->assertOk()->assertDontSee($pair['token'])->assertDontSee('token_hash');
        $this->postJson('/_display-test/status', ['register' => str_repeat('b', 64)])->assertJsonCount(0, 'devices');
        $bad = $this->order(); $bad['items'][0]['name'] = str_repeat('x', 501);
        $this->postJson('/_display-test/publish', ['register' => $this->register, 'owner' => str_repeat('c', 64), 'active' => true, 'order' => $bad])->assertUnprocessable();
        $this->withSession(['user' => ['business_id' => 2]])->postJson('/_display-test/status', ['register' => $this->register])->assertForbidden();
    }

    public function test_deleted_cashier_invalidates_device_access(): void
    {
        $pair = $this->companion->pair($this->companion->code(1, 1, $this->register), 'Screen');
        DB::table('users')->where('id', 1)->delete();
        $this->getJson('/api/customer-display-companion/state', ['Authorization' => 'Bearer '.$pair['token']])->assertUnauthorized();
    }
}
