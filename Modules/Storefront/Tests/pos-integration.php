<?php

// Exercises the real POS sale/payment/stock code and rolls every test record back.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
app('request')->setLaravelSession(app('session.store'));
$app->register(\Modules\Storefront\Providers\StorefrontServiceProvider::class);
if (! \Illuminate\Support\Facades\Route::has('storefront.index')) {
    require __DIR__.'/../Routes/web.php';
    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
}

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Storefront\Entities\StoreSetting;
use Modules\Storefront\Services\Catalog;
use Modules\Storefront\Services\Checkout;

$store = StoreSetting::where('business_id', config('storefront.default_business_id'))->firstOrFail();
$catalog = app(Catalog::class);
$products = $catalog->products($store);
$product = $products->first(function ($item) use ($store) {
    return $item['available'] && App\VariationLocationDetails::where('variation_id', $item['id'])->where('location_id', $store->location_id)->value('qty_available') >= 3;
});
if (! $product) { throw new RuntimeException('This test needs one stocked item with at least three available units.'); }
$input = ['name' => 'Storefront rollback test', 'email' => 'storefront-test@example.invalid', 'phone' => '0240000000', 'fulfilment' => 'pickup'];
$stockQuery = App\VariationLocationDetails::where('variation_id', $product['id'])->where('location_id', $store->location_id);
$before = (float) $stockQuery->value('qty_available');
$ordersBefore = Modules\Storefront\Entities\StoreOrder::count();
$failed = false;
DB::beginTransaction();
try {
    $cash = app(Checkout::class)->place($store, [$product['id'] => 1], $input + ['payment_method' => 'cash', 'checkout_key' => (string) Str::uuid()]);
    $sale = App\Transaction::findOrFail($cash->transaction_id);
    if ($cash->status !== 'placed' || $sale->status !== 'final' || $sale->payment_status !== 'due' || $sale->sell_lines()->count() !== 1) {
        throw new RuntimeException('Cash checkout did not create a final, unpaid POS sale.');
    }
    config(['storefront.paystack_secret' => 'sk_test_fixture']);
    $store->enable_paystack = true;
    $online = app(Checkout::class)->place($store, [$product['id'] => 1], $input + ['payment_method' => 'paystack', 'checkout_key' => (string) Str::uuid()]);
    Http::fake(['*' => Http::response(['status' => true, 'data' => ['id' => 'storefront-integration-test', 'reference' => $online->reference, 'status' => 'success', 'amount' => (int) $online->total_minor, 'currency' => $online->currency, 'customer' => ['email' => $input['email']], 'domain' => 'test']])]);
    $online = app(Checkout::class)->confirm($online);
    $sale = App\Transaction::find($online->transaction_id);
    if ($online->status !== 'paid' || ! $sale || $sale->payment_status !== 'paid' || $sale->payment_lines()->count() !== 1) {
        throw new RuntimeException('Verified online payment did not create a paid POS sale.');
    }
    app(Checkout::class)->confirm($online);
    if ($sale->payment_lines()->count() !== 1) { throw new RuntimeException('Duplicate confirmation created another payment.'); }
    $store->delivery_enabled = true;
    $store->delivery_fee = 15;
    $deliveryInput = array_merge($input, ['fulfilment' => 'delivery', 'address' => 'Test street', 'city' => 'Test city', 'payment_method' => 'cash', 'checkout_key' => (string) Str::uuid()]);
    $delivery = app(Checkout::class)->place($store, [$product['id'] => 1], $deliveryInput);
    if ((int) $delivery->delivery_minor !== 1500 || abs((float) App\Transaction::findOrFail($delivery->transaction_id)->shipping_charges - 15) > 0.001) {
        throw new RuntimeException('Delivery charge was not included in the POS sale.');
    }
    $deliverySale = App\Transaction::findOrFail($delivery->transaction_id);
    $deliverySale->update(['shipping_status' => 'shipped']);
    if ($delivery->fresh()->fulfilment_status !== 'out_for_delivery') { throw new RuntimeException('Sales shipping update did not reach tracking.'); }
    $delivery->refresh()->update(['fulfilment_status' => 'completed']);
    if ($deliverySale->fresh()->shipping_status !== 'delivered' || $deliverySale->fresh()->payment_status !== 'due') {
        throw new RuntimeException('Order progress did not update shipping independently of payment.');
    }
    $util = new Modules\Storefront\Services\GuestTransactionUtil();
    $util->storeBusinessId = $store->business_id;
    $util->createOrUpdatePaymentLines($deliverySale, [['amount' => $delivery->total_minor / 100, 'method' => 'cash', 'paid_on' => now()->toDateTimeString()]], $store->business_id, $store->business->owner_id, false);
    $util->updatePaymentStatus($deliverySale->id);
    if ($delivery->fresh()->status !== 'paid') { throw new RuntimeException('POS cash collection did not update storefront payment.'); }
    echo "PASS: sales shipping, order shipping, and real cash payment collection synchronization.\n";
    if (abs((float) $stockQuery->value('qty_available') - ($before - 3)) > 0.001) { throw new RuntimeException('Stock was not deducted exactly once per order.'); }
    echo "PASS: real POS cash sale, verified online sale, payment line, duplicate confirmation, delivery fee, stock deduction.\n";
} catch (Throwable $error) {
    $failed = true;
    fwrite(STDERR, get_class($error).': '.$error->getMessage().PHP_EOL);
} finally {
    DB::rollBack();
}
if ((float) $stockQuery->value('qty_available') !== $before || Modules\Storefront\Entities\StoreOrder::count() !== $ordersBefore) {
    throw new RuntimeException('Rollback verification failed.');
}
echo "Rollback verified: no test orders or stock changes retained.\n";
exit($failed ? 1 : 0);
