<?php

namespace Modules\Storefront\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Storefront\Entities\StoreSetting;

class PublicStoreLink
{
    public function data(): array
    {
        $empty = ['publicStoreUrl' => null, 'publicStoreDelivery' => false];
        if (! Route::has('storefront.index') || ! Schema::hasTable('storefront_settings')) { return $empty; }
        // Match the business displayed on the public homepage, independently of staff sessions.
        $businessId = (int) config('constants.public_catalog_business_id', 1);
        $store = StoreSetting::with(['business', 'location'])->where('business_id', $businessId)->where('enabled', true)->first();
        if (! $store || ! $store->business || ! $store->location || ! $store->location->is_active
            || (int) $store->location->business_id !== $businessId) { return $empty; }
        return ['publicStoreUrl' => route('storefront.index', $store), 'publicStoreDelivery' => $store->deliveryAvailable()];
    }
}
