<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Http\Controllers\StoreController;
use Modules\Storefront\Http\Controllers\PaymentController;
use Modules\Storefront\Http\Controllers\AdminController;
use Modules\Storefront\Http\Controllers\InstallController;
use Modules\Storefront\Http\Controllers\TrackingController;

Route::middleware('web')->group(function () {
    Route::get('/storefront-assets/{file}', [StoreController::class, 'asset'])->name('storefront.asset');
    Route::get('/shop', [StoreController::class, 'home'])->name('storefront.home');
    Route::get('/shop/{store}', [StoreController::class, 'index'])->middleware('throttle:120,1')->name('storefront.index');
    Route::get('/shop/{store}/track', [TrackingController::class, 'index'])->name('storefront.track');
    Route::post('/shop/{store}/track', [TrackingController::class, 'lookup'])->middleware('throttle:10,1')->name('storefront.track.lookup');
    Route::get('/shop/{store}/track/status', [TrackingController::class, 'status'])->middleware('throttle:30,1')->name('storefront.track.status');
    Route::post('/shop/{store}/cart', [StoreController::class, 'cart'])->middleware('throttle:120,1')->name('storefront.cart');
    Route::get('/shop/{store}/checkout', [StoreController::class, 'checkout'])->name('storefront.checkout');
    Route::post('/shop/{store}/checkout', [StoreController::class, 'place'])->middleware('throttle:10,1')->name('storefront.place');
    Route::get('/shop/{store}/orders/{token}', [StoreController::class, 'order'])->middleware('throttle:60,1')->name('storefront.order');
    Route::post('/shop/{store}/orders/{token}/pay', [PaymentController::class, 'start'])->middleware('throttle:10,1')->name('storefront.pay');
    Route::post('/shop/{store}/orders/{token}/verify', [PaymentController::class, 'verify'])->middleware('throttle:10,1')->name('storefront.verify');
    Route::get('/storefront/paystack/callback', [PaymentController::class, 'callback'])->middleware('throttle:30,1')->name('storefront.paystack.callback');
    Route::post('/storefront/paystack/webhook', [PaymentController::class, 'webhook'])
        ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)->middleware('throttle:120,1')->name('storefront.paystack.webhook');
});

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'])->prefix('storefront')->group(function () {
    Route::get('/install', [InstallController::class, 'index'])->name('storefront.install');
    Route::post('/install', [InstallController::class, 'install'])->name('storefront.install.run');
    Route::get('/install/update', [InstallController::class, 'update']);
    Route::get('/install/uninstall', [InstallController::class, 'uninstall']);
    Route::post('/install/uninstall', [InstallController::class, 'runUninstall'])->name('storefront.uninstall.run');
    Route::get('/settings', [AdminController::class, 'settings'])->name('storefront.settings');
    Route::post('/settings', [AdminController::class, 'save'])->name('storefront.settings.save');
    Route::get('/orders', [AdminController::class, 'orders'])->name('storefront.orders');
    Route::post('/orders/{id}', [AdminController::class, 'updateOrder'])->name('storefront.orders.update');
});
