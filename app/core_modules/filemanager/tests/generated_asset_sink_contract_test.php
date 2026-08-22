<?php
$root = dirname(__DIR__);
$api = file_get_contents($root . '/classes/fileapi_class_inc.php');
$register = file_get_contents($root . '/register.conf');
$checks = array(
    'public generated image API' => str_contains($api, 'function storeContextGeneratedImage'),
    'active context boundary' => str_contains($api, '$contextCode !== $activeContext'),
    'strict base64' => str_contains($api, "base64_decode((string) (\$asset['content'] ?? ''), true)"),
    'content-addressed filename' => str_contains($api, "hash('sha256', \$content)"),
    'managed catalogue row' => str_contains($api, '$this->objFiles->addFile'),
    'visible errors registered' => substr_count($register, 'TEXT: mod_filemanager_generated_asset_') === 6
        && !str_contains($api, "'The generated image"),
    'module version bump' => str_contains($register, 'MODULE_VERSION: 1.097')
);
foreach ($checks as $name => $passed) { if (!$passed) { fwrite(STDERR, "FAIL: $name\n"); exit(1); } }
echo "OK: " . count($checks) . " generated asset sink checks\n";
