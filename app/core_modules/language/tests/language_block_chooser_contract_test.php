<?php
$root = dirname(__DIR__);
$block = file_get_contents($root . '/classes/block_language_class_inc.php');
$canvas = file_get_contents(dirname($root) . '/canvas/classes/buildcanvas_class_inc.php');
$checks = array(
    'language block declares chooser availability' => strpos($block, 'isAvailableForBlockChooser') !== false,
    'language block requires multiple languages' => strpos($block, 'getLanguageList()) > 1') !== false,
    'canvas chooser honours block availability' => strpos($canvas, "method_exists(\$block, 'isAvailableForBlockChooser')") !== false,
);
$failed = array_keys(array_filter($checks, static function ($passed) { return !$passed; }));
if ($failed) {
    fwrite(STDERR, "Language block chooser contract failed:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}
echo "Language block chooser contract passed.\n";
