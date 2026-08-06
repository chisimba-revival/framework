<?php
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}

/**
 * Canonical compatibility boundary for user-account lifecycle operations.
 *
 * This first version performs a conservative dependency preflight. It never
 * deletes or reassigns module-owned data, and it does not claim that the
 * current unconstrained schema can yet support a safe hard delete.
 *
 * Future implementations may add registered dependency providers and a
 * transactional delete coordinator without changing callers of this service.
 *
 * @author Derek Keats
 */
class useraccountlifecycleservice extends dbTable
{
    /**
     * Explicit first-generation dependency policy.
     *
     * Each entry names a module-owned table and a logical-user-ID column.
     * The list is intentionally visible and reviewable until table owners can
     * register dependency providers through a later database evolution.
     */
    private $dependencyPolicy = array(
        array('table' => 'tbl_context', 'column' => 'userid'),
        array('table' => 'tbl_context_blocks', 'column' => 'updatedby'),
        array('table' => 'tbl_en', 'column' => 'creatoruserid'),
        array('table' => 'tbl_en', 'column' => 'modifiedbyuserid'),
        array('table' => 'tbl_logger', 'column' => 'userid'),
        array('table' => 'tbl_personalspace_blocks', 'column' => 'userid'),
        array('table' => 'tbl_sysconfig_properties', 'column' => 'creatorId')
    );

    public function init(
        $tableName = null,
        $pearDb = null,
        $errorCallback = 'globalPearErrorCallback'
    ) {
        parent::init(
            $tableName !== null ? $tableName : 'tbl_users',
            $pearDb,
            $errorCallback
        );
        $this->objUserService = $this->getObject('userservice', 'security');
    }

    /**
     * Request deletion using the retained tbl_users.id migration identifier.
     *
     * @return array Structured result containing ok, code and dependencies.
     */
    public function requestDeletionByStorageId($storageId)
    {
        $user = $this->objUserService->findByStorageId($storageId);
        if (!is_array($user) || empty($user['userid'])) {
            return $this->result(false, 'user_not_found');
        }

        $dependencies = $this->externalDependencies($user['userid']);
        if (!empty($dependencies)) {
            return $this->result(
                false,
                'user_deletion_has_dependencies',
                $user['userid'],
                $dependencies
            );
        }

        // The compatibility boundary is established, but coordinated hard
        // deletion remains disabled until every lifecycle owner participates
        // in one transaction with explicit failure and rollback semantics.
        return $this->result(
            false,
            'user_deletion_not_yet_supported',
            $user['userid']
        );
    }

    private function externalDependencies($userId)
    {
        $dependencies = array();
        $quotedUserId = $this->quoteValue((string) $userId);

        foreach ($this->dependencyPolicy as $policy) {
            $sql = 'SELECT COUNT(*) AS dependency_count FROM '
                . $policy['table'] . ' WHERE ' . $policy['column']
                . ' = ' . $quotedUserId;
            $rows = $this->getArray($sql);
            $count = is_array($rows) && isset($rows[0]['dependency_count'])
                ? (int) $rows[0]['dependency_count'] : 0;
            if ($count > 0) {
                $dependencies[] = array(
                    'table' => $policy['table'],
                    'column' => $policy['column'],
                    'count' => $count,
                    'policy' => 'refuse'
                );
            }
        }

        return $dependencies;
    }

    private function result(
        $ok,
        $code,
        $userId = null,
        array $dependencies = array()
    ) {
        return array(
            'ok' => (bool) $ok,
            'code' => (string) $code,
            'userId' => $userId,
            'dependencies' => $dependencies
        );
    }

    private function quoteValue($value)
    {
        return method_exists($this->_objDB, 'quoteSmart')
            ? $this->_objDB->quoteSmart($value)
            : "'" . str_replace("'", "''", $value) . "'";
    }
}
?>
