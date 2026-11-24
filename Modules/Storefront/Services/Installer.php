<?php

namespace Modules\Storefront\Services;

use App\Business;
use App\BusinessLocation;
use App\System;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Str;
use Modules\Storefront\Entities\StoreSetting;

class Installer
{
    public function install(int $businessId, bool $enable = false): StoreSetting
    {
        $business = Business::find($businessId);
        if (! $business) { throw new \RuntimeException('Business not found.'); }
        $location = BusinessLocation::where('business_id', $business->id)->where('is_active', 1)->orderBy('id')->first();
        if (! $location) { throw new \RuntimeException('Create an active business location first.'); }

        // Run only module migrations, without constructing a console or probing a terminal.
        $repository = app('migration.repository');
        if (! $repository->repositoryExists()) { $repository->createRepository(); }
        $migrator = new Migrator($repository, app('db'), app('files'), app('events'));
        $migrator->run([__DIR__.'/../Database/Migrations']);

        $slug = Str::slug($business->name) ?: 'store-'.$business->id;
        if (StoreSetting::where('slug', $slug)->where('business_id', '!=', $business->id)->exists()) { $slug .= '-'.$business->id; }
        $store = StoreSetting::firstOrCreate(['business_id' => $business->id], [
            'location_id' => $location->id, 'slug' => $slug, 'name' => $business->name, 'enabled' => false,
        ]);
        if ($enable) { $store->update(['enabled' => true]); }
        System::updateOrCreate(['key' => 'storefront_version'], ['value' => config('storefront.module_version')]);
        return $store;
    }
}
