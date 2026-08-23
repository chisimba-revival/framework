<?php

$toolbar = dirname(__DIR__);
$sideMenu = file_get_contents($toolbar . '/classes/sidemenu_class_inc.php');
if ($sideMenu === false) {
    fwrite(STDERR, "FAIL: unable to read post-login side menu\n");
    exit(1);
}

$checks = array(
    'post-login account navigation requests the context action' => preg_match(
        '/function getPostLoginMenuItems\(\).*?getMenuList\(\$menus, true\)/s',
        $sideMenu
    ) === 1,
    'action is conditional on active context' => str_contains(
        $sideMenu,
        'if ($includeLeaveContext && $this->context)'
    ),
    'action uses canonical leave route' => str_contains(
        $sideMenu,
        "array('action' => 'leavecontext')"
    ),
    'action uses system-text label' => str_contains(
        $sideMenu,
        "'mod_toolbar_leavecontext'"
    ),
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, 'FAIL: ' . $name . "\n");
        exit(1);
    }
}

fwrite(STDOUT, "PASS: post-login account card exposes a context-only leave action.\n");

?>
