<?php
/**
 * Canonical boundary between Chisimba users, authentication identities and
 * permission identities.
 *
 * Public callers use logical tbl_users.userid values. Permission storage keys
 * remain internal to this service.
 */
class identityservice extends dbTable
{
    public function init(
        $tableName = null,
        $pearDb = null,
        $errorCallback = 'globalPearErrorHandler'
    ) {
        parent::init(
            $tableName !== null ? $tableName : 'tbl_perms_perm_users',
            $pearDb,
            $errorCallback
        );
    }

    /**
     * Resolve a logical user ID to its permission identity.
     *
     * @return int|null
     */
    public function permissionUserIdForUser($userId, $containerName = 'database_local')
    {
        $userId = $this->normaliseLogicalUserId($userId);
        $containerName = $this->normaliseContainerName($containerName);

        if ($userId === null || $containerName === null) {
            return null;
        }

        $sql = 'SELECT pu.perm_user_id'
             . ' FROM tbl_perms_perm_users AS pu'
             . ' INNER JOIN tbl_users AS u'
             . ' ON CAST(pu.auth_user_id AS CHAR(25)) = u.userid'
             . ' WHERE u.userid = ' . $this->quoteValue($userId)
             . ' AND pu.auth_container_name = ' . $this->quoteValue($containerName)
             . ' LIMIT 2';

        $rows = $this->getArray($sql);
        if (!is_array($rows) || count($rows) !== 1) {
            return null;
        }

        return $this->positiveInteger($rows[0]['perm_user_id']);
    }

    /**
     * Resolve a permission identity to its logical user ID.
     *
     * @return string|null
     */
    public function userIdForPermissionUser($permissionUserId)
    {
        $permissionUserId = $this->positiveInteger($permissionUserId);
        if ($permissionUserId === null) {
            return null;
        }

        $sql = 'SELECT u.userid'
             . ' FROM tbl_perms_perm_users AS pu'
             . ' INNER JOIN tbl_users AS u'
             . ' ON CAST(pu.auth_user_id AS CHAR(25)) = u.userid'
             . ' WHERE pu.perm_user_id = ' . $permissionUserId
             . ' LIMIT 2';

        $rows = $this->getArray($sql);
        if (!is_array($rows) || count($rows) !== 1) {
            return null;
        }

        return (string) $rows[0]['userid'];
    }

    public function hasPermissionIdentity($userId, $containerName = 'database_local')
    {
        return $this->permissionUserIdForUser($userId, $containerName) !== null;
    }


    /**
     * Return the existing permission identity for a logical user, or create it.
     *
     * @return int|null
     */
    public function ensurePermissionIdentity(
        $userId,
        $containerName = 'database_local',
        $permissionType = 5
    ) {
        $userId = $this->normaliseLogicalUserId($userId);
        $containerName = $this->normaliseContainerName($containerName);
        $permissionType = $this->positiveInteger($permissionType);

        if ($userId === null || $containerName === null || $permissionType === null) {
            return null;
        }

        $existing = $this->permissionUserIdForUser($userId, $containerName);
        if ($existing !== null) {
            return $existing;
        }

        $userRows = $this->getArray(
            'SELECT userid FROM tbl_users WHERE userid = '
            . $this->quoteValue($userId)
            . ' LIMIT 2'
        );
        if (!is_array($userRows) || count($userRows) !== 1) {
            return null;
        }

        $rows = $this->getArray(
            'SELECT MAX(perm_user_id) AS max_perm_user_id FROM tbl_perms_perm_users'
        );
        $nextPermissionUserId = 1;
        if (is_array($rows)
            && isset($rows[0]['max_perm_user_id'])
            && $rows[0]['max_perm_user_id'] !== null) {
            $maximum = (int) $rows[0]['max_perm_user_id'];
            if ($maximum > 0) {
                $nextPermissionUserId = $maximum + 1;
            }
        }

        $inserted = $this->insert(array(
            'id' => substr('perm_' . md5($userId . '|' . $containerName . '|' . microtime(true)), 0, 32),
            'perm_user_id' => $nextPermissionUserId,
            'auth_user_id' => $userId,
            'auth_container_name' => $containerName,
            'perm_type' => $permissionType,
        ));

        if ($inserted === false) {
            return $this->permissionUserIdForUser($userId, $containerName);
        }

        return $this->permissionUserIdForUser($userId, $containerName);
    }

    private function normaliseLogicalUserId($value)
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || strlen($value) > 25) {
            return null;
        }

        return $value;
    }

    private function normaliseContainerName($value)
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || strlen($value) > 32) {
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

    private function quoteValue($value)
    {
        return "'" . addslashes((string) $value) . "'";
    }
}
?>
