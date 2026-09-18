<?php
require_once dirname(__FILE__) . '/../../app/core_modules/security/classes/nativeauth/mfarepositoryinterface.php';
require_once dirname(__FILE__) . '/../../app/core_modules/security/classes/nativeauth/mfaenrolment.php';
require_once dirname(__FILE__) . '/../../app/core_modules/security/classes/nativeauth/totpservice.php';
require_once dirname(__FILE__) . '/../../app/core_modules/security/classes/nativeauth/mfasecretprotector.php';
require_once dirname(__FILE__) . '/../../app/core_modules/security/classes/nativeauth/recoverycodeservice.php';
require_once dirname(__FILE__) . '/../../app/core_modules/security/classes/nativeauth/installationmfakeyprovider.php';
require_once dirname(__FILE__) . '/../../app/core_modules/security/classes/nativeauth/mfaenrolmentapplicationservice.php';

function v62check($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
        exit(1);
    }
}

class V62Repository implements MfaRepositoryInterface
{
    public $factor;
    public $recovery = array();
    public function findActiveTotpForUser($userId) { return null; }
    public function findPendingTotpById($id, $userId) {
        return $this->factor && $this->factor->id === $id
            && $this->factor->userId === $userId ? $this->factor : null;
    }
    public function storePendingTotp(array $row) {
        $this->factor = new MfaEnrolment(
            $row['id'], $row['user_id'], 'totp',
            $row['encrypted_secret'], $row['secret_nonce']
        );
        return true;
    }
    public function verifyPendingTotp($id, $at) { return true; }
    public function replaceRecoveryCodes($userId, array $codes, $at) {
        $this->recovery = $codes; return true;
    }
    public function disableTotpForUser($userId, $at) { return 0; }
    public function acceptTotpStep($id, $step, $at) { return true; }
    public function consumeRecoveryCode($userId, $code, $at) { return false; }
}

$now = 1785140000;
$key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
$repo = new V62Repository();
$totp = new TotpService();
$service = new MfaEnrolmentApplicationService(
    $repo,
    $totp,
    new MfaSecretProtector($key),
    new RecoveryCodeService(),
    function () use ($now) { return $now; }
);
$begin = $service->begin('user-1');
v62check(!empty($begin['enrolment_id']), 'opaque enrollment id is returned');
v62check(!empty($begin['secret']), 'one-time setup secret is returned');
v62check(
    strpos($repo->factor->ciphertext, $begin['secret']) === false,
    'plaintext secret is absent from persistence'
);
$code = $totp->atStep($begin['secret'], (int) floor($now / 30));
$confirmed = $service->confirm('user-1', $begin['enrolment_id'], $code);
v62check(is_array($confirmed), 'valid TOTP confirms enrollment');
v62check(count($confirmed['recovery_codes']) === 10, 'ten recovery codes returned once');
v62check(count($repo->recovery) === 10, 'ten recovery hashes persisted');
foreach ($repo->recovery as $record) {
    v62check(
        !in_array($record['code_hash'], $confirmed['recovery_codes'], true),
        'plaintext recovery code is absent from persistence'
    );
}
v62check(
    $service->confirm('other-user', $begin['enrolment_id'], $code) === false,
    'enrollment is bound to its user'
);
// Exercise the current installation-key boundary with isolated synthetic material.
$keyPath=sys_get_temp_dir().'/chisimba-mfa-test-'.bin2hex(random_bytes(8));
$master=new InstallationMasterKeyProvider($keyPath);
$provider=new InstallationMfaKeyProvider($master);
try {
    try {
        $provider->getKey();
        v62check(false, 'missing installation key fails closed');
    } catch (RuntimeException $expected) {
        v62check(true, 'missing installation key fails closed');
    }
    file_put_contents($keyPath,base64_encode($key));chmod($keyPath,0600);
    v62check(hash_equals(hash_hmac('sha256','chisimba:mfa-encryption-v1',$key,true),$provider->getKey()),'MFA key is purpose-derived from the installation master key');
    v62check(!hash_equals($key,$provider->getKey()),'raw installation key is not used as the MFA key');
} finally {if(is_file($keyPath))unlink($keyPath);}
echo "PASS: V62 MFA enrollment composition prerequisite tests." . PHP_EOL;
