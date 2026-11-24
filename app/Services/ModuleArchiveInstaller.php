<?php

namespace App\Services;

use ZipArchive;

class ModuleArchiveInstaller
{
    public function install(string $archive): string
    {
        $zip = new ZipArchive();
        if ($zip->open($archive) !== true) { throw new \RuntimeException('The module ZIP could not be opened.'); }
        try {
            $root = null;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                $parts = explode('/', $name);
                if (str_contains($name, '\\') || str_contains($name, "\0") || in_array('..', $parts, true)
                    || ! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $parts[0]) || count($parts) < 2) {
                    throw new \RuntimeException('The ZIP must contain one module folder with no paths outside it.');
                }
                $root = $root ?? $parts[0];
                if ($root !== $parts[0]) { throw new \RuntimeException('Upload one module at a time.'); }
                $zip->getExternalAttributesIndex($index, $operatingSystem, $attributes);
                if (($attributes >> 16 & 0170000) === 0120000) { throw new \RuntimeException('Module ZIPs cannot contain symbolic links.'); }
            }
            $manifest = $root ? json_decode($zip->getFromName($root.'/module.json') ?: '', true) : null;
            if (! $manifest || ($manifest['name'] ?? null) !== $root) { throw new \RuntimeException('The module folder and module.json name must match.'); }
            $directory = config('modules.paths.modules', base_path('Modules'));
            app('files')->ensureDirectoryExists($directory);
            if (! $zip->extractTo($directory)) { throw new \RuntimeException('The module files could not be extracted. Check folder permissions.'); }
        } finally {
            $zip->close();
        }
        app('cache')->store(config('modules.cache.driver'))->forget(config('modules.cache.key', 'laravel-modules'));
        $modules = app('modules')->scan();
        if (! isset($modules[$root])) { throw new \RuntimeException('The uploaded module could not be discovered.'); }
        $modules[$root]->enable();
        app('files')->delete(app()->getCachedRoutesPath());
        return $root;
    }
}
