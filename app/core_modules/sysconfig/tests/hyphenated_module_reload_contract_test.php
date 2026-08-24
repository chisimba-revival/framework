<?php
$controller = file_get_contents(dirname(__DIR__) . '/controller.php');
$checks = array(
    'hyphenated module ids accepted' => str_contains(
        $controller,
        "preg_match('/^[A-Za-z0-9_-]+$/', \$pmodule)"
    ),
    'site settings remain protected' => str_contains(
        $controller,
        "|| \$pmodule === '_site_'"
    ),
    'register file identity checked' => str_contains(
        $controller,
        "\$registerData['MODULE_ID'] !== \$pmodule"
    ),
    'additive reload retained' => str_contains(
        $controller,
        'registerMissingModuleParams('
    ),
);
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}
echo 'OK: ' . count($checks) . " hyphenated sysconfig reload checks\n";
?>
