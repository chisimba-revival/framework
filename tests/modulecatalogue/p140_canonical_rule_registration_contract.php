<?php
/**
 * Contract for canonical module RULE permission registration.
 *
 * @author Derek Keats
 */
$source = file_get_contents(
    dirname(__DIR__, 2)
    . '/app/core_modules/modulecatalogue/classes/modulesadmin_class_inc.php'
);
if ($source === false) {
    fwrite(STDERR, "FAIL: unable to read modulesadmin source\n");
    exit(1);
}
$required = array(
    "->ensureArea(\n                            'chisimba',\n                            \$moduleId",
    '->ensureRight(',
    '->ensureGroupGrant(',
    'Canonical module permission right creation failed',
);
foreach ($required as $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, "FAIL: missing canonical RULE registration contract\n");
        exit(1);
    }
}
$forbidden = array(
    '->perm->addRight(',
    '->perm->grantGroupRight(',
);
foreach ($forbidden as $needle) {
    if (strpos($source, $needle) !== false) {
        fwrite(STDERR, "FAIL: legacy RULE permission write remains\n");
        exit(1);
    }
}
echo "PASS: module RULE definitions and grants use PermissionService.\n";
