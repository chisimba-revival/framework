<?php
require_once dirname(__FILE__) . '/canonicalauthenticationresult.php';

/**
 * Converts the existing tbl_users-shaped record into the canonical result.
 *
 * This is a pure adapter. It does not authenticate, modify sessions, or write
 * database state.
 */
class LegacyAuthenticationResultAdapter
{
    public function fromDatabaseRecord(
        array $record,
        $provider = 'liveuser_database',
        array $metadata = array()
    ) {
        $userId = isset($record['userid'])
            ? (string) $record['userid'] : '';
        $username = isset($record['username'])
            ? (string) $record['username'] : '';

        $isActive = $this->normaliseActive(
            isset($record['isactive']) ? $record['isactive'] : null
        );

        $identity = array(
            'title' => isset($record['title'])
                ? (string) $record['title'] : '',
            'first_name' => isset($record['firstname'])
                ? (string) $record['firstname'] : '',
            'surname' => isset($record['surname'])
                ? (string) $record['surname'] : '',
            'creation_date' => isset($record['creationdate'])
                ? $record['creationdate'] : null,
            'email_address' => isset($record['emailaddress'])
                ? (string) $record['emailaddress'] : '',
            'login_count' => isset($record['logins'])
                ? $record['logins'] : 0,
            'is_active' => $isActive,
            'access_level' => isset($record['accesslevel'])
                ? (string) $record['accesslevel'] : '',
        );

        if (!$isActive) {
            return CanonicalAuthenticationResult::inactive(
                $provider,
                $userId,
                $username,
                $identity,
                $metadata
            );
        }

        return CanonicalAuthenticationResult::success(
            $provider,
            $userId,
            $username,
            $identity,
            array(),
            array(),
            array(),
            $metadata
        );
    }

    private function normaliseActive($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(
            strtolower(trim((string) $value)),
            array('1', 'true', 'yes', 'y', 'active', 'enabled'),
            true
        );
    }
}
