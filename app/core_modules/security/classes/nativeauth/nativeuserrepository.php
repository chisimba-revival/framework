<?php
require_once dirname(__FILE__) . '/nativeuserrepositoryinterface.php';
require_once dirname(__FILE__) . '/nativedatabaseadapterinterface.php';

/**
 * Schema-backed user repository for the retained tbl_users table.
 *
 * Read methods are production-shaped. Write methods are deliberately guarded
 * and disabled by default so shadow/comparison use cannot mutate authentication
 * state accidentally.
 */
class NativeUserRepository implements NativeUserRepositoryInterface
{
    private $database;
    private $writesEnabled;

    public function __construct(
        NativeDatabaseAdapterInterface $database,
        $writesEnabled = false
    ) {
        $this->database = $database;
        $this->writesEnabled = (bool) $writesEnabled;
    }

    public function findByUsername($username)
    {
        $record = $this->database->fetchOne(
            'SELECT id, userid, username, pass, isactive, puid, '
            . 'emailaddress, firstname, surname, accesslevel, '
            . 'howcreated, logins, last_login '
            . 'FROM tbl_users WHERE username = ? LIMIT 1',
            array(trim((string) $username))
        );

        return $this->normaliseRecord($record);
    }

    public function findById($userId)
    {
        $record = $this->database->fetchOne(
            'SELECT id, userid, username, pass, isactive, puid, '
            . 'emailaddress, firstname, surname, accesslevel, '
            . 'howcreated, logins, last_login '
            . 'FROM tbl_users '
            . 'WHERE userid = ? OR id = ? LIMIT 1',
            array((string) $userId, (string) $userId)
        );

        return $this->normaliseRecord($record);
    }

    public function isUserActive($userId)
    {
        $record = $this->findById($userId);
        if ($record === null) {
            return false;
        }

        return $record['is_active'];
    }

    public function updatePasswordHash($userId, $passwordHash)
    {
        $this->assertWritesEnabled();

        return $this->database->execute(
            'UPDATE tbl_users SET pass = ?, updated = CURRENT_DATE '
            . 'WHERE userid = ? OR id = ?',
            array((string) $passwordHash, (string) $userId, (string) $userId)
        ) > 0;
    }

    public function recordSuccessfulLogin($userId, array $context = array())
    {
        $this->assertWritesEnabled();

        return $this->database->execute(
            'UPDATE tbl_users '
            . 'SET logins = CAST(COALESCE(logins, 0) AS UNSIGNED) + 1, '
            . 'last_login = CURRENT_TIMESTAMP '
            . 'WHERE userid = ? OR id = ?',
            array((string) $userId, (string) $userId)
        ) > 0;
    }

    public function recordFailedLogin($username, array $context = array())
    {
        // Existing schema has no confirmed failed-login counter. This remains a
        // no-op until the legacy audit/logging behaviour is captured.
        return true;
    }

    private function normaliseRecord($record)
    {
        if (!is_array($record) || $record === array()) {
            return null;
        }

        $userId = '';
        if (isset($record['userid']) && (string) $record['userid'] !== '') {
            $userId = (string) $record['userid'];
        } elseif (isset($record['id'])) {
            $userId = (string) $record['id'];
        }

        return array(
            'id' => isset($record['id']) ? (string) $record['id'] : '',
            'user_id' => $userId,
            'username' => isset($record['username'])
                ? (string) $record['username'] : '',
            'password_hash' => isset($record['pass'])
                ? (string) $record['pass'] : '',
            'is_active' => $this->normaliseActive(
                isset($record['isactive']) ? $record['isactive'] : null
            ),
            'puid' => isset($record['puid']) ? (string) $record['puid'] : '',
            'email_address' => isset($record['emailaddress'])
                ? (string) $record['emailaddress'] : '',
            'first_name' => isset($record['firstname'])
                ? (string) $record['firstname'] : '',
            'surname' => isset($record['surname'])
                ? (string) $record['surname'] : '',
            'access_level' => isset($record['accesslevel'])
                ? (string) $record['accesslevel'] : '',
            'created_by' => isset($record['howcreated'])
                ? (string) $record['howcreated'] : '',
            'login_count' => isset($record['logins'])
                ? (string) $record['logins'] : '0',
            'last_login' => isset($record['last_login'])
                ? $record['last_login'] : null,
            'raw' => $record,
        );
    }

    private function normaliseActive($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalised = strtolower(trim((string) $value));

        return in_array(
            $normalised,
            array('1', 'true', 'yes', 'y', 'active', 'enabled'),
            true
        );
    }

    private function assertWritesEnabled()
    {
        if (!$this->writesEnabled) {
            throw new RuntimeException(
                'NativeUserRepository writes are disabled in read-only mode.'
            );
        }
    }
}
