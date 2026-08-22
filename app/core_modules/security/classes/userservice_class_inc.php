<?php
/**
 * Canonical service boundary for Chisimba user records.
 *
 * This service is the sole owner of tbl_users. Public methods use the logical
 * userid value; the storage primary key remains an internal implementation
 * detail. Authentication identities, groups and permissions are owned by their
 * respective canonical services and are deliberately not managed here.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class userservice extends dbTable
{
    public function init(
        $tableName = null,
        $pearDb = null,
        $errorCallback = 'globalPearErrorHandler'
    ) {
        parent::init(
            $tableName !== null ? $tableName : 'tbl_users',
            $pearDb,
            $errorCallback
        );
    }

    /**
     * Return one user by logical user ID.
     *
     * @return array|null
     */
    public function findByUserId($userId)
    {
        return $this->findOne('userid', $this->normaliseUserId($userId));
    }

    /**
     * Return one user by username.
     *
     * @return array|null
     */
    public function findByUsername($username)
    {
        return $this->findOne('username', $this->normaliseText($username, 255));
    }

    /**
     * Return one user by the retained storage primary key.
     *
     * This method exists only for bounded migration of callers that still hold
     * tbl_users.id. New callers must retain and pass the logical user ID.
     *
     * @return array|null
     */
    public function findByStorageId($id)
    {
        return $this->findOne('id', $this->normaliseText($id, 255));
    }

    /**
     * Return users for the native administration interface.
     */
    public function listUsers($query = '', $includeInactive = true)
    {
        $query = $this->normaliseText($query, 255, true);
        if ($query === null) {
            return array();
        }

        $conditions = array();
        if ($query !== '') {
            $term = $this->quoteValue('%' . $query . '%');
            $conditions[] = '(username LIKE ' . $term
                . ' OR firstname LIKE ' . $term
                . ' OR surname LIKE ' . $term
                . ' OR emailaddress LIKE ' . $term . ')';
        }
        if (!$includeInactive) {
            $conditions[] = 'isactive = 1';
        }

        $sql = 'SELECT id, userid, username, title, firstname, surname,'
            . ' emailaddress, sex, country, cellnumber, staffnumber,'
            . ' howcreated, isactive, creationdate, logins, last_login'
            . ' FROM tbl_users';
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY UPPER(surname), UPPER(firstname), UPPER(username)';

        $rows = $this->getArray($sql);
        return is_array($rows) ? $rows : array();
    }

    public function usernameAvailable($username, $excludingUserId = null)
    {
        return $this->valueAvailable('username', $username, $excludingUserId);
    }

    public function emailAvailable($email, $excludingUserId = null)
    {
        return $this->valueAvailable('emailaddress', $email, $excludingUserId);
    }

    public function userIdAvailable($userId)
    {
        $userId = $this->normaliseUserId($userId);
        return $userId !== null && $this->findByUserId($userId) === null;
    }

    /**
     * Generate an available logical user ID.
     *
     * Logical ID generation belongs to UserService because it alone can
     * authoritatively check tbl_users for a collision.
     *
     * @return string|null
     */
    public function generateUserId()
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $userId = (string) mt_rand(1000, 9999) . date('ymd');
            if ($this->userIdAvailable($userId)) {
                return $userId;
            }
        }

        return null;
    }

    /**
     * Create the tbl_users record only.
     *
     * The caller must subsequently use IdentityService and GroupService when a
     * permission identity or initial group membership is required.
     *
     * @return array Result with ok, code and userId keys.
     */
    public function createUser(array $input)
    {
        $data = $this->normaliseWriteData($input, true);
        if (!$data['ok']) {
            return $data;
        }

        $values = $data['values'];
        if (!$this->userIdAvailable($values['userid'])) {
            return $this->result(false, 'userid_taken');
        }
        if (!$this->usernameAvailable($values['username'])) {
            return $this->result(false, 'username_taken');
        }
        if ($values['emailaddress'] !== ''
            && !$this->emailAvailable($values['emailaddress'])) {
            return $this->result(false, 'email_taken');
        }

        $passwordHash = $this->normaliseText(
            isset($input['passwordHash']) ? $input['passwordHash'] : null,
            255
        );
        if ($passwordHash === null) {
            return $this->result(false, 'missing_password_hash');
        }

        $values['id'] = $this->newStorageId($values['userid']);
        $values['pass'] = $passwordHash;
        $values['creationdate'] = date('Y-m-d');
        $inserted = $this->insert($values);
        if ($inserted === false) {
            return $this->result(false, 'create_failed');
        }

        return $this->result(true, 'user_created', $values['userid']);
    }

    /**
     * Roll back only the exact tbl_users row created by a failed provisioning.
     *
     * This is not a general user-deletion API. Both identifiers must match,
     * the account must never have logged in, and its creation source must be
     * an approved canonical provisioning workflow.
     */
    public function rollbackProvisionedUser($userId, $storageId)
    {
        $userId = $this->normaliseUserId($userId);
        $storageId = $this->normaliseText($storageId, 255);
        if ($userId === null || $storageId === null) {
            return $this->result(false, 'invalid_rollback_identity');
        }

        $record = $this->findByUserId($userId);
        if (!is_array($record)
            || !isset($record['id'], $record['userid'], $record['howcreated'])
            || (string) $record['id'] !== $storageId
            || (string) $record['userid'] !== $userId
            || !in_array(
                (string) $record['howcreated'],
                array(
                    'useradmin',
                    'batch_user_registration',
                    'registration-service',
                ),
                true
            )
            || (isset($record['logins']) && (int) $record['logins'] !== 0)) {
            return $this->result(false, 'rollback_guard_rejected', $userId);
        }

        $removed = $this->delete('id', $storageId);
        return $removed === false
            ? $this->result(false, 'rollback_failed', $userId)
            : $this->result(true, 'provisioned_user_rolled_back', $userId);
    }

    /**
     * Update profile fields for one logical user.
     */
    public function updateUser($userId, array $input)
    {
        $userId = $this->normaliseUserId($userId);
        $existing = $userId === null ? null : $this->findByUserId($userId);
        if ($existing === null) {
            return $this->result(false, 'user_not_found');
        }

        $data = $this->normaliseWriteData($input, false);
        if (!$data['ok']) {
            return $data;
        }
        $values = $data['values'];
        unset($values['userid']);

        if (isset($values['username'])
            && !$this->usernameAvailable($values['username'], $userId)) {
            return $this->result(false, 'username_taken');
        }
        if (isset($values['emailaddress'])
            && $values['emailaddress'] !== ''
            && !$this->emailAvailable($values['emailaddress'], $userId)) {
            return $this->result(false, 'email_taken');
        }
        if (!$values) {
            return $this->result(false, 'no_changes');
        }

        $updated = $this->update('userid', $userId, $values);
        return $updated === false
            ? $this->result(false, 'update_failed')
            : $this->result(true, 'user_updated', $userId);
    }

    /**
     * Persist a credential hash already created by AuthenticationService.
     */
    public function updatePasswordHash($userId, $passwordHash)
    {
        $userId = $this->normaliseUserId($userId);
        if ($userId === null || $this->findByUserId($userId) === null) {
            return $this->result(false, 'user_not_found');
        }
        $passwordHash = $this->normaliseText($passwordHash, 255);
        if ($passwordHash === null) {
            return $this->result(false, 'invalid_password_hash');
        }

        $updated = $this->update(
            'userid',
            $userId,
            array('pass' => $passwordHash, 'updated' => date('Y-m-d'))
        );
        return $updated === false
            ? $this->result(false, 'password_update_failed')
            : $this->result(true, 'password_updated', $userId);
    }

    public function setActive($userId, $active)
    {
        $userId = $this->normaliseUserId($userId);
        if ($userId === null || $this->findByUserId($userId) === null) {
            return $this->result(false, 'user_not_found');
        }

        $updated = $this->update(
            'userid',
            $userId,
            array('isactive' => $active ? 1 : 0)
        );
        return $updated === false
            ? $this->result(false, 'status_update_failed')
            : $this->result(true, $active ? 'user_activated' : 'user_deactivated', $userId);
    }

    private function normaliseWriteData(array $input, $creating)
    {
        $map = array(
            'userid' => array('userId', 'userid'),
            'username' => array('username', 'handle'),
            'title' => array('title'),
            'firstname' => array('firstName', 'firstname'),
            'surname' => array('surname'),
            'emailaddress' => array('emailAddress', 'emailaddress', 'email'),
            'sex' => array('sex'),
            'country' => array('country'),
            'cellnumber' => array('cellnumber'),
            'staffnumber' => array('staffnumber'),
            'howcreated' => array('howCreated', 'howcreated'),
            'isactive' => array('isActive', 'isactive', 'is_active'),
        );

        $values = array();
        foreach ($map as $column => $keys) {
            $present = false;
            $value = $this->inputValue($input, $keys, $present);
            if (!$present) {
                continue;
            }
            if ($column === 'isactive') {
                $values[$column] = $value ? 1 : 0;
                continue;
            }
            $normalised = $column === 'userid'
                ? $this->normaliseUserId($value)
                : $this->normaliseText($value, 255, true);
            if ($normalised === null) {
                return $this->result(false, 'invalid_' . $column);
            }
            $values[$column] = $normalised;
        }

        if ($creating) {
            foreach (array('userid', 'username', 'firstname', 'surname') as $required) {
                if (!isset($values[$required]) || $values[$required] === '') {
                    return $this->result(false, 'missing_' . $required);
                }
            }
            foreach (array(
                'title', 'emailaddress', 'sex', 'country',
                'cellnumber', 'staffnumber'
            ) as $optional) {
                if (!isset($values[$optional])) {
                    $values[$optional] = '';
                }
            }
            if (!isset($values['howcreated']) || $values['howcreated'] === '') {
                $values['howcreated'] = 'useradmin';
            }
            if (!isset($values['isactive'])) {
                $values['isactive'] = 1;
            }
        }

        return array('ok' => true, 'code' => 'valid', 'values' => $values);
    }

    private function findOne($column, $value)
    {
        if ($value === null) {
            return null;
        }
        $rows = $this->getArray(
            'SELECT * FROM tbl_users WHERE ' . $column . ' = '
            . $this->quoteValue($value) . ' LIMIT 2'
        );
        return is_array($rows) && count($rows) === 1 ? $rows[0] : null;
    }

    private function valueAvailable($column, $value, $excludingUserId)
    {
        $value = $this->normaliseText($value, 255);
        if ($value === null) {
            return false;
        }
        $sql = 'SELECT userid FROM tbl_users WHERE LOWER(' . $column . ') = LOWER('
            . $this->quoteValue($value) . ')';
        $excludingUserId = $this->normaliseUserId($excludingUserId);
        if ($excludingUserId !== null) {
            $sql .= ' AND userid <> ' . $this->quoteValue($excludingUserId);
        }
        $sql .= ' LIMIT 1';
        $rows = $this->getArray($sql);
        return !is_array($rows) || count($rows) === 0;
    }

    private function inputValue(array $input, array $keys, &$present)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $input)) {
                $present = true;
                return $input[$key];
            }
        }
        $present = false;
        return null;
    }

    private function normaliseUserId($value)
    {
        return $this->normaliseText($value, 25);
    }

    private function normaliseText($value, $maximumLength, $allowEmpty = false)
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);
        if ((!$allowEmpty && $value === '')
            || strlen($value) > $maximumLength
            || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            return null;
        }
        return $value;
    }

    private function newStorageId($userId)
    {
        return substr(
            'usr_' . md5($userId . '|' . microtime(true) . '|' . mt_rand()),
            0,
            32
        );
    }

    private function result($ok, $code, $userId = null)
    {
        $result = array('ok' => (bool) $ok, 'code' => (string) $code);
        if ($userId !== null) {
            $result['userId'] = (string) $userId;
        }
        return $result;
    }

    private function quoteValue($value)
    {
        return "'" . addslashes((string) $value) . "'";
    }
}
?>
