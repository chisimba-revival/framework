<?php
require_once dirname(__FILE__)
    . '/../classes/nativeauth/mfarepositoryinterface.php';
require_once dirname(__FILE__)
    . '/../classes/nativeauth/totpservice.php';
require_once dirname(__FILE__)
    . '/../classes/nativeauth/mfasecretprotector.php';
require_once dirname(__FILE__)
    . '/../classes/nativeauth/mfaenrolment.php';
require_once dirname(__FILE__)
    . '/../classes/nativeauth/mfachallengeservice.php';

class MemoryMfaRepository implements MfaRepositoryInterface
{
    public $factor;
    public $accepted = null;
    public function findActiveTotpForUser($userId) { return $this->factor; }
    public function acceptTotpStep($id, $step, $at) {
        if ($this->accepted !== null && $step <= $this->accepted) return false;
        $this->accepted = $step;
        return true;
    }
    public function consumeRecoveryCode($userId, $code, $usedAt) { return false; }
}
function ensureMfaV11($condition, $label) {
    if (!$condition) { fwrite(STDERR, 'FAIL: ' . $label . PHP_EOL); exit(1); }
}
$key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
$protector = new MfaSecretProtector($key);
$secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
$protected = $protector->protect($secret);
$repo = new MemoryMfaRepository();
$repo->factor = new MfaEnrolment(
    'factor-1',
    'user-1',
    'totp',
    $protected['ciphertext'],
    $protected['nonce']
);
$totp = new TotpService(30, 8, 0);
$service = new MfaChallengeService($repo, $totp, $protector);
$code = $totp->atStep($secret, 1);
ensureMfaV11($service->verifyTotp('user-1', $code, 59), 'TOTP accepted');
ensureMfaV11(!$service->verifyTotp('user-1', $code, 59), 'TOTP replay rejected');
fwrite(STDOUT, 'PASS: MFA challenge contract' . PHP_EOL);
