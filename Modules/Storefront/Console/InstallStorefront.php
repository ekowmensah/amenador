<?php

namespace Modules\Storefront\Console;

use Illuminate\Console\Command;
use Modules\Storefront\Services\Installer;

class InstallStorefront extends Command
{
    protected $signature = 'storefront:install {--business= : Business ID} {--enable : Enable this business storefront}';
    protected $description = 'Install or update the Storefront module without changing existing orders or products';

    public function handle()
    {
        try {
            $store = app(Installer::class)->install((int) ($this->option('business') ?: config('storefront.default_business_id')), (bool) $this->option('enable'));
        } catch (\Throwable $error) {
            $this->error($error->getMessage());
            return 1;
        }
        $this->info('Storefront installed. Settings: '.url('/storefront/settings'));
        $this->line('Store: '.route('storefront.index', $store));
        return 0;
    }
}
