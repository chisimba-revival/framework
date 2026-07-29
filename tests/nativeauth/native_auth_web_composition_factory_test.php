<?php
/**
 * Structural contract for the native authentication composition root.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
$root = dirname(__DIR__, 2);
$file = $root
    . '/app/core_modules/security/classes/nativeauth/'
    . 'nativeauthwebcompositionfactory.php';
$source = file_get_contents($file);
if ($source === false) {
    fwrite(STDERR, "FAIL: composition root cannot be read.\n");
    exit(1);
}
$required = array(
    'final class NativeAuthWebCompositionFactory',
    'new CsrfTokenService(',
    'new PendingAuthenticationService(',
    'new Mdb2MfaRepository(',
    'new MfaChallengeService(',
    'new AuthenticationTransactionCoordinator(',
    'new AuthenticationApplicationService(',
    'new MfaEnrolmentApplicationService(',
    'new MfaWebFlowService(',
    'new MfaWebControllerAdapter(',
    'new GuardedLoginApplicationService(',
    "'guarded_login' => \$guardedLogin",
    'new PersistentLoginCoordinator(',
    'new InstallationMfaKeyProvider()',
);
foreach ($required as $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, "FAIL: missing composition: $needle\n");
        exit(1);
    }
}
$forbidden = array(
    'authenticateUser(',
    'LiveUser',
    '$_POST',
    '$_GET',
    '$_REQUEST',
    'INSERT ',
    'UPDATE ',
    'DELETE ',
);
foreach ($forbidden as $needle) {
    if (strpos($source, $needle) !== false) {
        fwrite(STDERR, "FAIL: forbidden factory responsibility: $needle\n");
        exit(1);
    }
}
echo "PASS: guarded native-authentication stack has one composition root.\n";
