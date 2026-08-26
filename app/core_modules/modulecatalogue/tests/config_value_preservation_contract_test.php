<?php
$source = file_get_contents(
    dirname(__DIR__) . '/classes/modulesadmin_class_inc.php'
);

$checks = array(
    'module registration is additive' => str_contains(
        $source,
        'registerMissingModuleParams('
    ),
    'legacy overwrite registrar is not used by updates' => !preg_match(
        '/updateFlag\s*=\s*TRUE[\s\S]{0,200}registerModuleParams\s*\(/',
        $source
    ),
    'preservation intent is documented' => str_contains(
        $source,
        'must never replace a site\'s established'
    ),
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}

echo 'PASS: module updates preserve existing configuration values' . PHP_EOL;
?>
