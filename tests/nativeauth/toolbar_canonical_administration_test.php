<?php
/**
 * Focused contract test for canonical toolbar administration.
 *
 * @author Derek Keats
 */
$root = dirname(__DIR__, 2);
$paths = array(
    'controller' => $root . '/app/core_modules/toolbar/controller.php',
    'dbmenu' => $root . '/app/core_modules/toolbar/classes/dbmenu_class_inc.php',
    'tool' => $root . '/app/core_modules/toolbar/templates/content/addtool_tpl.php',
    'menu' => $root . '/app/core_modules/toolbar/templates/content/addmenu_tpl.php',
    'links' => $root . '/app/core_modules/toolbar/templates/content/editlinks_tpl.php',
);
$source = array();
foreach ($paths as $name => $path) {
    $source[$name] = file_get_contents($path);
    if ($source[$name] === false) {
        fwrite(STDERR, "Unable to read $name source\n");
        exit(1);
    }
}
$requiredController = array(
    'requireAdministrator',
    'requireMutation',
    "REQUEST_METHOD",
    "csrf->consume(self::CSRF_CONTEXT",
    'rightsForArea',
    'validRightSelection',
    'getLinkById',
    'deleteLinkById',
    'deleteLinksForModule',
);
foreach ($requiredController as $needle) {
    if (strpos($source['controller'], $needle) === false) {
        fwrite(STDERR, "Missing administration contract: $needle\n");
        exit(1);
    }
}
foreach (array('tool', 'menu', 'links') as $template) {
    if (strpos($source[$template], 'toolbar_csrf') === false) {
        fwrite(STDERR, "Missing CSRF field in $template template\n");
        exit(1);
    }
}
foreach (array('tool', 'menu') as $template) {
    if (strpos($source[$template], 'rightOptions') === false
        || strpos($source[$template], 'name="permissions"') === false) {
        fwrite(STDERR, "Missing canonical right selection in $template\n");
        exit(1);
    }
}
$all = implode("\n", $source);
foreach (array(
    "getObject('perms'",
    'groupadminmodel',
    'getAcls(',
    '|_con_',
    'window.opener',
    "javascript:void(0)",
    "case 'restoreperms'",
) as $needle) {
    if (stripos($all, $needle) !== false) {
        fwrite(STDERR, "Legacy toolbar administration remains: $needle\n");
        exit(1);
    }
}
$deleteCase = strpos($source['controller'], "case 'delete':");
$deleteGuard = strpos(
    $source['controller'],
    '$this->requireMutation();',
    $deleteCase
);
if ($deleteCase === false
    || $deleteGuard === false
    || $deleteGuard - $deleteCase > 120) {
    fwrite(STDERR, "Delete is not guarded as a mutation\n");
    exit(1);
}
if (file_exists(
    $root . '/app/core_modules/toolbar/templates/content/editperms_tpl.php'
)) {
    fwrite(STDERR, "Obsolete ACL editor still exists\n");
    exit(1);
}
echo "Canonical toolbar administration contract passed.\n";
