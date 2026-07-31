<?php
/**
 * Canonical permission grant storage and evaluation.
 *
 * This service is the sole application boundary for tbl_perms_rights,
 * tbl_perms_userrights and tbl_perms_grouprights. Public callers use logical
 * tbl_users.userid values; permission identity keys remain internal.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
class permissionservice extends dbTable
{
    protected $objIdentityService;

    public function init(
        $tableName = null,
        $pearDb = null,
        $errorCallback = 'globalPearErrorHandler'
    ) {
        parent::init(
            $tableName !== null ? $tableName : 'tbl_perms_rights',
            $pearDb,
            $errorCallback
        );
        $this->objIdentityService = $this->getObject(
            'identityservice',
            'security'
        );
    }

    /**
     * Resolve one right name within one explicit canonical area.
     *
     * Missing, duplicate or malformed definitions fail closed.
     *
     * @return int|null
     */
    public function rightIdForArea($areaId, $rightDefineName)
    {
        $areaId = $this->positiveInteger($areaId);
        $rightDefineName = $this->normaliseRightName($rightDefineName);
        if ($areaId === null || $rightDefineName === null) {
            return null;
        }

        $rows = $this->getArray(
            'SELECT right_id, right_define_name FROM tbl_perms_rights'
            . ' WHERE area_id = ' . $areaId
        );
        if (!is_array($rows)) {
            return null;
        }

        $matches = array();
        foreach ($rows as $row) {
            if (is_array($row)
                && isset($row['right_define_name'])
                && (string) $row['right_define_name'] === $rightDefineName) {
                $matches[] = $row;
            }
        }
        if (count($matches) !== 1 || !isset($matches[0]['right_id'])) {
            return null;
        }

        return $this->positiveInteger($matches[0]['right_id']);
    }

    /**
     * Resolve one canonical area by application and definition name.
     *
     * Missing or duplicate definitions fail closed.
     *
     * @return int|null
     */
    public function areaIdForName($applicationId, $areaDefineName)
    {
        $applicationId = $this->normaliseApplicationId($applicationId);
        $areaDefineName = $this->normaliseAreaName($areaDefineName);
        if ($applicationId === null || $areaDefineName === null) {
            return null;
        }

        $rows = $this->getArray(
            'SELECT area_id FROM tbl_perms_areas'
            . ' WHERE application_id = ' . $this->quoteValue($applicationId)
            . ' AND area_define_name = ' . $this->quoteValue($areaDefineName)
            . ' LIMIT 2'
        );
        if (!is_array($rows) || count($rows) !== 1
            || !isset($rows[0]['area_id'])) {
            return null;
        }

        return $this->positiveInteger($rows[0]['area_id']);
    }

    /**
     * Return an existing canonical area or create it idempotently.
     *
     * @return int|null
     */
    public function ensureArea($applicationId, $areaDefineName)
    {
        $applicationId = $this->normaliseApplicationId($applicationId);
        $areaDefineName = $this->normaliseAreaName($areaDefineName);
        if ($applicationId === null || $areaDefineName === null) {
            return null;
        }

        $existing = $this->areaIdForName($applicationId, $areaDefineName);
        if ($existing !== null) {
            return $existing;
        }

        $areaId = $this->nextIntegerId('tbl_perms_areas', 'area_id');
        if ($areaId === null) {
            return null;
        }

        if (!$this->insertInto(
            'tbl_perms_areas',
            array(
                'id' => $this->definitionRecordId(
                    'area',
                    $applicationId,
                    $areaDefineName
                ),
                'application_id' => $applicationId,
                'area_id' => $areaId,
                'area_define_name' => $areaDefineName,
            )
        )) {
            return $this->areaIdForName($applicationId, $areaDefineName);
        }

        return $this->areaIdForName($applicationId, $areaDefineName);
    }

    /**
     * Return an existing right or define it idempotently within one area.
     *
     * Any supplied administrator group receives the new or existing right.
     *
     * @return int|null
     */
    public function ensureRight(
        $areaId,
        $rightDefineName,
        $administratorGroupId = null
    ) {
        $areaId = $this->positiveInteger($areaId);
        $rightDefineName = $this->normaliseRightName($rightDefineName);
        if ($areaId === null
            || $rightDefineName === null
            || !$this->areaExistsExactlyOnce($areaId)) {
            return null;
        }

        $rightId = $this->rightIdForArea($areaId, $rightDefineName);
        if ($rightId === null) {
            $rightId = $this->nextIntegerId(
                'tbl_perms_rights',
                'right_id'
            );
            if ($rightId === null
                || !$this->insertInto(
                    'tbl_perms_rights',
                    array(
                        'id' => $this->definitionRecordId(
                            'right',
                            $areaId,
                            $rightDefineName
                        ),
                        'area_id' => $areaId,
                        'right_id' => $rightId,
                        'right_define_name' => $rightDefineName,
                        'has_implied' => '',
                    )
                )) {
                $rightId = $this->rightIdForArea(
                    $areaId,
                    $rightDefineName
                );
            }
        }

        if ($rightId === null) {
            return null;
        }

        if ($administratorGroupId !== null
            && !$this->ensureGroupGrant($administratorGroupId, $rightId)) {
            return null;
        }

        return $rightId;
    }

    /**
     * List the rights defined for one canonical area.
     */
    public function rightsForArea($areaId)
    {
        $areaId = $this->positiveInteger($areaId);
        if ($areaId === null || !$this->areaExistsExactlyOnce($areaId)) {
            return array();
        }

        $rows = $this->getArray(
            'SELECT right_id, right_define_name, has_implied'
            . ' FROM tbl_perms_rights'
            . ' WHERE area_id = ' . $areaId
            . ' ORDER BY right_define_name, right_id'
        );
        if (!is_array($rows)) {
            return array();
        }

        $rights = array();
        foreach ($rows as $row) {
            if (!is_array($row)
                || !isset($row['right_id'], $row['right_define_name'])) {
                return array();
            }
            $rightId = $this->positiveInteger($row['right_id']);
            $rightName = $this->normaliseRightName(
                $row['right_define_name']
            );
            if ($rightId === null || $rightName === null) {
                return array();
            }
            $rights[] = array(
                'rightId' => $rightId,
                'name' => $rightName,
                'hasImplied' => isset($row['has_implied'])
                    ? (string) $row['has_implied']
                    : '',
            );
        }

        return $rights;
    }

    /**
     * Idempotently record and materialize a grant for a contextual role.
     *
     * The template is canonical permission policy. Concrete grants are made
     * only to exact context-role groups such as biology101^Lecturers.
     */
    public function ensureContextRoleGrantTemplate($rightId, $roleName)
    {
        $rightId = $this->positiveInteger($rightId);
        $roleName = $this->normaliseContextRoleName($roleName);
        if ($rightId === null
            || $roleName === null
            || !$this->rightExistsExactlyOnce($rightId)) {
            return false;
        }

        $rows = $this->getArray(
            'SELECT id FROM tbl_perms_contextrolegrants'
            . ' WHERE right_id = ' . $rightId
            . ' AND role_name = ' . $this->quoteValue($roleName)
            . ' LIMIT 2'
        );
        if (!is_array($rows) || count($rows) > 1) {
            return false;
        }

        if (count($rows) === 0
            && !$this->insertInto(
                'tbl_perms_contextrolegrants',
                array(
                    'id' => $this->definitionRecordId(
                        'context-role',
                        $rightId,
                        $roleName
                    ),
                    'right_id' => $rightId,
                    'role_name' => $roleName,
                )
            )) {
            return false;
        }

        return $this->materializeRoleTemplateForExistingGroups(
            $rightId,
            $roleName
        );
    }

    /**
     * Materialize every contextual-role template for one concrete context.
     *
     * This is called after context groups have been created. Missing or
     * duplicate concrete groups fail closed.
     */
    public function materializeContextRoleGrants($contextCode)
    {
        $contextCode = $this->normaliseContextCode($contextCode);
        if ($contextCode === null) {
            return false;
        }

        $templates = $this->getArray(
            'SELECT right_id, role_name'
            . ' FROM tbl_perms_contextrolegrants'
            . ' ORDER BY right_id, role_name'
        );
        if (!is_array($templates)) {
            return false;
        }

        foreach ($templates as $template) {
            if (!is_array($template)
                || !isset($template['right_id'], $template['role_name'])) {
                return false;
            }
            $rightId = $this->positiveInteger($template['right_id']);
            $roleName = $this->normaliseContextRoleName(
                $template['role_name']
            );
            if ($rightId === null || $roleName === null) {
                return false;
            }

            $groupIds = $this->groupIdsForExactName(
                $contextCode . '^' . $roleName
            );
            if (count($groupIds) !== 1
                || !$this->ensureGroupGrant($groupIds[0], $rightId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate one explicit canonical right for one logical user.
     *
     * A grant may be direct or inherited from any canonical group membership.
     * Unknown users, rights, duplicate definitions and query failures deny.
     */
    public function isGranted($userId, $rightId)
    {
        $rightId = $this->positiveInteger($rightId);
        if ($rightId === null || !$this->rightExistsExactlyOnce($rightId)) {
            return false;
        }

        $permissionUserId = $this->permissionUserIdForUser($userId);
        if ($permissionUserId === null) {
            return false;
        }

        $direct = $this->getArray(
            'SELECT id FROM tbl_perms_userrights'
            . ' WHERE perm_user_id = ' . $permissionUserId
            . ' AND right_id = ' . $rightId
            . ' LIMIT 1'
        );
        if (is_array($direct) && count($direct) > 0) {
            return true;
        }

        $group = $this->getArray(
            'SELECT gr.id FROM tbl_perms_grouprights AS gr'
            . ' INNER JOIN tbl_perms_groupusers AS gu'
            . ' ON gu.group_id = gr.group_id'
            . ' WHERE gu.perm_user_id = ' . $permissionUserId
            . ' AND gr.right_id = ' . $rightId
            . ' LIMIT 1'
        );

        return is_array($group) && count($group) > 0;
    }

    /**
     * Idempotently grant one defined right to one canonical group.
     */
    public function ensureGroupGrant($groupId, $rightId)
    {
        $groupId = $this->positiveInteger($groupId);
        $rightId = $this->positiveInteger($rightId);
        if ($groupId === null
            || $rightId === null
            || !$this->groupExistsExactlyOnce($groupId)
            || !$this->rightExistsExactlyOnce($rightId)) {
            return false;
        }

        $rows = $this->getArray(
            'SELECT id FROM tbl_perms_grouprights'
            . ' WHERE group_id = ' . $groupId
            . ' AND right_id = ' . $rightId
            . ' LIMIT 2'
        );
        if (!is_array($rows)) {
            return false;
        }
        if (count($rows) === 1) {
            return true;
        }
        if (count($rows) > 1) {
            return false;
        }

        $this->insertInto(
            'tbl_perms_grouprights',
            array(
                'id' => $this->recordId('group', $groupId, $rightId),
                'group_id' => $groupId,
                'right_id' => $rightId,
                'right_level' => 1,
            )
        );

        $rows = $this->getArray(
            'SELECT id FROM tbl_perms_grouprights'
            . ' WHERE group_id = ' . $groupId
            . ' AND right_id = ' . $rightId
            . ' LIMIT 2'
        );

        return is_array($rows) && count($rows) === 1;
    }

    /**
     * Idempotently grant every currently defined right to one canonical group.
     *
     * Installation policy decides which group receives this capability.
     */
    public function ensureAllDefinedRightsForGroup($groupId)
    {
        $groupId = $this->positiveInteger($groupId);
        if ($groupId === null || !$this->groupExistsExactlyOnce($groupId)) {
            return false;
        }

        $rows = $this->getArray(
            'SELECT right_id FROM tbl_perms_rights ORDER BY right_id'
        );
        if (!is_array($rows)) {
            return false;
        }

        foreach ($rows as $row) {
            if (!is_array($row)
                || !isset($row['right_id'])
                || !$this->ensureGroupGrant($groupId, $row['right_id'])) {
                return false;
            }
        }

        return true;
    }

    protected function permissionUserIdForUser($userId)
    {
        if (!is_scalar($userId)) {
            return null;
        }
        $userId = trim((string) $userId);
        if ($userId === '' || strlen($userId) > 25) {
            return null;
        }

        $permissionUserId = $this->objIdentityService
            ->permissionUserIdForUser($userId);

        return $this->positiveInteger($permissionUserId);
    }

    protected function insertInto($tableName, array $data)
    {
        $previous = $this->_tableName;
        $this->_tableName = $tableName;
        $inserted = $this->insert($data);
        $this->_tableName = $previous;

        return $inserted !== false;
    }

    private function rightExistsExactlyOnce($rightId)
    {
        $rows = $this->getArray(
            'SELECT right_id FROM tbl_perms_rights'
            . ' WHERE right_id = ' . $rightId
            . ' LIMIT 2'
        );

        return is_array($rows) && count($rows) === 1;
    }

    private function groupExistsExactlyOnce($groupId)
    {
        $rows = $this->getArray(
            'SELECT group_id FROM tbl_perms_groups'
            . ' WHERE group_id = ' . $groupId
            . ' LIMIT 2'
        );

        return is_array($rows) && count($rows) === 1;
    }

    private function areaExistsExactlyOnce($areaId)
    {
        $rows = $this->getArray(
            'SELECT area_id FROM tbl_perms_areas'
            . ' WHERE area_id = ' . $areaId
            . ' LIMIT 2'
        );

        return is_array($rows) && count($rows) === 1;
    }

    private function materializeRoleTemplateForExistingGroups(
        $rightId,
        $roleName
    ) {
        $rows = $this->getArray(
            'SELECT group_id, group_define_name FROM tbl_perms_groups'
            . ' WHERE group_define_name LIKE '
            . $this->quoteValue('%^' . $roleName)
            . ' ORDER BY group_id'
        );
        if (!is_array($rows)) {
            return false;
        }

        foreach ($rows as $row) {
            if (!is_array($row)
                || !isset($row['group_id'], $row['group_define_name'])
                || substr(
                    (string) $row['group_define_name'],
                    -strlen('^' . $roleName)
                ) !== '^' . $roleName
                || !$this->ensureGroupGrant($row['group_id'], $rightId)) {
                return false;
            }
        }

        return true;
    }

    private function groupIdsForExactName($groupName)
    {
        $rows = $this->getArray(
            'SELECT group_id, group_define_name FROM tbl_perms_groups'
            . ' WHERE group_define_name = ' . $this->quoteValue($groupName)
            . ' LIMIT 2'
        );
        if (!is_array($rows)) {
            return array();
        }

        $groupIds = array();
        foreach ($rows as $row) {
            if (!is_array($row)
                || !isset($row['group_id'], $row['group_define_name'])
                || (string) $row['group_define_name'] !== $groupName) {
                return array();
            }
            $groupId = $this->positiveInteger($row['group_id']);
            if ($groupId === null) {
                return array();
            }
            $groupIds[] = $groupId;
        }

        return $groupIds;
    }

    private function nextIntegerId($tableName, $columnName)
    {
        $allowed = array(
            'tbl_perms_areas' => 'area_id',
            'tbl_perms_rights' => 'right_id',
        );
        if (!isset($allowed[$tableName])
            || $allowed[$tableName] !== $columnName) {
            return null;
        }

        $rows = $this->getArray(
            'SELECT MAX(' . $columnName . ') AS maximum_id'
            . ' FROM ' . $tableName
        );
        if (!is_array($rows) || !isset($rows[0])) {
            return null;
        }

        if (!isset($rows[0]['maximum_id'])
            || $rows[0]['maximum_id'] === null) {
            return 1;
        }

        $maximum = $this->positiveInteger($rows[0]['maximum_id']);

        return $maximum === null ? null : $maximum + 1;
    }

    private function normaliseRightName($value)
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);
        if ($value === ''
            || strlen($value) > 32
            || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            return null;
        }

        return $value;
    }

    private function normaliseApplicationId($value)
    {
        return $this->normaliseDefinitionName($value, 32);
    }

    private function normaliseAreaName($value)
    {
        return $this->normaliseDefinitionName($value, 50);
    }

    private function normaliseContextCode($value)
    {
        $value = $this->normaliseDefinitionName($value, 100);
        if ($value === null || strpos($value, '^') !== false) {
            return null;
        }

        return $value;
    }

    private function normaliseContextRoleName($value)
    {
        $value = $this->normaliseDefinitionName($value, 50);
        if ($value === null || strpos($value, '^') !== false) {
            return null;
        }

        return $value;
    }

    private function normaliseDefinitionName($value, $maximumLength)
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);
        if ($value === ''
            || strlen($value) > $maximumLength
            || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            return null;
        }

        return $value;
    }

    private function positiveInteger($value)
    {
        if (!is_scalar($value)
            || !preg_match('/^[1-9]\d*$/', (string) $value)) {
            return null;
        }

        return (int) $value;
    }

    private function recordId($kind, $ownerId, $rightId)
    {
        return substr(
            'perm_' . md5($kind . '|' . $ownerId . '|' . $rightId),
            0,
            32
        );
    }

    private function definitionRecordId($kind, $owner, $name)
    {
        return substr(
            'perm_' . md5($kind . '|' . $owner . '|' . $name),
            0,
            32
        );
    }

    private function quoteValue($value)
    {
        return "'" . addslashes((string) $value) . "'";
    }
}
?>
