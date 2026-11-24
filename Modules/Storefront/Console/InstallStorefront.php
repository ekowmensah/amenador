<?php

namespace Modules\Storefront\Console;

use App\Business;
use App\BusinessLocation;
use App\System;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Modules\Storefront\Entities\StoreSetting;

class InstallStorefront extends Command
{
    protected $signature = 'storefront:install {--business= : Business ID} {--enable : Enable this business storefront}';
    protected $description = 'Install or update the Storefront module without changing existing orders or products';

    public function handle()
    {
        $business = Business::find($this->option('business') ?: config('storefront.default_business_id'));
        if (! $business) { $this->error('Business not found.'); return 1; }
        $location = BusinessLocation::where('business_id', $business->id)->where('is_active', 1)->orderBy('id')->first();
        if (! $location) { $this->error('Create an active business location first.'); return 1; }
        $status = Artisan::call('migrate', ['--path' => 'Modules/Storefront/Database/Migrations', '--force' => true]);
        if ($status !== 0) { $this->error(Artisan::output()); return $status; }
        $slug = Str::slug($business->name) ?: 'store-'.$business->id;
        if (StoreSetting::where('slug', $slug)->where('business_id', '!=', $business->id)->exists()) { $slug .= '-'.$business->id; }
        $store = StoreSetting::firstOrCreate(['business_id' => $business->id], [
            'location_id' => $location->id, 'slug' => $slug, 'name' => $business->name, 'enabled' => false,
        ]);
        if ($this->option('enable')) { $store->update(['enabled' => true]); }
        System::updateOrCreate(['key' => 'storefront_version'], ['value' => config('storefront.module_version')]);
        $this->info('Storefront installed. Settings: '.url('/storefront/settings'));
        $this->line('Store: '.route('storefront.index', $store));
        return 0;
    }
}
