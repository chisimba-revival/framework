<?php
/**
 * Focused contract test for canonical toolbar registration.
 *
 * @author Derek Keats
 */

$root = dirname(__DIR__, 2);
$registerPath = $root
    . '/app/core_modules/toolbar/classes/register_class_inc.php';
$dbMenuPath = $root
    . '/app/core_modules/toolbar/classes/dbmenu_class_inc.php';
$modulesAdminPath = $root
    . '/app/core_modules/modulecatalogue/classes/modulesadmin_class_inc.php';

$register = file_get_contents($registerPath);
$dbMenu = file_get_contents($dbMenuPath);
$modulesAdmin = file_get_contents($modulesAdminPath);
if ($register === false || $dbMenu === false || $modulesAdmin === false) {
    fwrite(STDERR, "Unable to read toolbar registration sources\n");
    exit(1);
}

$required = array(
    'canonicalRightForRegistration',
    'canonicalRightForAccessList',
    "ensureArea(\n            'chisimba'",
    'ensureRight(',
    'ensureGroupGrant(',
    'ensureContextRoleGrantTemplate',
    "'permissions' => \$rightId === '' ? '' : (string) \$rightId",
);
foreach ($required as $needle) {
    if (strpos($register, $needle) === false) {
        fwrite(STDERR, "Missing canonical registration contract: $needle\n");
        exit(1);
    }
}

$forbiddenRegister = array(
    'permissions_model',
    'groupAdminModel',
    'newAcl',
    'addAclGroup',
    '|_con_',
);
foreach ($forbiddenRegister as $needle) {
    if (stripos($register, $needle) !== false) {
        fwrite(STDERR, "Legacy toolbar registration remains: $needle\n");
        exit(1);
    }
}

if (stripos($dbMenu, "permissions LIKE '%Lecturer%'") !== false
    || stripos($dbMenu, "permissions LIKE '%Site Admin%'") !== false) {
    fwrite(STDERR, "Legacy textual permission SQL remains in dbmenu\n");
    exit(1);
}

$menuStart = strpos(
    $modulesAdmin,
    'Toolbar owns menu authorization registration.'
);
$menuEnd = strpos($modulesAdmin, '// end Site Navigation', $menuStart);
if ($menuStart === false || $menuEnd === false) {
    fwrite(STDERR, "Unable to isolate module registration menu boundary\n");
    exit(1);
}
$menuBoundary = substr(
    $modulesAdmin,
    $menuStart,
    $menuEnd - $menuStart
);
foreach (array(
    'permissions_model',
    'newAcl',
    'addAclGroup',
    '|_con_',
    'permissions LIKE',
) as $needle) {
    if (stripos($menuBoundary, $needle) !== false) {
        fwrite(STDERR, "Legacy module menu registration remains: $needle\n");
        exit(1);
    }
}
if (strpos($menuBoundary, 'canonicalRightForRegistration') === false
    || strpos($menuBoundary, 'canonicalRightForAccessList') === false) {
    fwrite(STDERR, "Module registration does not use canonical toolbar API\n");
    exit(1);
}

echo "Canonical toolbar registration contract passed.\n";
