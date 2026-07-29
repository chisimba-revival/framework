<?php
$root = dirname(__DIR__, 2);
$source = file_get_contents($root . '/app/core_modules/security/controller.php');
foreach (array(
    "case 'login'", 'native_auth_begin', "['guarded_login']->begin(",
    'mfa_enrol_start', 'mfa_enrol_confirm', 'mfa_totp',
    'mfa_recovery', 'mfa_cancel',
) as $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, "FAIL: missing controller proof: $needle\n");
        exit(1);
    }
}
echo "PASS: active controller delegates to guarded login and MFA boundaries.\n";
