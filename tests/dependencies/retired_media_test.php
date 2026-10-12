<?php
$root = dirname(__DIR__, 2);
foreach (json_decode(file_get_contents(__DIR__.'/retired-media-paths.json'), true, 512, JSON_THROW_ON_ERROR) as $path) {
    if (file_exists($root.'/'.$path)) throw new RuntimeException('Retired media runtime: '.$path);
}
echo "PASS: migrated Flash and Java media runtime files are absent\n";
