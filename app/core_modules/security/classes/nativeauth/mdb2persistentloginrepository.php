<?php
require_once dirname(__FILE__) . '/persistentloginrepositoryinterface.php';

/**
 * MDB2 persistence for rotating remembered-login credentials.
 *
 * Rotation locks and consumes the old credential in the same transaction as
 * inserting its replacement. This class canonically owns its table writes.
 *
 * @author Derek Keats
 */
class Mdb2PersistentLoginRepository implements PersistentLoginRepositoryInterface
{
    private $db;

    public function __construct($connection)
    {
        $this->db = $connection;
    }

    public function store(array $record)
    {
        return $this->execute(
            'INSERT INTO tbl_auth_persistent_logins '
            . '(id,user_id,selector,verifier_hash,issued_at,expires_at) '
            . 'VALUES (?,?,?,?,?,?)',
            array($record['id'], $record['user_id'], $record['selector'],
                $record['verifier_hash'], $this->date($record['issued_at']),
                $this->date($record['expires_at']))
        ) === 1;
    }

    public function findActiveBySelector($selector, $now)
    {
        return $this->fetchOne(
            'SELECT id,user_id,selector,verifier_hash,expires_at '
            . 'FROM tbl_auth_persistent_logins '
            . 'WHERE selector=? AND revoked_at IS NULL AND expires_at>? LIMIT 1',
            array((string) $selector, $this->date($now))
        );
    }

    public function rotate($id, array $replacement, $usedAt)
    {
        $this->begin();
        try {
            $current = $this->fetchOne(
                'SELECT id FROM tbl_auth_persistent_logins '
                . 'WHERE id=? AND revoked_at IS NULL FOR UPDATE',
                array((string) $id)
            );
            if (!$current) {
                $this->rollback();
                return false;
            }
            if (!$this->store($replacement)) {
                throw new RuntimeException('replacement token insert failed');
            }
            $changed = $this->execute(
                'UPDATE tbl_auth_persistent_logins SET last_used_at=?,'
                . 'revoked_at=?,replaced_by_id=? WHERE id=? AND revoked_at IS NULL',
                array($this->date($usedAt), $this->date($usedAt),
                    $replacement['id'], (string) $id)
            );
            if ($changed !== 1) {
                throw new RuntimeException('old token was already consumed');
            }
            $this->commit();
            return true;
        } catch (Throwable $error) {
            $this->rollback();
            throw $error;
        }
    }

    public function revoke($id, $revokedAt)
    {
        return $this->execute(
            'UPDATE tbl_auth_persistent_logins SET revoked_at=? '
            . 'WHERE id=? AND revoked_at IS NULL',
            array($this->date($revokedAt), (string) $id)
        ) === 1;
    }

    public function revokeAllForUser($userId, $revokedAt)
    {
        return $this->execute(
            'UPDATE tbl_auth_persistent_logins SET revoked_at=? '
            . 'WHERE user_id=? AND revoked_at IS NULL',
            array($this->date($revokedAt), (string) $userId)
        );
    }

    public function purgeExpired($now)
    {
        return $this->execute(
            'DELETE FROM tbl_auth_persistent_logins WHERE expires_at<=?',
            array($this->date($now))
        );
    }

    private function fetchOne($sql, array $args)
    {
        $statement = $this->db->prepare($sql, null, MDB2_PREPARE_RESULT);
        $result = $statement->execute($args);
        $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        $result->free();
        $statement->free();
        return $row ?: null;
    }

    private function execute($sql, array $args)
    {
        $statement = $this->db->prepare($sql, null, MDB2_PREPARE_MANIP);
        $affected = $statement->execute($args);
        $statement->free();
        if (PEAR::isError($affected)) {
            throw new RuntimeException($affected->getMessage());
        }
        return (int) $affected;
    }

    private function begin() { $this->db->beginTransaction(); }
    private function commit() { $this->db->commit(); }
    private function rollback() { $this->db->rollback(); }
    private function date($timestamp) { return gmdate('Y-m-d H:i:s', (int) $timestamp); }
}
