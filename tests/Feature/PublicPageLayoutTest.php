<?php

namespace Tests\Feature;

use App\Utils\PublicPageLayout;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class PublicPageLayoutTest extends TestCase
{
    public function test_public_layout_follows_pos_by_default_and_accepts_an_override(): void
    {
        $resolver = new PublicPageLayout;
        foreach (array_keys(config('pos_layouts')) as $layout) {
            $this->assertSame($layout, $resolver->resolve(['pos_layout' => $layout]));
            $this->assertSame($layout, $resolver->resolve(['pos_layout' => $layout, 'public_layout' => 'follow_pos']));
            $this->assertSame($layout, $resolver->resolve(['pos_layout' => 'restaurant', 'public_layout' => $layout]));
        }
        $this->assertSame('restaurant', $resolver->resolve([]));
        $this->assertSame('restaurant', $resolver->resolve(['public_layout' => 'unknown']));
        $this->assertSame('restaurant', $resolver->resolve(['pos_layout' => 'unknown']));
    }

    public function test_both_guest_pages_render_the_same_preset_and_keep_login_controls(): void
    {
        view()->share('asset_v', 'test');
        view()->share('errors', new ViewErrorBag);
        foreach (array_keys(config('pos_layouts')) as $layout) {
            $preset = config('public_page_layouts.'.$layout);
            $data = [
                'pageLayout' => $layout, 'pagePreset' => $preset,
                'publicBusinessName' => 'Example Business',
                'loginPhone' => '+233 24 123 4567', 'loginPhoneHref' => '+233241234567',
            ];
            $public = view('welcome', $data)->render();
            $login = view('auth.login', $data)->render();
            foreach ([$public, $login] as $html) {
                $this->assertStringContainsString('public-layout-'.$layout, $html);
                $this->assertStringContainsString('Example Business', $html);
            }
            $this->assertStringContainsString(e($preset['title']), $public);
            $this->assertStringContainsString(e($preset['login_title']), $login);
            $this->assertStringContainsString('tel:+233241234567', $login);
            foreach (['name="_token"', 'name="username"', 'name="password"', 'name="remember"', 'id="show_hide_icon"'] as $control) {
                $this->assertStringContainsString($control, $login);
            }
        }
    }

    public function test_all_business_layouts_have_matching_public_and_customer_display_artwork(): void
    {
        $this->assertSame(array_keys(config('pos_layouts')), array_keys(config('public_page_layouts')));
        foreach (config('pos_layouts') as $key => $preset) {
            $this->assertNotEmpty($preset['display']['order']);
            $this->assertFileExists(public_path('img/'.$preset['display']['image']));
            $this->assertFileExists(public_path('img/'.config('public_page_layouts.'.$key.'.image')));
        }
        $this->assertSame('All products', config('pos_layouts.general.all'));
        $this->assertSame('Your purchase', config('pos_layouts.general.display.order'));
    }
}
