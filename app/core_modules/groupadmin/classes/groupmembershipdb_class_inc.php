<?php
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class groupmembershipdb extends dbTable
{
    public function init(
        $tableName = null,
        $pearDb = null,
        $errorCallback = 'globalPearErrorHandler'
    ) {
        parent::init(
            $tableName !== null ? $tableName : 'tbl_perms_groupusers',
            $pearDb,
            $errorCallback
        );
    }

    /**
     * Ensure one direct canonical group membership on the dbTable connection.
     */
    public function ensureMembership($groupId, $permissionUserId)
    {
        $groupId = $this->positiveInteger($groupId);
        $permissionUserId = $this->positiveInteger($permissionUserId);
        if ($groupId === null || $permissionUserId === null) {
            return false;
        }
        if ($this->membershipExists($groupId, $permissionUserId)) {
            return true;
        }
        $inserted = $this->_execute(
            'INSERT INTO tbl_perms_groupusers (group_id, perm_user_id)'
            . ' VALUES (' . $groupId . ', ' . $permissionUserId . ')'
        );
        return $inserted !== false
            && $this->membershipExists($groupId, $permissionUserId);
    }

    public function membershipExists($groupId, $permissionUserId)
    {
        $groupId = $this->positiveInteger($groupId);
        $permissionUserId = $this->positiveInteger($permissionUserId);
        if ($groupId === null || $permissionUserId === null) {
            return false;
        }

        $sql = 'SELECT COUNT(*) AS cnt FROM tbl_perms_groupusers'
             . ' WHERE group_id = ' . $groupId
             . ' AND perm_user_id = ' . $permissionUserId;

        $rows = $this->getArray($sql);
        return is_array($rows)
            && isset($rows[0]['cnt'])
            && (int) $rows[0]['cnt'] > 0;
    }

    public function addMembership($groupId, $permissionUserId)
    {
        $groupId = $this->positiveInteger($groupId);
        $permissionUserId = $this->positiveInteger($permissionUserId);
        if ($groupId === null || $permissionUserId === null) {
            return false;
        }

        if ($this->membershipExists($groupId, $permissionUserId)) {
            return true;
        }

        return $this->insert(array(
            'group_id' => $groupId,
            'perm_user_id' => $permissionUserId,
        )) !== false;
    }

    public function removeMembership($groupId, $permissionUserId)
    {
        $groupId = $this->positiveInteger($groupId);
        $permissionUserId = $this->positiveInteger($permissionUserId);
        if ($groupId === null || $permissionUserId === null) {
            return false;
        }

        if (!$this->membershipExists($groupId, $permissionUserId)) {
            return true;
        }

        $sql = 'DELETE FROM tbl_perms_groupusers'
             . ' WHERE group_id = ' . $groupId
             . ' AND perm_user_id = ' . $permissionUserId;

        return $this->query($sql) !== false;
    }

    private function positiveInteger($value)
    {
        if (!is_scalar($value)
            || !preg_match('/^[1-9]\\d*$/', (string) $value)) {
            return null;
        }

        return (int) $value;
    }
}
?>
