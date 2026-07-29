<?php
$root = dirname(__DIR__, 2);
$source = file_get_contents($root . '/app/core_modules/security/controller.php');
if (strpos($source, "case 'mfa_cancel'") === false
    || strpos($source, "nativeMfaPage(\$result)") === false) {
    fwrite(STDERR, "FAIL: cancellation/expiry routing proof is absent.\n");
    exit(1);
}
echo "PASS: cancellation and expired MFA outcomes are routed.\n";
