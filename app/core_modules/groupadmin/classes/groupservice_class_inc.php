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
    private $objMembershipReader;

    public function init()
    {
        // Hierarchy reads are owned directly by GroupService (p202).
        $this->objUserAdmin = $this->getObject('useradmin_model2', 'security');
        $this->objUser = $this->getObject('user', 'security');
        $this->objContext = $this->getObject('dbcontext', 'context');
        $this->objMembershipDb = $this->getObject('groupmembershipdb', 'groupadmin');
        $this->objMembershipReader = $this->getObject(
            'groupmembershipreader',
            'groupadmin'
        );
    
        $this->objIdentityService = $this->getObject('identityservice', 'security');
        $this->objSysConfig = $this->getObject(
            'dbsysconfig',
            'sysconfig'
        );

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
        $topGroups = $this->legacyTopLevelGroupRows();
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
     * Idempotently establish a validated set of canonical groups.
     *
     * Installation policy is supplied by the caller. GroupService owns only
     * definition validation and writes to the canonical permissions tables.
     *
     * @param array $definitions Declarative group definitions.
     * @return array Structured provisioning result.
     */
    /**
     * Ensure one direct parent-child relationship between canonical groups.
     */
    public function ensureSubgroup($groupId, $subgroupId)
    {
        $groupId = $this->positiveInteger($groupId);
        $subgroupId = $this->positiveInteger($subgroupId);
        if ($groupId === null || $subgroupId === null
            || $groupId === $subgroupId) {
            return false;
        }

        $rows = $this->objUser->getArray(
            'SELECT group_id, subgroup_id'
            . ' FROM tbl_perms_group_subgroups'
            . ' WHERE group_id = ' . $groupId
            . ' AND subgroup_id = ' . $subgroupId
            . ' LIMIT 2'
        );
        if (!is_array($rows) || count($rows) > 1) {
            return false;
        }
        if (count($rows) === 1) {
            return true;
        }
        $inserted = $this->objUser->_execute(
            'INSERT INTO tbl_perms_group_subgroups (group_id, subgroup_id)'
            . ' VALUES (' . $groupId . ', ' . $subgroupId . ')'
        );
        if ($inserted === false) {
            return false;
        }
        $rows = $this->objUser->getArray(
            'SELECT group_id, subgroup_id'
            . ' FROM tbl_perms_group_subgroups'
            . ' WHERE group_id = ' . $groupId
            . ' AND subgroup_id = ' . $subgroupId
            . ' LIMIT 2'
        );
        return is_array($rows) && count($rows) === 1;
    }

    public function ensureGroups($definitions)
    {
        if (!is_array($definitions) || count($definitions) === 0) {
            return array('ok' => false, 'code' => 'group_definitions_empty');
        }

        $validated = array();
        $seen = array();
        foreach ($definitions as $definition) {
            if (!is_array($definition)
                || !isset($definition['name'])
                || !is_scalar($definition['name'])) {
                return array('ok' => false, 'code' => 'group_definition_invalid');
            }

            $name = trim((string) $definition['name']);
            $description = isset($definition['description'])
                && is_scalar($definition['description'])
                ? trim((string) $definition['description'])
                : '';
            if ($name === ''
                || strlen($name) > 255
                || preg_match('/[\x00-\x1F\x7F]/', $name)
                || strlen($description) > 255
                || preg_match('/[\x00-\x1F\x7F]/', $description)) {
                return array(
                    'ok' => false,
                    'code' => 'group_definition_invalid',
                    'group' => $name,
                );
            }

            $key = strtolower($name);
            if (isset($seen[$key])) {
                return array(
                    'ok' => false,
                    'code' => 'group_definition_duplicate',
                    'group' => $name,
                );
            }
            $seen[$key] = true;
            $validated[] = array(
                'name' => $name,
                'description' => $description,
            );
        }

        $groupIds = array();
        foreach ($validated as $definition) {
            $result = $this->ensureCanonicalGroup(
                $definition['name'],
                $definition['description']
            );
            if (!is_array($result) || empty($result['ok'])) {
                return is_array($result)
                    ? $result
                    : array(
                        'ok' => false,
                        'code' => 'group_provisioning_failure',
                        'group' => $definition['name'],
                    );
            }
            $groupIds[$definition['name']] = $result['groupId'];
        }

        return array(
            'ok' => true,
            'code' => 'groups_ready',
            'groups' => $groupIds,
        );
    }

    /**
     * Establish one exact-name group with a valid canonical identifier.
     *
     * @param string $groupName
     * @param string $description
     * @return array Structured provisioning result.
     */
    /**
     * Ensure one direct canonical membership without invoking LiveUser writes.
     */
    public function ensureMembership($groupId, $permissionUserId)
    {
        return $this->objMembershipDb->ensureMembership(
            $groupId,
            $permissionUserId
        );
    }

    /**
     * Idempotently remove one direct canonical membership.
     *
     * Authorization belongs to the calling application boundary, just as it
     * does for ensureMembership(). This method owns only the canonical write.
     */
    public function removeMembership($groupId, $permissionUserId)
    {
        // CHISIMBA_GROUPSERVICE_IDEMPOTENT_REMOVE
        if (!$this->objMembershipDb->membershipExists(
            $groupId,
            $permissionUserId
        )) {
            return true;
        }

        return $this->objMembershipDb->removeMembership(
            $groupId,
            $permissionUserId
        );
    }

    /**
     * Create one ordinary administrator-defined canonical group.
     *
     * The description is accepted for compatibility but remains metadata:
     * tbl_perms_groups has no description column. If a parent is supplied,
     * creation and the subgroup relationship are one transaction.
     */
    /**
     * Create one canonical group with a controlled namespaced identifier.
     *
     * The namespace and local name are validated independently. The caret is
     * introduced only by this method for compatibility with active Chisimba
     * identifiers; ordinary createGroup() remains deliberately strict.
     */
    public function createNamespacedGroup($namespace, $localName,
        $description = null, $parentId = null)
    {
        $this->assertAdministrator();
        $namespace = trim((string) $namespace);
        $localName = trim((string) $localName);
        if (!$this->validRenameName($namespace)
            || !$this->validRenameName($localName)) {
            return array('ok' => false, 'code' => 'group_namespace_invalid');
        }

        $groupName = $namespace . '^' . $localName;
        if (strlen($groupName) > 255) {
            return array('ok' => false, 'code' => 'group_name_invalid');
        }
        $rows = $this->exactGroupRows($groupName);
        if ($rows === false) {
            return array('ok' => false, 'code' => 'group_lookup_failed');
        }
        if (count($rows) !== 0) {
            return array('ok' => false, 'code' => 'group_already_exists');
        }

        if ($parentId !== null) {
            $parentId = $this->positiveInteger($parentId);
            if ($parentId === null || $this->findGroup($parentId) === null) {
                return array('ok' => false, 'code' => 'parent_group_not_found');
            }
        }

        $this->objUser->beginTransaction();
        try {
            $created = $this->ensureCanonicalGroup($groupName, $description);
            if (!is_array($created) || empty($created['ok'])
                || empty($created['groupId'])) {
                $code = is_array($created) && isset($created['code'])
                    ? $created['code'] : 'group_create_failed';
                throw new Exception($code);
            }
            $groupId = $this->positiveInteger($created['groupId']);
            if ($groupId === null) {
                throw new Exception('group_identifier_failed');
            }
            if ($parentId !== null && !$this->ensureSubgroup($parentId, $groupId)) {
                throw new Exception('subgroup_assign_failed');
            }
            $verify = $this->exactGroupRows($groupName);
            if (!is_array($verify) || count($verify) !== 1
                || $this->positiveInteger($verify[0]['group_id']) !== $groupId
                || $this->positiveInteger($verify[0]['puid']) !== $groupId) {
                throw new Exception('group_create_verify_failed');
            }
            $this->objUser->commitTransaction();
            return array('ok' => true, 'code' => 'group_created', 'groupId' => $groupId);
        } catch (Exception $exception) {
            $this->objUser->rollbackTransaction();
            return array('ok' => false, 'code' => $exception->getMessage());
        }
    }

    public function createGroup($groupName, $description = null, $parentId = null)
    {
        $this->assertAdministrator();
        $groupName = trim((string) $groupName);
        if (!$this->validRenameName($groupName)) {
            return array('ok' => false, 'code' => 'group_name_invalid');
        }

        $rows = $this->exactGroupRows($groupName);
        if ($rows === false) {
            return array('ok' => false, 'code' => 'group_lookup_failed');
        }
        if (count($rows) !== 0) {
            return array('ok' => false, 'code' => 'group_already_exists');
        }

        if ($parentId !== null) {
            $parentId = $this->positiveInteger($parentId);
            if ($parentId === null || $this->findGroup($parentId) === null) {
                return array('ok' => false, 'code' => 'parent_group_not_found');
            }
        }

        $this->objUser->beginTransaction();
        try {
            $created = $this->ensureCanonicalGroup($groupName, $description);
            if (!is_array($created) || empty($created['ok'])
                || empty($created['groupId'])) {
                $code = is_array($created) && isset($created['code'])
                    ? $created['code'] : 'group_create_failed';
                throw new Exception($code);
            }

            $groupId = $this->positiveInteger($created['groupId']);
            if ($groupId === null) {
                throw new Exception('group_identifier_failed');
            }
            if ($parentId !== null && !$this->ensureSubgroup($parentId, $groupId)) {
                throw new Exception('subgroup_assign_failed');
            }

            $verify = $this->exactGroupRows($groupName);
            if (!is_array($verify) || count($verify) !== 1
                || $this->positiveInteger($verify[0]['group_id']) !== $groupId
                || $this->positiveInteger($verify[0]['puid']) !== $groupId) {
                throw new Exception('group_create_verify_failed');
            }

            $this->objUser->commitTransaction();
            return array(
                'ok' => true,
                'code' => 'group_created',
                'groupId' => $groupId,
            );
        } catch (Exception $exception) {
            $this->objUser->rollbackTransaction();
            return array('ok' => false, 'code' => $exception->getMessage());
        }
    }

    /**
     * Delete one ordinary canonical group and its owned canonical relations.
     *
     * Child groups make deletion fail explicitly. Legacy tbl_permissions_acl
     * rows are deliberately preserved until their mixed identifier contract
     * is migrated into the canonical permission architecture.
     */
    public function deleteGroup($groupId)
    {
        $this->assertAdministrator();
        $groupId = $this->positiveInteger($groupId);
        if ($groupId === null) {
            return array('ok' => false, 'code' => 'group_id_invalid');
        }

        $group = $this->findGroup($groupId);
        if ($group === null) {
            return array('ok' => false, 'code' => 'group_not_found');
        }
        if (strcasecmp((string) $group['storedName'], 'Site Admin') === 0) {
            return array('ok' => false, 'code' => 'protected_group');
        }

        $children = $this->objUser->getArray(
            'SELECT subgroup_id FROM tbl_perms_group_subgroups'
            . ' WHERE group_id = ' . $groupId
        );
        if (!is_array($children)) {
            return array('ok' => false, 'code' => 'group_children_lookup_failed');
        }
        if (count($children) !== 0) {
            return array('ok' => false, 'code' => 'group_has_children');
        }

        $this->objUser->beginTransaction();
        try {
            $this->objUser->_execute(
                'DELETE FROM tbl_perms_groupusers WHERE group_id = ' . $groupId
            );
            $this->objUser->_execute(
                'DELETE FROM tbl_perms_group_subgroups'
                . ' WHERE subgroup_id = ' . $groupId
            );
            $this->objUser->_execute(
                'DELETE FROM tbl_perms_grouprights WHERE group_id = ' . $groupId
            );
            $this->objUser->_execute(
                'DELETE FROM tbl_perms_groups WHERE group_id = ' . $groupId
            );

            $remaining = $this->objUser->getArray(
                'SELECT group_id FROM tbl_perms_groups WHERE group_id = ' . $groupId
            );
            if (!is_array($remaining) || count($remaining) !== 0) {
                throw new Exception('group_delete_verify_failed');
            }
            $relations = $this->objUser->getArray(
                'SELECT group_id FROM tbl_perms_groupusers WHERE group_id = ' . $groupId
                . ' UNION ALL SELECT subgroup_id FROM tbl_perms_group_subgroups'
                . ' WHERE subgroup_id = ' . $groupId
                . ' UNION ALL SELECT group_id FROM tbl_perms_grouprights'
                . ' WHERE group_id = ' . $groupId
            );
            if (!is_array($relations) || count($relations) !== 0) {
                throw new Exception('group_relations_remain');
            }

            $this->objUser->commitTransaction();
            return array('ok' => true, 'code' => 'group_deleted');
        } catch (Exception $exception) {
            $this->objUser->rollbackTransaction();
            return array('ok' => false, 'code' => $exception->getMessage());
        }
    }

    private function ensureCanonicalGroup($groupName, $description)
    {
        $rows = $this->exactGroupRows($groupName);
        if ($rows === false) {
            return array(
                'ok' => false,
                'code' => 'group_lookup_failed',
                'group' => $groupName,
            );
        }

        if (count($rows) === 0) {
            /*
             * GroupService is the sole writer for canonical permission
             * groups. Encode the already validated UTF-8 name as hexadecimal
             * data so it cannot alter SQL syntax. The generated puid is read
             * back below and becomes the canonical group_id.
             *
             * The manifest description remains installation metadata because
             * tbl_perms_groups has no description column.
             */
            $encodedName = bin2hex($groupName);
            if ($encodedName === '') {
                return array(
                    'ok' => false,
                    'code' => 'group_create_failed',
                    'group' => $groupName,
                );
            }
            $this->objUser->_execute(
                "INSERT INTO tbl_perms_groups"
                . " (group_type, group_define_name)"
                . " VALUES (1, UNHEX('" . $encodedName . "'))"
            );
            $rows = $this->exactGroupRows($groupName);
        }

        if (!is_array($rows) || count($rows) !== 1) {
            return array(
                'ok' => false,
                'code' => 'group_duplicate',
                'group' => $groupName,
            );
        }

        $puid = isset($rows[0]['puid'])
            ? $this->positiveInteger($rows[0]['puid'])
            : null;
        $groupId = isset($rows[0]['group_id'])
            ? $this->positiveInteger($rows[0]['group_id'])
            : null;
        if ($puid === null) {
            return array(
                'ok' => false,
                'code' => 'group_row_malformed',
                'group' => $groupName,
            );
        }

        if ($groupId === null) {
            $this->objUser->_execute(
                'UPDATE tbl_perms_groups SET group_id = ' . $puid
                . ' WHERE puid = ' . $puid . ' AND group_id IS NULL'
            );
            $rows = $this->exactGroupRows($groupName);
            $groupId = is_array($rows)
                && count($rows) === 1
                && isset($rows[0]['group_id'])
                ? $this->positiveInteger($rows[0]['group_id'])
                : null;
        }

        if ($groupId === null || $groupId !== $puid) {
            return array(
                'ok' => false,
                'code' => 'group_identifier_failed',
                'group' => $groupName,
            );
        }

        return array(
            'ok' => true,
            'code' => 'group_ready',
            'groupId' => $groupId,
        );
    }

    /**
     * Return every exact-name row required for duplicate-safe provisioning.
     *
     * @param string $groupName
     * @return array|boolean Rows, or false on database failure.
     */
    private function exactGroupRows($groupName)
    {
        if (!is_scalar($groupName)) {
            return false;
        }
        $groupName = trim((string) $groupName);
        if ($groupName === ''
            || strlen($groupName) > 255
            || preg_match('/[\x00-\x1F\x7F]/', $groupName)) {
            return false;
        }

        $rows = $this->objUser->getArray(
            'SELECT puid, group_id, group_define_name'
            . ' FROM tbl_perms_groups'
        );
        if (!is_array($rows)) {
            return false;
        }

        $matches = array();
        foreach ($rows as $row) {
            if (is_array($row)
                && isset($row['group_define_name'])
                && (string) $row['group_define_name'] === $groupName) {
                $matches[] = $row;
            }
        }
        return $matches;
    }

    /**
     * Resolve an exact stored group name to its canonical group identifier.


     *
     * GroupService owns this table lookup and normalizes the legacy database
     * contract. Missing, duplicate or malformed matches return false.
     *
     * @param string $groupName
     * @return integer|boolean Positive group identifier, or false.
     */
    /**
     * Return the exact row shape required by the legacy unfiltered facade.
     *
     * This is a compatibility read contract. GroupService owns the database
     * read and does not call back into groupadminmodel.
     */
    /**
     * Return descendant group rows in deterministic breadth-first order.
     *
     * This compatibility read is owned entirely by GroupService. It returns
     * FALSE when the identifier is invalid, missing, or has no descendants.
     */
    public function legacySubgroupRows($parentId)
    {
        if (!is_scalar($parentId) || !preg_match('/^[0-9]+$/', (string) $parentId)) {
            return false;
        }
        $parentId = (string) $parentId;
        if ($this->legacyStoredNameForGroupId($parentId) === null) {
            return false;
        }
        $queue = array($parentId);
        $seen = array($parentId => true);
        $result = array();
        while (!empty($queue)) {
            $current = array_shift($queue);
            $rows = $this->objUser->getArray(
                'SELECT g.group_id, g.group_type, g.group_define_name'
                . ' FROM tbl_perms_group_subgroups r'
                . ' INNER JOIN tbl_perms_groups g ON g.group_id=r.subgroup_id'
                . ' WHERE r.group_id=' . (int) $current
                . ' ORDER BY r.subgroup_id'
            );
            if (!is_array($rows)) {
                return false;
            }
            foreach ($rows as $row) {
                $childId = (string) $row['group_id'];
                if (isset($seen[$childId])) {
                    continue;
                }
                $seen[$childId] = true;
                $result[] = $row;
                $queue[] = $childId;
            }
        }
        return empty($result) ? false : $result;
    }

    /**
     * Return the direct parent's stored group name, or NULL.
     */
    public function legacyParentStoredName($subgroupId)
    {
        if (!is_scalar($subgroupId) || !preg_match('/^[0-9]+$/', (string) $subgroupId)) {
            return null;
        }
        $rows = $this->objUser->getArray(
            'SELECT g.group_define_name'
            . ' FROM tbl_perms_group_subgroups r'
            . ' INNER JOIN tbl_perms_groups g ON g.group_id=r.group_id'
            . ' WHERE r.subgroup_id=' . (int) $subgroupId
            . ' ORDER BY r.group_id LIMIT 1'
        );
        if (!is_array($rows) || empty($rows) || !isset($rows[0]['group_define_name'])) {
            return null;
        }
        return (string) $rows[0]['group_define_name'];
    }

    /**
     * Return groups which are not registered as subgroups.
     *
     * This compatibility-shaped read is owned by GroupService and preserves
     * the deterministic fields and ordering used by the canonical group list.
     */
    public function legacyTopLevelGroupRows()
    {
        $rows = $this->objUser->getArray(
            'SELECT g.group_id, g.group_type, g.group_define_name'
            . ' FROM tbl_perms_groups g'
            . ' WHERE NOT EXISTS ('
            . 'SELECT 1 FROM tbl_perms_group_subgroups r'
            . ' WHERE r.subgroup_id=g.group_id)'
            . ' ORDER BY g.group_id'
        );
        return is_array($rows) ? $rows : false;
    }

    public function legacyGroupRows()
    {
        $rows = $this->objUser->getArray(
            'SELECT group_id, group_type, group_define_name'
            . ' FROM tbl_perms_groups'
        );
        if (!is_array($rows) || empty($rows)) {
            return false;
        }
        return $rows;
    }

    /**
     * Return a stored group name's legacy string identifier, or NULL.
     */
    public function legacyGroupIdForStoredName($groupName)
    {
        if (!is_scalar($groupName)) {
            return null;
        }
        $groupName = (string) $groupName;
        $rows = $this->legacyGroupRows();
        if (!is_array($rows)) {
            return null;
        }
        foreach ($rows as $row) {
            if (isset($row['group_define_name'], $row['group_id'])
                && (string) $row['group_define_name'] === $groupName) {
                return (string) $row['group_id'];
            }
        }
        return null;
    }

    /**
     * Return an identifier's stored group name, or NULL.
     */
    public function legacyStoredNameForGroupId($groupId)
    {
        if (!is_scalar($groupId)) {
            return null;
        }
        $groupId = (string) $groupId;
        $rows = $this->legacyGroupRows();
        if (!is_array($rows)) {
            return null;
        }
        foreach ($rows as $row) {
            if (isset($row['group_id'], $row['group_define_name'])
                && (string) $row['group_id'] === $groupId) {
                return (string) $row['group_define_name'];
            }
        }
        return null;
    }

    public function groupIdForName($groupName)
    {
        if (!is_scalar($groupName)) {
            return false;
        }

        $groupName = trim((string) $groupName);
        if ($groupName === ''
            || strlen($groupName) > 255
            || preg_match('/[\x00-\x1F\x7F]/', $groupName)) {
            return false;
        }

        /*
         * Fetch the two owned columns and compare the validated scalar in PHP.
         * This avoids depending on an unavailable quoting helper or on the
         * legacy group model's inconsistent name-lookup return contract.
         */
        $rows = $this->objUser->getArray(
            'SELECT group_id, group_define_name FROM tbl_perms_groups'
        );
        if (!is_array($rows)) {
            return false;
        }

        $matches = array();
        foreach ($rows as $row) {
            if (is_array($row)
                && isset($row['group_define_name'])
                && (string) $row['group_define_name'] === $groupName) {
                $matches[] = $row;
            }
        }

        if (count($matches) !== 1 || !isset($matches[0]['group_id'])) {
            return false;
        }

        $groupId = $this->positiveInteger($matches[0]['group_id']);

        return $groupId === null ? false : $groupId;
    }

    /**
     * Determine whether a logical user belongs to a group.
     *
     * @param mixed $userId
     * @param mixed $groupId
     * @return boolean
     */
    public function isGroupMember($userId, $groupId)
    {
        return $this->objMembershipReader->isGroupMember($userId, $groupId);
    }

    /**
     * Return normalized direct members of one group.
     *
     * @param mixed $groupId
     * @return array
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

        $permissionUserId = $this->objIdentityService->permissionUserIdForUser($userId);
        if ($permissionUserId === null) {
            return array('ok' => false, 'code' => 'permission_user_not_found');
        }

        if ($this->objMembershipDb->membershipExists($group['id'], $permissionUserId)) {
            return array('ok' => false, 'code' => 'already_member');
        }

        $candidate = $this->findUserById($this->getAvailableUsers($group['id']), $userId);
        if ($candidate === null) {
            return array('ok' => false, 'code' => 'user_not_available');
        }

        return $this->objMembershipDb->addMembership($group['id'], $permissionUserId)
            ? array('ok' => true, 'code' => 'member_added')
            : array('ok' => false, 'code' => 'add_failed');
    }

    /**
     * Determine whether one logical user has any direct group membership.
     *
     * This guard belongs here because GroupService owns membership data.
     */
    public function hasAnyMembership($userId)
    {
        $userId = $this->normaliseUserId($userId);
        if ($userId === null) {
            return true;
        }

        $permissionUserId = $this->objIdentityService
            ->permissionUserIdForUser($userId);
        if ($permissionUserId === null) {
            return false;
        }

        $rows = $this->objUser->getArray(
            'SELECT group_id FROM tbl_perms_groupusers'
            . ' WHERE perm_user_id = ' . (int) $permissionUserId
            . ' LIMIT 1'
        );

        return is_array($rows) && count($rows) > 0;
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

    /**
     * Add the first administrator to a manifest-required bootstrap group.
     *
     * This is not an unauthenticated group-management API. It is available
     * only before first registration completes, only for canonical user 1,
     * and only for the Guest and Site Admin groups.
     */
    public function addBootstrapMember($groupId, $userId, $groupName)
    {
        $guard = $this->validateBootstrapMembership(
            $groupId,
            $userId,
            $groupName
        );
        if ($guard !== null) {
            return $guard;
        }
        return $this->addBootstrapMemberRecord($groupId, $userId);
    }

    /**
     * Compensate a bootstrap membership created during failed provisioning.
     */
    public function removeBootstrapMember($groupId, $userId, $groupName)
    {
        $guard = $this->validateBootstrapMembership(
            $groupId,
            $userId,
            $groupName
        );
        if ($guard !== null) {
            return $guard;
        }
        return $this->removeBootstrapMemberRecord($groupId, $userId);
    }

    private function validateBootstrapMembership($groupId, $userId, $groupName)
    {
        $completed = $this->objSysConfig->getValue(
            'firstreg_run',
            'modulecatalogue'
        );
        if (in_array(strtolower(trim((string) $completed)),
            array('1', 'true', 'yes', 'on'), true)) {
            return array('ok' => false, 'code' => 'bootstrap_closed');
        }
        if ((string) $userId !== '1') {
            return array('ok' => false, 'code' => 'invalid_bootstrap_user');
        }
        $groupName = trim((string) $groupName);
        if (!in_array($groupName, array('Guest', 'Site Admin'), true)) {
            return array('ok' => false, 'code' => 'invalid_bootstrap_group');
        }
        $group = $this->findGroup($groupId);
        if (!is_array($group)
            || !isset($group['name'])
            || (string) $group['name'] !== $groupName) {
            return array('ok' => false, 'code' => 'bootstrap_group_mismatch');
        }
        if ($this->objIdentityService->permissionUserIdForUser('1') === null) {
            return array('ok' => false, 'code' => 'bootstrap_identity_missing');
        }
        return null;
    }



    private function addBootstrapMemberRecord($groupId, $userId)
    {

        $group = $this->findGroup($groupId);
        $userId = $this->normaliseUserId($userId);

        if ($group === null) {
            return array('ok' => false, 'code' => 'group_not_found');
        }
        if ($userId === null) {
            return array('ok' => false, 'code' => 'invalid_user');
        }

        $permissionUserId = $this->objIdentityService->permissionUserIdForUser($userId);
        if ($permissionUserId === null) {
            return array('ok' => false, 'code' => 'permission_user_not_found');
        }

        if ($this->objMembershipDb->membershipExists($group['id'], $permissionUserId)) {
            return array('ok' => false, 'code' => 'already_member');
        }

        $candidate = $this->findUserById($this->getAvailableUsers($group['id']), $userId);
        if ($candidate === null) {
            return array('ok' => false, 'code' => 'user_not_available');
        }

        return $this->objMembershipDb->addMembership($group['id'], $permissionUserId)
            ? array('ok' => true, 'code' => 'member_added')
            : array('ok' => false, 'code' => 'add_failed');

    }

    private function removeBootstrapMemberRecord($groupId, $userId)
    {

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
        $rows = $this->objUser->getArray(
            'SELECT group_id, group_type, group_define_name'
            . ' FROM tbl_perms_groups WHERE group_id = ' . (int) $groupId
        );
        if (!is_array($rows) || count($rows) !== 1) {
            return null;
        }

        $row = $rows[0];
        return array(
            'id' => (string) $row['group_id'],
            'type' => (string) $row['group_type'],
            'name' => (string) $row['group_define_name'],
            'storedName' => (string) $row['group_define_name'],
        );
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
        $payload = $this->legacySubgroupRows($parentId);
        if (!is_array($payload)) {
            return array();
        }

        $records = array();

        foreach ($payload as $key => $subgroup) {
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


    /**
     * Atomically rename one ordinary canonical group and its descendants.
     * Context-backed groups must be rejected by the coordinating consumer.
     */
    public function renameGroupHierarchy($groupId, $oldName, $newName)
    {
        $this->assertAdministrator();
        $groupId = $this->positiveInteger($groupId);
        $oldName = trim((string) $oldName);
        $newName = trim((string) $newName);
        if ($groupId === null || !$this->validRenameName($oldName)
            || !$this->validRenameName($newName) || $oldName === $newName) {
            return array('ok' => false, 'code' => 'invalid_group_rename');
        }

        $rows = $this->objUser->getArray(
            'SELECT group_id, group_define_name FROM tbl_perms_groups'
        );
        if (!is_array($rows)) {
            return array('ok' => false, 'code' => 'group_read_failed');
        }
        $source = array();
        $targetNames = array();
        foreach ($rows as $row) {
            $name = isset($row['group_define_name'])
                ? (string) $row['group_define_name'] : '';
            if ($name === $newName || strpos($name, $newName . '^') === 0) {
                $targetNames[$name] = true;
            }
            if ($name === $oldName || strpos($name, $oldName . '^') === 0) {
                $source[] = $row;
            }
        }
        $topLevelMatches = 0;
        foreach ($source as $row) {
            if ((string) $row['group_define_name'] === $oldName
                && (int) $row['group_id'] === $groupId) {
                $topLevelMatches++;
            }
        }
        if ($topLevelMatches !== 1) {
            return array('ok' => false, 'code' => 'group_source_mismatch');
        }
        foreach ($source as $row) {
            $old = (string) $row['group_define_name'];
            $replacement = $newName . substr($old, strlen($oldName));
            if (isset($targetNames[$replacement])) {
                return array('ok' => false, 'code' => 'group_target_exists');
            }
        }

        $this->objUser->beginTransaction();
        try {
            foreach ($source as $row) {
                $old = (string) $row['group_define_name'];
                $replacement = $newName . substr($old, strlen($oldName));
                $this->objUser->_execute(
                    'UPDATE tbl_perms_groups SET group_define_name = UNHEX(\''
                    . bin2hex($replacement) . '\') WHERE group_id = '
                    . (int) $row['group_id'] . ' AND group_define_name = UNHEX(\''
                    . bin2hex($old) . '\')'
                );
            }

            $verify = $this->objUser->getArray(
                'SELECT group_id, group_define_name FROM tbl_perms_groups'
            );
            if (!is_array($verify)) {
                throw new Exception('group_verify_failed');
            }
            $renamed = 0;
            foreach ($verify as $row) {
                $name = isset($row['group_define_name'])
                    ? (string) $row['group_define_name'] : '';
                if ($name === $oldName || strpos($name, $oldName . '^') === 0) {
                    throw new Exception('group_old_name_remains');
                }
                if ($name === $newName || strpos($name, $newName . '^') === 0) {
                    $renamed++;
                }
            }
            if ($renamed !== count($source)) {
                throw new Exception('group_verify_mismatch');
            }
            $this->objUser->commitTransaction();
            return array('ok' => true, 'code' => 'group_hierarchy_renamed');
        } catch (Exception $exception) {
            $this->objUser->rollbackTransaction();
            return array('ok' => false, 'code' => $exception->getMessage());
        }
    }

    private function validRenameName($name)
    {
        return $name !== '' && strpos($name, '^') === false
            && strlen($name) <= 255
            && !preg_match('/[\\x00-\\x1F\\x7F]/', $name);
    }

}
?>
