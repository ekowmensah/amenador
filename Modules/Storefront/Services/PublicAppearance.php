<?php

namespace Modules\Storefront\Services;

use Modules\Storefront\Entities\StoreSetting;

class PublicAppearance
{
    public function data(StoreSetting $store): array
    {
        // Resolve the storefront's own business, never a staff session or the default website tenant.
        $settings = $store->business->common_settings ?? [];
        $presets = config('public_page_layouts') ?: config('storefront_presets');
        if (class_exists(\App\Utils\PublicPageLayout::class) && config('public_page_layouts')) {
            $layout = app(\App\Utils\PublicPageLayout::class)->resolve($settings);
        } else {
            $layout = $settings['public_layout'] ?? 'follow_pos';
            if ($layout === 'follow_pos') { $layout = $settings['pos_layout'] ?? 'restaurant'; }
        }
        if (! is_string($layout) || ! isset($presets[$layout])) { $layout = 'restaurant'; }
        $preset = $presets[$layout];
        $image = basename($preset['image']);
        return [
            'pageLayout' => $layout, 'pagePreset' => $preset,
            'storefrontArt' => is_file(public_path('img/'.$image)) ? asset('img/'.$image) : route('storefront.asset', $image),
            'storefrontPresetCss' => is_file(public_path('css/public-page-layouts.css'))
                ? asset('css/public-page-layouts.css?v='.filemtime(public_path('css/public-page-layouts.css')))
                : route('storefront.asset', 'public-presets.css').'?v='.config('storefront.module_version'),
        ];
    }
}
