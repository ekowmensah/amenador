<?php

namespace Tests\Feature;

use App\Modules\StorageActivator;
use App\Services\ModuleArchiveInstaller;
use Mockery;
use Tests\TestCase;
use ZipArchive;

class ModulePackageTest extends TestCase
{
    private $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = storage_path('framework/testing/module-package-'.bin2hex(random_bytes(8)));
        app('files')->ensureDirectoryExists($this->directory);
    }

    protected function tearDown(): void
    {
        $resolved = realpath($this->directory);
        $parent = realpath(storage_path('framework/testing'));
        if ($resolved && $parent && str_starts_with($resolved, $parent.DIRECTORY_SEPARATOR.'module-package-')) {
            app('files')->deleteDirectory($resolved);
        }
        parent::tearDown();
    }

    private function zip(array $entries): string
    {
        $path = $this->directory.'/upload.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        foreach ($entries as $name => $contents) { $zip->addFromString($name, $contents); }
        $zip->close();
        return $path;
    }

    public function test_zip_upload_extracts_and_enables_module_without_console_or_host_patches(): void
    {
        config(['modules.paths.modules' => $this->directory.'/Modules']);
        $archive = $this->zip(['FixtureModule/module.json' => json_encode(['name' => 'FixtureModule']), 'FixtureModule/Config/config.php' => '<?php return [];']);
        $module = Mockery::mock();
        $module->shouldReceive('enable')->once();
        $repository = Mockery::mock();
        $repository->shouldReceive('scan')->once()->andReturn(['FixtureModule' => $module]);
        app()->instance('modules', $repository);
        $files = Mockery::mock(\Illuminate\Filesystem\Filesystem::class)->makePartial();
        $files->shouldReceive('delete')->with(app()->getCachedRoutesPath())->once()->andReturn(true);
        app()->instance('files', $files);
        $this->assertSame('FixtureModule', app(ModuleArchiveInstaller::class)->install($archive));
        $this->assertFileExists($this->directory.'/Modules/FixtureModule/module.json');
        $this->assertFileDoesNotExist($this->directory.'/Modules/app/Http/Kernel.php');
    }

    public function test_zip_cannot_write_outside_module_directory(): void
    {
        config(['modules.paths.modules' => $this->directory.'/Modules']);
        $archive = $this->zip(['FixtureModule/module.json' => '{"name":"FixtureModule"}', 'FixtureModule/../../escaped.php' => 'invalid']);
        try {
            app(ModuleArchiveInstaller::class)->install($archive);
            $this->fail('Unsafe ZIP accepted.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('no paths outside', $error->getMessage());
        }
        $this->assertFileDoesNotExist($this->directory.'/escaped.php');
        $this->assertDirectoryDoesNotExist($this->directory.'/Modules');
    }

    public function test_activation_moves_to_storage_and_preserves_existing_states_without_modifying_git_seed(): void
    {
        $seed = $this->directory.'/modules_statuses.json';
        $runtime = $this->directory.'/runtime/modules_statuses.json';
        $contents = json_encode(['ExistingModule' => true, 'Storefront' => false]);
        file_put_contents($seed, $contents);
        config(['modules.activators.file.statuses-file' => $seed, 'modules.runtime_statuses_file' => $runtime, 'modules.cache.enabled' => false]);
        $activator = new StorageActivator($this->app);
        $activator->setActiveByName('Storefront', true);
        $this->assertSame($contents, file_get_contents($seed));
        $this->assertSame(['ExistingModule' => true, 'Storefront' => true], json_decode(file_get_contents($runtime), true));
        // A future deployment's seed must not undo installed runtime settings.
        file_put_contents($seed, '{}');
        config(['modules.activators.file.statuses-file' => $seed]);
        $again = new StorageActivator($this->app);
        $module = Mockery::mock(\Nwidart\Modules\Module::class);
        $module->shouldReceive('getName')->andReturn('Storefront');
        $this->assertTrue($again->hasStatus($module, true));
        $this->assertSame($runtime, $again->getStatusesFilePath());
    }

    public function test_cached_legacy_module_configuration_is_upgraded_before_modules_boot(): void
    {
        config(['modules.activator' => 'file', 'modules.activators.file.class' => \Nwidart\Modules\Activators\FileActivator::class]);
        (new \App\Providers\AppServiceProvider($this->app))->register();
        $this->assertSame(StorageActivator::class, config('modules.activators.file.class'));
    }

    public function test_built_storefront_zip_installs_into_a_clean_module_directory_and_preserves_git_seed(): void
    {
        ob_start();
        require base_path('Modules/Storefront/build.php');
        ob_end_clean();
        $manifest = json_decode(file_get_contents(base_path('Modules/Storefront/module.json')), true);
        $archive = storage_path('app/module-packages/Storefront-'.$manifest['version'].'.zip');
        $seed = $this->directory.'/seed.json';
        $runtime = $this->directory.'/runtime.json';
        file_put_contents($seed, '{"ExistingModule":true}');
        config(['modules.paths.modules' => $this->directory.'/Modules',
            'modules.activators.file.statuses-file' => $seed, 'modules.runtime_statuses_file' => $runtime, 'modules.cache.enabled' => false]);
        app()->instance(\Nwidart\Modules\Contracts\ActivatorInterface::class, new StorageActivator($this->app));
        app()->instance('modules', new \Nwidart\Modules\Laravel\LaravelFileRepository($this->app, $this->directory.'/Modules'));
        $files = Mockery::mock(\Illuminate\Filesystem\Filesystem::class)->makePartial();
        $files->shouldReceive('delete')->with(app()->getCachedRoutesPath())->once()->andReturn(true);
        app()->instance('files', $files);
        $this->assertSame('Storefront', app(ModuleArchiveInstaller::class)->install($archive));
        foreach (['Http/Middleware/HostIntegration.php', 'Services/Installer.php', 'Services/SaleSync.php', 'Resources/assets/homepage.css', 'Resources/views/public_promo.blade.php'] as $file) {
            $this->assertFileExists($this->directory.'/Modules/Storefront/'.$file);
        }
        $this->assertDirectoryDoesNotExist($this->directory.'/Modules/Storefront/Tests');
        $this->assertFileDoesNotExist($this->directory.'/Modules/Storefront/.env');
        $this->assertFileDoesNotExist($this->directory.'/Modules/Storefront/build.php');
        $this->assertSame('{"ExistingModule":true}', file_get_contents($seed));
        $this->assertSame(['ExistingModule' => true, 'Storefront' => true], json_decode(file_get_contents($runtime), true));
    }
}
