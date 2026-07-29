<?php
$root = dirname(__DIR__, 2);
$classes = $root . '/app/core_modules/toolbar/classes';
$files = array(
    'toolbar_class_inc.php',
    'toolbar_elearn_class_inc.php',
    'tools_class_inc.php',
    'flatmenu_class_inc.php',
    'cssmenu_class_inc.php',
    'tabsmenu_class_inc.php',
    'menu_class_inc.php',
    'adminmenu_class_inc.php',
    'postloginmenu_elearn_class_inc.php',
    'sidemenu_class_inc.php',
);

$failed = false;
foreach ($files as $file) {
    $source = file_get_contents($classes . '/' . $file);
    $forbidden = array(
        "/getObject\\s*\\(\\s*['\"]user['\"]\\s*,\\s*['\"]security['\"]/",
        "/getObject\\s*\\(\\s*['\"]perms['\"]\\s*,\\s*['\"]permissions['\"]/",
        "/permissions_model/",
        "/groupAdminModel/",
        "/->isLoggedIn\\s*\\(/",
        "/->isAdmin\\s*\\(/",
        "/action['\"]?\\s*=>\\s*['\"]logoff['\"]/",
        "/javascript:\\s*if\\s*\\(\\s*confirm/",
    );
    foreach ($forbidden as $pattern) {
        if (preg_match($pattern, $source)) {
            fwrite(STDERR, "$file contains forbidden legacy wiring: $pattern\n");
            $failed = true;
        }
    }
}

$tools = file_get_contents($classes . '/tools_class_inc.php');
foreach (array(
    'toolbarsecuritycontext',
    'mayUseRight',
    'ctype_digit',
    'return false',
) as $required) {
    if (strpos($tools, $required) === false) {
        fwrite(STDERR, "tools renderer lacks required fail-closed contract: $required\n");
        $failed = true;
    }
}

$logoutFiles = array(
    'toolbar_elearn_class_inc.php',
    'flatmenu_class_inc.php',
    'cssmenu_class_inc.php',
    'tabsmenu_class_inc.php',
);
foreach ($logoutFiles as $file) {
    $source = file_get_contents($classes . '/' . $file);
    if (strpos($source, 'logoutForm') === false) {
        fwrite(STDERR, "$file does not use canonical POST/CSRF logout\n");
        $failed = true;
    }
}

if ($failed) {
    exit(1);
}
echo "Canonical toolbar renderer contract passed.\n";
?>
