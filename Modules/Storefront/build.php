<?php

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__, 2);
$directory = $root.'/storage/app/module-packages';
if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) { throw new RuntimeException('Could not create output directory.'); }
$output = $directory.'/Storefront-1.3.1.zip';
$zip = new ZipArchive();
if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) { throw new RuntimeException('Could not create module ZIP.'); }
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__, FilesystemIterator::SKIP_DOTS)) as $file) {
    if (! $file->isFile() || str_starts_with($file->getFilename(), '.env') || $file->getExtension() === 'zip') { continue; }
    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(__DIR__) + 1));
    $zip->addFile($file->getPathname(), 'Storefront/'.$relative);
}
if (! $zip->close()) { throw new RuntimeException('Could not finish module ZIP.'); }
echo 'Built '.$output.PHP_EOL;
