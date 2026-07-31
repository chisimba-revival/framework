<?php
/**
 * Shared implementation for direct canonical group-membership reads.
 *
 * GroupService remains the public group-domain boundary. This internal
 * component lets legacy-shaped adapters use the same identity resolution and
 * membership repository without constructing GroupService from beneath it.
 *
 * @author Derek Keats
 * @package groupadmin
 */

if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class groupmembershipreader extends ChisimbaObject
{
    private $objIdentityService;
    private $objMembershipDb;

    public function init()
    {
        $this->objIdentityService = $this->getObject(
            'identityservice',
            'security'
        );
        $this->objMembershipDb = $this->getObject(
            'groupmembershipdb',
            'groupadmin'
        );
    }

    /**
     * Determine whether a logical user belongs directly to one group.
     *
     * @param mixed $userId Logical tbl_users.userid value.
     * @param mixed $groupId Canonical group ID.
     * @return boolean
     */
    public function isGroupMember($userId, $groupId)
    {
        if (!is_scalar($userId)
            || trim((string) $userId) === ''
            || !is_scalar($groupId)
            || !preg_match('/^[1-9]\d*$/', (string) $groupId)) {
            return false;
        }

        $permissionUserId = $this->objIdentityService
            ->permissionUserIdForUser(trim((string) $userId));
        if ($permissionUserId === null) {
            return false;
        }

        return $this->objMembershipDb->membershipExists(
            (int) $groupId,
            $permissionUserId
        );
    }
}
?>
