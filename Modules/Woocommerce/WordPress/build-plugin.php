<?php

// Build a portable WordPress upload ZIP with forward-slash entry names on Windows too.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$source = __DIR__.'/perry-customer-pos';
$output = __DIR__.'/perry-customer-pos.zip';
$zip = new ZipArchive();
if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Could not create plugin ZIP.');
}
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    if ($file->isFile()) {
        $relative = substr($file->getPathname(), strlen($source) + 1);
        $zip->addFile($file->getPathname(), 'perry-customer-pos/'.str_replace('\\', '/', $relative));
    }
}
if (! $zip->close()) {
    throw new RuntimeException('Could not finish plugin ZIP.');
}
echo 'Built '.$output.PHP_EOL;
