<?php
require_once dirname(__FILE__)
    . '/../../app/core_modules/security/classes/nativeauth/mfawebflowservice.php';

function v65_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
        exit(1);
    }
    echo "PASS: " . $message . PHP_EOL;
}

class V65Result {
    public function isSuccess() { return true; }
    public function getUserId() { return 'user-7'; }
    public function getUsername() { return 'derek'; }
    public function getProviderId() { return 'database'; }
}
class V65Auth {
    public $begins = 0; public $totp = 0; public $recovery = 0;
    public function begin($csrf, $id, $name, $required, $remember, $meta) {
        $this->begins++;
        return array('status' => $required ? 'mfa_required' : 'complete');
    }
    public function completeTotp($csrf, $id, $code) {
        $this->totp++; return $code === '123456';
    }
    public function completeRecoveryCode($csrf, $id, $code) {
        $this->recovery++; return $code === 'RECOVERY';
    }
}
class V65Enrolment {
    public function begin($id) {
        return array('enrolment_id' => 'enrol-1',
            'secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ');
    }
    public function confirm($id, $enrolment, $code) {
        return $code === '123456'
            ? array('recovery_codes' => array('ONE', 'TWO')) : false;
    }
}
class V65Provisioning {
    public function build($secret, $issuer, $account) {
        return array('uri' => 'otpauth://local', 'setup_key' => $secret);
    }
}
class V65Pending {
    public $cleared = 0; public $value = null;
    public function peek() { return $this->value; }
    public function clear() { $this->cleared++; $this->value = null; }
}
class V65Factors {
    public $factor = null;
    public function findActiveTotpForUser($id) { return $this->factor; }
}

$auth = new V65Auth();
$enrol = new V65Enrolment();
$provision = new V65Provisioning();
$pending = new V65Pending();
$factors = new V65Factors();
$flow = new MfaWebFlowService(
    $auth, $enrol, $provision, $pending, $factors
);
$proof = array('result' => new V65Result());

$result = $flow->afterPassword('csrf', $proof, true,
    array('required' => true), array('password' => 'must-not-pass'));
v65_assert($result['status'] === 'mfa_enrolment_required',
    'required unenrolled user is routed to enrollment');
v65_assert($auth->begins === 0,
    'unenrolled route creates no authenticated or pending-login state');

$factors->factor = (object) array('id' => 'factor-1');
$result = $flow->afterPassword('csrf', $proof, true,
    array('required' => true), array('ip' => '127.0.0.1'));
v65_assert($result['status'] === 'mfa_required' && $auth->begins === 1,
    'enrolled required user enters the guarded challenge transaction');

$result = $flow->afterPassword('csrf', $proof, false,
    array('required' => false));
v65_assert($result['status'] === 'complete' && $auth->begins === 2,
    'non-MFA user finalises through the transaction coordinator');

$setup = $flow->beginEnrolment('user-7', 'Chisimba', 'derek');
v65_assert(isset($setup['uri'], $setup['setup_key'])
    && !isset($setup['secret']),
    'enrollment exposes only the provisioning contract');
v65_assert($flow->confirmEnrolment('user-7', 'enrol-1', '123456') !== false,
    'valid first code confirms enrollment and returns recovery codes');
v65_assert($flow->completeTotp('csrf', 'tx', '123456') === true,
    'TOTP completion delegates to the authenticated transaction boundary');
v65_assert($flow->completeRecoveryCode('csrf', 'tx', 'RECOVERY') === true,
    'recovery completion delegates to the authenticated transaction boundary');
$flow->cancel();
v65_assert($pending->cleared === 1, 'cancellation clears pending identity');

echo "PASS: V65 guarded MFA web-flow tests." . PHP_EOL;
