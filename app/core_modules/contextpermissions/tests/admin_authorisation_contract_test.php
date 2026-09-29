<?php
/** Keep the permission-rule editor behind the current user-service admin check. */
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$register = file_get_contents($root . '/register.conf');

$checks = array(
    'permission editor checks the current user-service administrator role' =>
        str_contains($controller, "return \$objUser->isAdmin();"),
    'permission editor does not fall back to permissive legacy access' =>
        !str_contains($controller, 'return parent::isValid( $action, $default );'),
    'module version records the security repair' => preg_match(
        '/^MODULE_VERSION:\s+0\.206$/m',
        $register
    ) === 1,
);

foreach ($checks as $label => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$label}\n");
        exit(1);
    }
}

echo "PASS: context-permission administrator authorisation contract verified.\n";
