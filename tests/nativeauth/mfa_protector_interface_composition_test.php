<?php
/**
 * Regression contract for deferred MFA composition through its interface.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
$root = dirname(__DIR__, 2);
$native = $root . '/app/core_modules/security/classes/nativeauth/';

require_once $native . 'mfarepositoryinterface.php';
require_once $native . 'totpservice.php';
require_once $native . 'recoverycodeservice.php';
require_once $native . 'mfasecretprotector.php';
require_once $native . 'deferredmfasecretprotector.php';
require_once $native . 'mfachallengeservice.php';
require_once $native . 'mfaenrolmentapplicationservice.php';

function assertProtectorContract($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

class V92KeyProvider
{
    public $calls = 0;

    public function getKey()
    {
        $this->calls++;
        return random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}

class V92MfaRepository implements MfaRepositoryInterface
{
    public function findActiveTotpForUser($userId) { return false; }
    public function acceptTotpStep($factorId, $step, $now) { return false; }
    public function consumeRecoveryCode($userId, $codeHash, $now) { return false; }
    public function storePendingTotp($record) { return false; }
    public function findPendingTotpById($id, $userId) { return false; }
    public function verifyPendingTotp($id, $verifiedAt) { return false; }
    public function replaceRecoveryCodes($userId, array $records, $createdAt) {
        return false;
    }
    public function disableTotpForUser($userId, $disabledAt) { return false; }
}

$provider = new V92KeyProvider();
$protector = new DeferredMfaSecretProtector($provider);
$repository = new V92MfaRepository();
$totp = new TotpService();
$recovery = new RecoveryCodeService();

new MfaChallengeService($repository, $totp, $protector);
new MfaEnrolmentApplicationService(
    $repository,
    $totp,
    $protector,
    $recovery
);

assertProtectorContract(
    $protector instanceof MfaSecretProtectorInterface,
    'deferred protector implements the shared contract'
);
assertProtectorContract(
    (new MfaSecretProtector(
        random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
    )) instanceof MfaSecretProtectorInterface,
    'eager protector implements the shared contract'
);
assertProtectorContract(
    $provider->calls === 0,
    'constructing MFA services does not load the installation key'
);

echo "PASS: both MFA services accept lazy protection without loading a key\n";
