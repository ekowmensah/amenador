<?php

namespace Tests\Feature;

use App\Http\Controllers\Install\ModulesController;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class ModuleInstallationTest extends TestCase
{
    public function test_disabled_module_installation_form_renders_with_the_resource_route_parameter(): void
    {
        $template = str_replace("@extends('layouts.app')", '', file_get_contents(resource_path('views/install/modules/index.blade.php')));
        $html = \Illuminate\Support\Facades\Blade::render($template."\n@yield('content')", [
            'modules' => ['Storefront' => ['name' => 'Storefront', 'description' => 'Customer shop',
                'is_installed' => false, 'is_enabled' => false, 'install_link' => '#']],
            'is_demo' => false, 'mods' => serialize([]),
        ]);
        $this->assertStringContainsString('Enable for installation', $html);
        $this->assertStringContainsString('action="'.url('/manage-modules/Storefront').'"', $html);
        $this->assertStringContainsString('name="_method" value="PUT"', $html);
        $this->assertStringNotContainsString('module_name=', $html);
    }

    public function test_enabling_uploaded_module_clears_cached_routes_and_returns_to_install_list(): void
    {
        $user = Mockery::mock(\App\User::class)->makePartial();
        $user->shouldReceive('can')->with('manage_modules')->andReturn(true);
        auth()->setUser($user);
        $util = Mockery::mock(ModuleUtil::class);
        $util->shouldReceive('notAllowedInDemo')->once()->andReturn(null);
        $module = Mockery::mock();
        $module->shouldReceive('enable')->once();
        \Module::shouldReceive('find')->with('Storefront')->once()->andReturn($module);
        $files = Mockery::mock(\Illuminate\Filesystem\Filesystem::class)->makePartial();
        $files->shouldReceive('delete')->with(app()->getCachedRoutesPath())->once()->andReturn(true);
        app()->instance('files', $files);
        $request = Request::create('/manage-modules/Storefront', 'PUT', ['action_type' => 'activate', 'install_after_activate' => 1]);
        $response = (new ModulesController($util))->update($request, 'Storefront');
        $this->assertSame(action([ModulesController::class, 'index']), $response->getTargetUrl());
        $this->assertSame(1, (int) session('status.success'));
        $this->assertStringContainsString('Click Install', session('status.msg'));
    }

    public function test_unauthorized_user_cannot_enable_modules(): void
    {
        $user = Mockery::mock(\App\User::class)->makePartial();
        $user->shouldReceive('can')->with('manage_modules')->andReturn(false);
        auth()->setUser($user);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new ModulesController(app(ModuleUtil::class)))->update(Request::create('/manage-modules/Storefront', 'PUT', ['action_type' => 'activate']), 'Storefront');
    }
}
