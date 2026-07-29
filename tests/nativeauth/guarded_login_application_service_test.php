<?php
require_once dirname(__DIR__, 2)
    . '/app/core_modules/security/classes/nativeauth/'
    . 'configurablemfaenforcementpolicy.php';
require_once dirname(__DIR__, 2)
    . '/app/core_modules/security/classes/nativeauth/'
    . 'guardedloginapplicationservice.php';

function v74assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
}
class V74Result {
    public function isSuccess() { return true; }
}
class V74Credentials {
    public $valid = true;
    public function verifyCredentials($username, $password) {
        return $this->valid ? array('result' => new V74Result()) : false;
    }
}
class V74Policy {
    public $status = ConfigurableMfaEnforcementPolicy::STATUS_NOT_REQUIRED;
    public function evaluate($result, array $context = array()) {
        return array('status' => $this->status, 'deadline' => 1234);
    }
}
class V74Context {
    public function resolve($result) { return array('mfa_enrolled' => false); }
}
class V74Flow {
    public $calls = 0;
    public $required = null;
    public function afterPassword($csrf, array $proof, $remember, array $policy,
        array $metadata = array()) {
        $this->calls++;
        $this->required = $policy['required'];
        return array('status' => $policy['required']
            ? 'mfa_enrolment_required' : 'complete');
    }
}

$credentials = new V74Credentials();
$policy = new V74Policy();
$flow = new V74Flow();
$service = new GuardedLoginApplicationService(
    $credentials, $policy, new V74Context(), $flow
);
$credentials->valid = false;
$bad = $service->begin('csrf', 'admin', 'wrong', false);
v74assert($bad['status'] === 'invalid_request',
    'incorrect credentials use one invalid result');
v74assert($flow->calls === 0,
    'incorrect credentials never enter session/MFA finalisation');

$credentials->valid = true;
$ok = $service->begin('csrf', 'admin', 'a', false);
v74assert($ok['status'] === 'complete' && $flow->required === false,
    'non-required MFA continues through guarded flow');

$policy->status =
    ConfigurableMfaEnforcementPolicy::STATUS_CHALLENGE_REQUIRED;
$mfa = $service->begin('csrf', 'admin', 'a', true);
v74assert($mfa['status'] === 'mfa_enrolment_required'
    && $flow->required === true,
    'required MFA continues through guarded flow');

$policy->status = ConfigurableMfaEnforcementPolicy::STATUS_GRACE;
$grace = $service->begin('csrf', 'admin', 'a', false);
v74assert($grace['mfa_policy_status'] === 'grace'
    && $grace['mfa_grace_deadline'] === 1234
    && $flow->required === false,
    'grace status is preserved for the reminder without forcing challenge');

echo "PASS: guarded login application boundary.\n";
