<?php

namespace App\Utils;

use App\Business;
use App\BusinessLocation;

class PublicPageLayout
{
    public function resolve(array $settings): string
    {
        $layout = $settings['public_layout'] ?? 'follow_pos';
        if ($layout === 'follow_pos') {
            $layout = $settings['pos_layout'] ?? 'restaurant';
        }

        return is_string($layout) && array_key_exists($layout, config('public_page_layouts')) ? $layout : 'restaurant';
    }

    public function data(): array
    {
        // Guest pages always use the website's configured business, never a
        // previous login session or a visitor-controlled business identifier.
        $businessId = (int) config('constants.public_catalog_business_id', 1);
        $business = Business::find($businessId);
        $settings = $business->common_settings ?? [];
        $pageLayout = $this->resolve($settings);
        $loginPhone = trim((string) ($settings['login_phone'] ?? ''));
        if ($loginPhone === '' && $business) {
            $location = BusinessLocation::where('business_id', $businessId)
                ->where('is_active', 1)->whereNull('deleted_at')->orderBy('id')->first();
            $loginPhone = trim((string) ($location->mobile ?? ''));
            if ($loginPhone === '') {
                $loginPhone = trim((string) ($location->alternate_number ?? ''));
            }
        }

        return [
            'pageLayout' => $pageLayout,
            'pagePreset' => config('public_page_layouts.'.$pageLayout),
            'publicBusinessName' => $business->name ?? config('app.name'),
            'loginPhone' => $loginPhone,
            'loginPhoneHref' => preg_replace('/[^0-9+]/', '', $loginPhone),
        ];
    }
}
