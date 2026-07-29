<?php
$root = dirname(__DIR__, 2);
$source = file_get_contents($root . '/app/core_modules/security/controller.php');

// These authentication-state operations are forbidden throughout the
// controller. The canonical authentication services own them.
foreach (array('session_regenerate_id(', 'authenticateUser(') as $bad) {
    if (strpos($source, $bad) !== false) {
        fwrite(STDERR, "FAIL: controller owns authentication state: $bad\n");
        exit(1);
    }
}

// setSession() is still legitimately used by unrelated legacy controller
// actions (old URL and password-reset state). Check only the new native
// authentication dispatch methods, from nativeLogin() up to nativeMfaPage().
$start = strpos($source, 'private function nativeLogin()');
$end = strpos($source, 'private function nativeMfaPage(array $result)');
if ($start === false || $end === false || $end <= $start) {
    fwrite(STDERR, "FAIL: native authentication method boundaries are absent.\n");
    exit(1);
}
$nativeDispatch = substr($source, $start, $end - $start);
if (strpos($nativeDispatch, 'setSession(') !== false) {
    fwrite(STDERR, "FAIL: native authentication dispatch owns session state.\n");
    exit(1);
}
foreach (array(
    "['guarded_login']->begin(",
    '$adapter->startEnrolment(',
    '$adapter->confirmEnrolment(',
    '$adapter->completeTotp(',
    '$adapter->completeRecovery(',
    '$adapter->cancel(',
) as $proof) {
    if (strpos($nativeDispatch, $proof) === false) {
        fwrite(STDERR, "FAIL: native delegation proof is absent: $proof\n");
        exit(1);
    }
}
echo "PASS: native login creates no authentication state before MFA.\n";
