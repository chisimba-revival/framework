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
     * Run a statement with bound values and return every row.
     *
     * Values are never interpolated into SQL. The previous quoteValue() helper
     * escaped with addslashes(), which is only safe while the connection
     * charset cannot produce a backslash byte inside a multi-byte character.
     * Under utf8mb3 that holds; it would not under GBK, BIG5 or SJIS.
     *
     * Bind types are declared because an undeclared placeholder resolves to
     * null and raises a PHP 8.1 deprecation on every execution.
     *
     * Failures are reported through error_log() rather than the inherited
     * errorCallback(). This class's init() default names a callback that does
     * not exist in the framework, so calling it would raise a TypeError and
     * turn a database problem into an unhandled fatal. This matches
     * dbTable::_queryRow() on the same failure.
     *
     * @param string $sql Statement using ? placeholders
     * @param array $bindings Values in placeholder order
     * @return array Rows, or an empty array when the statement cannot be run
     */
    private function selectRows($sql, array $bindings = array())
    {
        $types = array();
        foreach ($bindings as $value) {
            $types[] = is_int($value) ? 'integer' : 'text';
        }

        try {
            $db = $this->objEngine->getDbObj();
            $statement = $db->prepare($sql, $types);
            if (MDB2::isError($statement)) {
                error_log('Identity query could not be prepared: ' . $statement->getMessage());
                return array();
            }
            $result = $statement->execute($bindings);
            if (MDB2::isError($result)) {
                error_log('Identity query failed: ' . $result->getMessage());
                $statement->free();
                return array();
            }
            $rows = $result->fetchAll();
            $result->free();
            $statement->free();
        } catch (Throwable $e) {
            error_log('Identity query failed: ' . $e->getMessage());
            return array();
        }

        return is_array($rows) ? $rows : array();
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
             . ' WHERE u.userid = ?'
             . ' AND pu.auth_container_name = ?'
             . ' LIMIT 2';

        $rows = $this->selectRows($sql, array($userId, $containerName));
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
             . ' WHERE pu.perm_user_id = ?'
             . ' LIMIT 2';

        $rows = $this->selectRows($sql, array($permissionUserId));
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

        $userRows = $this->selectRows(
            'SELECT userid FROM tbl_users WHERE userid = ? LIMIT 2',
            array($userId)
        );
        if (!is_array($userRows) || count($userRows) !== 1) {
            return null;
        }

        $rows = $this->selectRows(
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

    /**
     * Roll back one exact permission identity from a failed provisioning.
     *
     * Refuses removal when the exact identity does not match. The provisioning
     * orchestrator must remove and verify its own membership first.
     */
    public function rollbackProvisionedIdentity(
        $userId,
        $permissionUserId,
        $containerName = 'database_local'
    ) {
        $userId = $this->normaliseLogicalUserId($userId);
        $permissionUserId = $this->positiveInteger($permissionUserId);
        $containerName = $this->normaliseContainerName($containerName);
        if ($userId === null
            || $permissionUserId === null
            || $containerName === null) {
            return false;
        }

        $rows = $this->selectRows(
            'SELECT perm_user_id FROM tbl_perms_perm_users'
            . ' WHERE perm_user_id = ?'
            . ' AND auth_user_id = ?'
            . ' AND auth_container_name = ?'
            . ' LIMIT 2',
            array($permissionUserId, $userId, $containerName)
        );
        if (!is_array($rows) || count($rows) !== 1) {
            return false;
        }

        return $this->delete('perm_user_id', $permissionUserId) !== false;
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
}
?>
