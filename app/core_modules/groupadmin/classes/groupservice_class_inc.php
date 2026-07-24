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

    public function init()
    {
        $this->objGroups = $this->getObject('groupadminmodel', 'groupadmin');
        $this->objUserAdmin = $this->getObject('useradmin_model2', 'security');
        $this->objUser = $this->getObject('user', 'security');
        $this->objContext = $this->getObject('dbcontext', 'context');
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
                'typeLabel' => $children ? 'Context' : 'Site group',
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

        $memberships = $this->objGroups->getGroupUsers($groupId);
        if (!is_array($memberships)) {
            return array();
        }

        $records = array();
        $seen = array();

        foreach ($memberships as $membership) {
            if (!is_array($membership)
                || empty($membership['auth_user_id'])) {
                continue;
            }

            $authUserId = (string) $membership['auth_user_id'];
            if (isset($seen[$authUserId])) {
                continue;
            }

            $user = $this->objUser->getUserDetails($authUserId);
            if (!is_array($user) || !$user) {
                continue;
            }

            $records[] = $this->normaliseUser($user);
            $seen[$authUserId] = true;
        }

        return $records;
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

        $memberIds = array();
        $memberships = $this->objGroups->getGroupUsers($groupId);
        if (is_array($memberships)) {
            foreach ($memberships as $membership) {
                if (is_array($membership)
                    && isset($membership['auth_user_id'])) {
                    $memberIds[(string) $membership['auth_user_id']] = true;
                }
            }
        }

        $users = $this->objUserAdmin->getUsers(
            'listall',
            'firstname',
            'surname',
            true
        );
        if (!is_array($users)) {
            return array();
        }

        $records = array();
        foreach ($users as $user) {
            if (!is_array($user)) {
                continue;
            }

            $authUserId = isset($user['userid'])
                ? (string) $user['userid']
                : '';

            if ($authUserId === '' || isset($memberIds[$authUserId])) {
                continue;
            }

            $records[] = $this->normaliseUser($user);
        }

        return $records;
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
                'typeLabel' => 'Context group',
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
            $displayName = $username !== '' ? $username : 'Unnamed user';
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
            return 'Active';
        }
        if ($value === false || $value === 0 || $value === '0') {
            return 'Inactive';
        }

        $value = trim((string) $value);
        return $value !== '' ? $value : 'Unknown';
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
