<?php
/**
 * Permanent native-login cutover contract.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
$root = dirname(__FILE__) . '/../..';
$security = $root . '/app/core_modules/security';
$user = file_get_contents($security . '/classes/user_class_inc.php');
$auth = file_get_contents($security . '/classes/auth_database_class_inc.php');
$verifier = file_get_contents(
    $security . '/classes/nativeauth/nativepasswordverifier.php'
);

function v99contract($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

v99contract(strpos($user, 'nativeSessionService->isAuthenticated()') !== false,
    'user login state is owned by NativeSessionService');
v99contract(strpos($auth, 'LocalPasswordProvider') !== false,
    'database credential adapter delegates to LocalPasswordProvider');
v99contract(strpos($verifier, 'password_verify(') !== false,
    'native verifier uses password_verify');
v99contract(strpos($verifier, "return 'unknown';") !== false,
    'unsupported credential schemes fail closed');

$forbidden = array(
    'LegacyAuthSessionBridge',
    'CHISIMBA_NATIVE_AUTH_LOGIN',
    'NATIVE_LOGIN_STATE_COMPATIBILITY',
    'legacy LiveUser login remains available as rollback',
    'valid legacy hash authenticates',
    'legacy hash requests migration',
);
$sources = array(
    $security . '/classes',
);
foreach ($forbidden as $needle) {
    foreach ($sources as $source) {
        $command = 'rg -l -F '
            . escapeshellarg($needle) . ' '
            . escapeshellarg($source);
        exec($command, $output, $status);
        v99contract($status === 1,
            'forbidden rollback contract is absent: ' . $needle);
        $output = array();
    }
}

v99contract(!is_file(
    $security . '/classes/nativeauth/legacyauthsessionbridge.php'
), 'legacy authentication session bridge is deleted');
v99contract(!is_file(
    $root . '/tests/nativeauth/native_login_state_compatibility_test.php'
), 'rollout compatibility test is deleted');

echo "ALL PERMANENT NATIVE LOGIN CUTOVER CONTRACTS PASSED\n";
