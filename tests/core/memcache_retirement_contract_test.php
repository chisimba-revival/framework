<?php
/** Ensure the retired Memcache integration cannot be re-enabled accidentally. */
$root = dirname(__DIR__, 2);
$files = array(
    'engine' => $root . '/app/classes/core/engine_class_inc.php',
    'dbtable' => $root . '/app/classes/core/dbtable_class_inc.php',
    'groupops' => $root . '/app/core_modules/groupadmin/classes/groupops_class_inc.php',
    'cacheops' => $root . '/app/core_modules/cache/classes/cacheops_class_inc.php',
    'altconfig' => $root . '/app/core_modules/config/classes/altconfig_class_inc.php',
    'languageconfig' => $root . '/app/core_modules/language/classes/languageconfig_class_inc.php',
);

$source = '';
foreach ($files as $name => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "FAIL: missing {$name} source\n");
        exit(1);
    }
    $source .= file_get_contents($path);
}

$checks = array(
    'legacy cache adapter has been removed' => !is_file($root . '/app/classes/core/chisimbacache_class_inc.php'),
    'no first-party code loads the Memcache extension' => strpos($source, "extension_loaded('memcache')") === false,
    'no first-party code exposes the Memcache setting' => strpos($source, 'getenable_memcache') === false,
    'generic cache helper no longer serializes values for Memcache' => strpos(file_get_contents($files['cacheops']), 'unserialize(') === false,
);

foreach ($checks as $label => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$label}\n");
        exit(1);
    }
}

echo "PASS: legacy Memcache runtime integration is retired.\n";
