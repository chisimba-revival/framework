<?php
$root = dirname(__DIR__, 2);
$controller = file_get_contents($root . '/app/core_modules/security/controller.php');
$form = file_get_contents(
    $root . '/app/core_modules/security/templates/content/native_login_tpl.php'
);
if (strpos($controller, "issue(self::LOGIN_CSRF_CONTEXT)") === false
    || strpos($controller, "getParam('native_auth_begin'") === false
    || strpos($form, 'name="native_auth_begin"') === false) {
    fwrite(STDERR, "FAIL: login CSRF issue/consume contract is incomplete.\n");
    exit(1);
}
echo "PASS: native login CSRF form/action contract.\n";
