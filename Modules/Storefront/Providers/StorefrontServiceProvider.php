<?php

namespace Modules\Storefront\Providers;

use Illuminate\Support\ServiceProvider;

class StorefrontServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'storefront');
        $this->mergeConfigFrom(__DIR__.'/../Config/public_presets.php', 'storefront_presets');
        $this->commands([\Modules\Storefront\Console\InstallStorefront::class]);
    }

    public function boot()
    {
        \App\Transaction::updated(function ($sale) {
            app(\Modules\Storefront\Services\SaleSync::class)->fromSale($sale);
        });
        \Modules\Storefront\Entities\StoreOrder::updated(function ($order) {
            app(\Modules\Storefront\Services\SaleSync::class)->fromOrder($order);
        });
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'storefront');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        view()->composer('welcome', function ($view) {
            $view->with(app(\Modules\Storefront\Services\PublicStoreLink::class)->data());
        });
        view()->composer('storefront::*', function ($view) {
            $store = $view->getData()['store'] ?? null;
            if ($store instanceof \Modules\Storefront\Entities\StoreSetting) {
                $view->with(app(\Modules\Storefront\Services\PublicAppearance::class)->data($store));
            }
        });
    }
}
