<?php
/**
 * Canonical internal service boundary for group and membership data.
 *
 * This class owns no storage. It delegates group and membership operations to
 * groupadminmodel and user reads to the established security classes. Native
 * and legacy transports must converge here rather than introduce direct SQL.
 *
 * Milestone 15 initially exposes read operations only.
 *
 * @package groupadmin
 */

if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class groupservice extends ChisimbaObject
{
    private $objGroups;
    private $objUserAdmin;
    private $objUser;
    private $objContext;
    private $objMembershipDb;

    public function init()
    {
        $this->objGroups = $this->getObject('groupadminmodel', 'groupadmin');
        $this->objUserAdmin = $this->getObject('useradmin_model2', 'security');
        $this->objUser = $this->getObject('user', 'security');
        $this->objContext = $this->getObject('dbcontext', 'context');
        $this->objMembershipDb = $this->getObject('groupmembershipdb', 'groupadmin');
    
        $this->objIdentityService = $this->getObject('identityservice', 'security');
}

    /**
     * Return a normalized flat hierarchy.
     *
     * Top-level records are followed by their direct context subgroups. The
     * presentation layer may group these records visually without knowing
     * anything about LiveUser or ExtJS response formats.
     */
    public function listGroups()
    {
        $topGroups = $this->objGroups->getTopLevelGroups();
        if (!is_array($topGroups)) {
            return array();
        }

        $records = array();

        foreach ($topGroups as $topGroup) {
            if (!is_array($topGroup)) {
                continue;
            }

            $groupId = $this->positiveInteger(
                isset($topGroup['group_id']) ? $topGroup['group_id'] : null
            );
            $storedName = trim((string) (
                isset($topGroup['group_define_name'])
                    ? $topGroup['group_define_name']
                    : ''
            ));

            if ($groupId === null || $storedName === '') {
                continue;
            }

            $children = $this->directSubgroups($groupId);
            $contextTitle = $this->contextTitle($storedName);

            $records[] = array(
                'id' => $groupId,
                'name' => $contextTitle !== '' ? $contextTitle : $storedName,
                'storedName' => $storedName,
                'type' => $children ? 'context' : 'group',
                                'parentId' => null,
                'contextCode' => $children ? $storedName : '',
            );

            foreach ($children as $child) {
                $records[] = $child;
            }
        }

        return $records;
    }

    /**
     * Return normalized direct members of one group.
     */
    public function getMembers($groupId)
    {
        $groupId = $this->positiveInteger($groupId);
        if ($groupId === null) {
            return array();
        }

        /*
         * Native read path.  LiveUser getUsers() is unreliable on PHP 8.2,
         * while this schema join is already proven elsewhere in GroupAdmin.
         */
        $sql = "
            SELECT DISTINCT
                gu.perm_user_id AS id,
                pu.auth_user_id AS userid,
                us.firstname,
                us.surname,
                us.username,
                us.emailAddress,
                us.isActive
            FROM tbl_perms_groupusers AS gu
            INNER JOIN tbl_perms_perm_users AS pu
                ON gu.perm_user_id = pu.perm_user_id
            INNER JOIN tbl_users AS us
                ON pu.auth_user_id = us.userId
            WHERE gu.group_id = " . $groupId . "
            ORDER BY UPPER(us.surname), UPPER(us.firstname), UPPER(us.username)
        ";

        $rows = $this->objUser->getArray($sql);
        if (!is_array($rows)) {
            return array();
        }

        $members = array();
        $seen = array();

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $user = $this->normaliseUser($row);
            $identityKey = $this->userIdentityKey($user);

            if ($identityKey === '' || isset($seen[$identityKey])) {
                continue;
            }

            $seen[$identityKey] = true;
            $members[] = $user;
        }

        return $members;
    }

    /**
     * Return normalized users who are not direct members of one group.
     */
    public function getAvailableUsers($groupId)
    {
        $groupId = $this->positiveInteger($groupId);
        if ($groupId === null) {
            return array();
        }

        $memberKeys = array();
        foreach ($this->getMembers($groupId) as $member) {
            $key = $this->userIdentityKey($member);
            if ($key !== '') {
                $memberKeys[$key] = true;
            }
        }

        $rows = $this->objUserAdmin->getAll();
        if (!is_array($rows)) {
            return array();
        }

        $available = array();
        $seen = array();

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $user = $this->normaliseUser($row);

            if (strtolower((string) $user['status']) !== 'active') {
                continue;
            }

            $identityKey = $this->userIdentityKey($user);
            if ($identityKey === ''
                || isset($memberKeys[$identityKey])
                || isset($seen[$identityKey])) {
                continue;
            }

            $seen[$identityKey] = true;
            $available[] = $user;
        }

        return $available;
    }

    public function addMember($groupId, $userId)
    {
        $this->assertAdministrator();

        $group = $this->findGroup($groupId);
        $userId = $this->normaliseUserId($userId);

        if ($group === null) {
            return array('ok' => false, 'code' => 'group_not_found');
        }
        if ($userId === null) {
            return array('ok' => false, 'code' => 'invalid_user');
        }

        $candidate = $this->findUserById($this->getAvailableUsers($group['id']), $userId);
        if ($candidate === null) {
            return array('ok' => false, 'code' => 'user_not_available');
        }

        $permissionUserId = $this->objIdentityService->permissionUserIdForUser($userId);
        if ($permissionUserId === null) {
            return array('ok' => false, 'code' => 'permission_user_not_found');
        }

        if ($this->objMembershipDb->membershipExists($group['id'], $permissionUserId)) {
            return array('ok' => false, 'code' => 'already_member');
        }

        return $this->objMembershipDb->addMembership($group['id'], $permissionUserId)
            ? array('ok' => true, 'code' => 'member_added')
            : array('ok' => false, 'code' => 'add_failed');
    }

    public function removeMember($groupId, $userId)
    {
        $this->assertAdministrator();

        $group = $this->findGroup($groupId);
        $userId = $this->normaliseUserId($userId);

        if ($group === null) {
            return array('ok' => false, 'code' => 'group_not_found');
        }
        if ($userId === null) {
            return array('ok' => false, 'code' => 'invalid_user');
        }

        $member = $this->findUserById($this->getMembers($group['id']), $userId);
        if ($member === null) {
            return array('ok' => false, 'code' => 'not_a_member');
        }

        if (strcasecmp((string) $group['storedName'], 'Site Admin') === 0) {
            if ((string) $this->objUser->userId() === (string) $userId) {
                return array('ok' => false, 'code' => 'cannot_remove_self_admin');
            }
            if (count($this->getMembers($group['id'])) <= 1) {
                return array('ok' => false, 'code' => 'cannot_remove_last_admin');
            }
        }

        $permissionUserId = $this->objIdentityService->permissionUserIdForUser($userId);
        if ($permissionUserId === null) {
            return array('ok' => false, 'code' => 'permission_user_not_found');
        }

        return $this->objMembershipDb->removeMembership($group['id'], $permissionUserId)
            ? array('ok' => true, 'code' => 'member_removed')
            : array('ok' => false, 'code' => 'remove_failed');
    }

    private function assertAdministrator()
    {
        if (!$this->objUser->isLoggedIn() || !$this->objUser->isAdmin()) {
            throw new Exception('Administrator authorization required.');
        }
    }

    private function findGroup($groupId)
    {
        $groupId = $this->positiveInteger($groupId);
        if ($groupId === null) {
            return null;
        }

        foreach ($this->listGroups() as $group) {
            if ((int) $group['id'] === $groupId) {
                return $group;
            }
        }

        return null;
    }

    private function findUserById(array $users, $userId)
    {
        foreach ($users as $user) {
            if (isset($user['userId'])
                && (string) $user['userId'] === (string) $userId) {
                return $user;
            }
        }

        return null;
    }

    private function normaliseUserId($value)
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === ''
            || strlen($value) > 255
            || preg_match('/[\\x00-\\x1F\\x7F]/', $value)) {
            return null;
        }

        return $value;
    }

    private function directSubgroups($parentId)
    {
        $payload = $this->objGroups->getSubgroups($parentId);
        if (!is_array($payload)
            || !isset($payload[0])
            || !is_array($payload[0])) {
            return array();
        }

        $records = array();

        foreach ($payload[0] as $key => $subgroup) {
            if (!is_array($subgroup)) {
                continue;
            }

            $groupId = $this->positiveInteger(
                isset($subgroup['group_id'])
                    ? $subgroup['group_id']
                    : $key
            );
            $storedName = trim((string) (
                isset($subgroup['group_define_name'])
                    ? $subgroup['group_define_name']
                    : ''
            ));

            if ($groupId === null || $storedName === '') {
                continue;
            }

            $parts = explode('^', $storedName, 2);
            $displayName = isset($parts[1]) && trim($parts[1]) !== ''
                ? trim($parts[1])
                : $storedName;

            $records[] = array(
                'id' => $groupId,
                'name' => $displayName,
                'storedName' => $storedName,
                'type' => 'subgroup',
                                'parentId' => (int) $parentId,
                'contextCode' => isset($parts[0]) ? trim($parts[0]) : '',
            );
        }

        return $records;
    }

    private function contextTitle($contextCode)
    {
        if ($contextCode === '') {
            return '';
        }

        $title = $this->objContext->getTitle($contextCode, false);
        return is_scalar($title) ? trim((string) $title) : '';
    }

    private function userIdentityKey(array $user)
    {
        $userId = isset($user['userId']) ? trim((string) $user['userId']) : '';
        if ($userId !== '') {
            return 'id:' . strtolower($userId);
        }

        $username = isset($user['username']) ? trim((string) $user['username']) : '';
        if ($username !== '') {
            return 'username:' . strtolower($username);
        }

        $email = isset($user['email']) ? trim((string) $user['email']) : '';
        if ($email !== '') {
            return 'email:' . strtolower($email);
        }

        return '';
    }

    private function normaliseUser(array $user)
    {
        $firstName = trim((string) $this->value(
            $user,
            array('firstname', 'firstName'),
            ''
        ));
        $surname = trim((string) $this->value(
            $user,
            array('surname', 'lastname', 'lastName'),
            ''
        ));
        $username = trim((string) $this->value(
            $user,
            array('username', 'handle'),
            ''
        ));
        $displayName = trim($firstName . ' ' . $surname);

        if ($displayName === '') {
            $displayName = $username;
        }

        return array(
            'id' => $this->value($user, array('id'), null),
            'userId' => $this->value(
                $user,
                array('userid', 'userId', 'auth_user_id'),
                null
            ),
            'username' => $username,
            'displayName' => $displayName,
            'email' => trim((string) $this->value(
                $user,
                array('emailaddress', 'emailAddress', 'email'),
                ''
            )),
            'status' => $this->normaliseStatus(
                $this->value($user, array('isactive', 'isActive', 'is_active'), '')
            ),
        );
    }

    private function value(array $record, array $keys, $default)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $record)) {
                return $record[$key];
            }

            foreach ($record as $recordKey => $value) {
                if (strtolower((string) $recordKey) === strtolower($key)) {
                    return $value;
                }
            }
        }

        return $default;
    }

    private function normaliseStatus($value)
    {
        if ($value === true || $value === 1 || $value === '1') {
            return 'active';
        }
        if ($value === false || $value === 0 || $value === '0') {
            return 'inactive';
        }

        $value = trim((string) $value);
        return $value !== '' ? $value : 'unknown';
    }

    private function positiveInteger($value)
    {
        if (!is_scalar($value)
            || !preg_match('/^[1-9]\d*$/', (string) $value)) {
            return null;
        }

        return (int) $value;
    }
}
?>
