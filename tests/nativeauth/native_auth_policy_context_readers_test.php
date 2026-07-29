<?php
require_once dirname(__DIR__, 2)
    . '/app/core_modules/security/classes/nativeauth/'
    . 'nativeauthpolicycontextreaders.php';

function v77check($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
}
class V77Users {
    public $calls = array();
    public function findByUserId($id) {
        $this->calls[] = $id;
        return array('userid' => $id, 'creationdate' => '2026-07-28');
    }
}
class V77Groups {
    public $groupId = 6;
    public $calls = array();
    public function groupIdForName($name) {
        $this->calls[] = array('group', $name);
        return $this->groupId;
    }
    public function isGroupMember($userId, $groupId) {
        $this->calls[] = array('member', $userId, $groupId);
        return true;
    }
}
class V77Settings {
    public $value = '2026-07-28 06:00:00';
    public $calls = array();
    public function getValue($name, $module = '_site_', $default = null) {
        $this->calls[] = array($name, $module, $default);
        return $this->value;
    }
}
class V77Factors {
    public $factor = null;
    public $calls = array();
    public function findActiveTotpForUser($id) {
        $this->calls[] = $id;
        return $this->factor;
    }
}

$users = new V77Users();
$groups = new V77Groups();
$settings = new V77Settings();
$factors = new V77Factors();
$readers = new NativeAuthPolicyContextReaders(
    $users, $groups, $settings, $factors
);

v77check($readers->userRecord('user-7')['userid'] === 'user-7',
    'canonical user record is returned');
v77check($readers->isSiteAdministrator('user-7') === true,
    'Site Admin membership uses canonical group service');
v77check($groups->calls === array(
    array('group', 'Site Admin'), array('member', 'user-7', 6),
), 'administrator lookup uses exact canonical group contract');
v77check($readers->hasActiveFactor('user-7') === false,
    'missing active factor is false');
$factors->factor = new stdClass();
v77check($readers->hasActiveFactor('user-7') === true,
    'verified active factor is true');
v77check($readers->policyEnabledAt('user-7') > 0,
    'policy activation date is normalised');
v77check($settings->calls === array(
    array('mfa_policy_enabled_at', 'security', 0),
), 'policy activation uses the owning module and safe default');
$settings->value = 'not-a-date';
v77check($readers->policyEnabledAt('user-7') === 0,
    'absent policy activation safely falls back to account creation');

$groups->groupId = false;
$failed = false;
try {
    $readers->isSiteAdministrator('user-7');
} catch (RuntimeException $exception) {
    $failed = true;
}
v77check($failed, 'missing or duplicate Site Admin identity fails closed');

echo "PASS: production MFA policy readers use canonical contracts.\n";
