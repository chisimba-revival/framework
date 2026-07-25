<?php
class initialadminprovisioningservice extends ChisimbaObject
{
    private $objIdentityService;
    private $objGroupService;
    private $objMembershipDb;
    private $objUser;

    public function init()
    {
        $this->objIdentityService = $this->getObject('identityservice', 'security');
        $this->objGroupService = $this->getObject('groupservice', 'groupadmin');
        $this->objMembershipDb = $this->getObject('groupmembershipdb', 'groupadmin');
        $this->objUser = $this->getObject('user', 'security');
    }

    public function ensureInitialAdministrator($userId = '1')
    {
        $userId = trim((string) $userId);
        if ($userId !== '1') {
            return array('ok' => false, 'code' => 'invalid_bootstrap_user');
        }

        $users = $this->objUser->getArray(
            "SELECT userid, username FROM tbl_users"
            . " WHERE userid = '1' AND LOWER(username) = 'admin' LIMIT 2"
        );
        if (!is_array($users) || count($users) !== 1) {
            return array('ok' => false, 'code' => 'bootstrap_user_not_found');
        }

        $permissionUserId = $this->objIdentityService->ensurePermissionIdentity($userId);
        if ($permissionUserId === null) {
            return array('ok' => false, 'code' => 'permission_identity_failed');
        }

        $groupId = $this->positiveInteger(
            $this->objGroupService->groupIdForName('Site Admin')
        );
        if ($groupId === null) {
            return array('ok' => false, 'code' => 'site_admin_group_missing');
        }

        if (!$this->objMembershipDb->membershipExists($groupId, $permissionUserId)
            && !$this->objMembershipDb->addMembership($groupId, $permissionUserId)) {
            return array('ok' => false, 'code' => 'site_admin_membership_failed');
        }

        return array(
            'ok' => true,
            'code' => 'initial_administrator_ready',
            'userId' => $userId,
            'permissionUserId' => $permissionUserId,
            'groupId' => $groupId,
        );
    }

    private function positiveInteger($value)
    {
        if (!is_scalar($value) || !preg_match('/^[1-9]\d*$/', (string) $value)) {
            return null;
        }
        return (int) $value;
    }
}
?>
