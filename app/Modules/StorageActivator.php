<?php

namespace App\Modules;

use Illuminate\Container\Container;
use Nwidart\Modules\Activators\FileActivator;

class StorageActivator extends FileActivator
{
    public function __construct(Container $app)
    {
        $config = $app['config'];
        $files = $app['files'];
        $legacy = $config->get('modules.activators.file.statuses-file');
        $runtime = $config->get('modules.runtime_statuses_file', storage_path('app/modules_statuses.json'));
        if (! $files->exists($runtime)) {
            $files->ensureDirectoryExists(dirname($runtime));
            $contents = $files->exists($legacy) ? $files->get($legacy) : '{}';
            if (! is_array(json_decode($contents, true))) { throw new \RuntimeException('The module activation settings are invalid.'); }
            $files->put($runtime, $contents, true);
        }
        $config->set('modules.activators.file.statuses-file', $runtime);
        $config->set('modules.activators.file.cache-key', 'activator.installed.runtime');
        parent::__construct($app);
    }
}
