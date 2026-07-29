<?php
class AuthenticationApplicationService {
    const CSRF_TOTP = 'native_auth_totp';
    const CSRF_RECOVERY = 'native_auth_recovery';
}
require_once dirname(__FILE__)
    . '/../../app/core_modules/security/classes/nativeauth/'
    . 'mfawebcontrolleradapter.php';

function v69_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
        exit(1);
    }
    echo "PASS: " . $message . PHP_EOL;
}
class V69Csrf {
    public $valid = true;
    public function issue($context) { return 'token-' . $context; }
    public function consume($context, $token) {
        return $this->valid && $token === 'token-' . $context;
    }
}
class V69Flow {
    public $cancelled = false;
    public function afterPassword() {}
    public function beginEnrolment($id, $issuer, $account) {
        return array(
            'enrolment_id' => 'enrol-1',
            'provisioning_uri' => 'otpauth://totp/x',
            'manual_key' => 'BASE32',
        );
    }
    public function confirmEnrolment($id, $enrolment, $code) {
        return $code === '123456'
            ? array('recovery_codes' => array('ONE', 'TWO'))
            : false;
    }
    public function completeTotp($csrf, $id, $code) {
        return $csrf === 'totp' && $id === 'tx' && $code === '123456';
    }
    public function completeRecoveryCode($csrf, $id, $code) {
        return $csrf === 'recovery' && $id === 'tx' && $code === 'RECOVERY';
    }
    public function cancel() { $this->cancelled = true; }
    public function currentChallenge() {
        return array(
            'transaction_id' => 'tx',
            'username' => 'derek',
            'expires_at' => time() + 300,
        );
    }
}

$csrf = new V69Csrf();
$flow = new V69Flow();
$adapter = new MfaWebControllerAdapter($flow, $csrf);

v69_assert(
    $adapter->startEnrolment(
        'GET', 'token-native_mfa_enrol_start', 'u1', 'Chisimba', 'derek'
    )['status'] === 'invalid_request',
    'enrollment mutation rejects GET'
);
$started = $adapter->startEnrolment(
    'POST', 'token-native_mfa_enrol_start', 'u1', 'Chisimba', 'derek'
);
v69_assert(
    $started['view'] === 'enrolment'
        && !isset($started['secret'])
        && strpos($started['provisioning_uri'], 'otpauth://') === 0,
    'enrollment exposes only local provisioning contract'
);
$confirmed = $adapter->confirmEnrolment(
    'POST', 'token-native_mfa_enrol_confirm', 'u1', 'enrol-1', '123 456'
);
v69_assert(
    $confirmed['view'] === 'recovery_codes'
        && count($confirmed['recovery_codes']) === 2,
    'confirmed enrollment returns one-time recovery codes'
);
v69_assert(
    $adapter->completeTotp('GET', 'totp', 'tx', '123456')['status']
        === 'invalid_request',
    'TOTP mutation rejects GET'
);
v69_assert(
    $adapter->completeTotp('POST', 'totp', 'tx', '123456')['status']
        === 'complete',
    'TOTP completion delegates to guarded boundary'
);
v69_assert(
    $adapter->completeRecovery(
        'POST', 'recovery', 'tx', 'RECOVERY'
    )['status'] === 'complete',
    'recovery completion delegates to guarded boundary'
);
$challenge = $adapter->challengePage();
v69_assert(
    $challenge['view'] === 'challenge'
        && isset($challenge['totp_csrf_token'])
        && isset($challenge['recovery_csrf_token'])
        && isset($challenge['cancel_csrf_token']),
    'challenge rendering receives three separate CSRF intents'
);
v69_assert(
    $adapter->cancel(
        'POST', 'token-native_mfa_cancel'
    )['status'] === 'cancelled' && $flow->cancelled,
    'CSRF-protected cancellation clears pending identity'
);
echo "PASS: V69 thin MFA controller adapter tests." . PHP_EOL;
