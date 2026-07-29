<?php
require_once dirname(__FILE__) . '/mfarepositoryinterface.php';
require_once dirname(__FILE__) . '/mfaenrolment.php';

/**
 * Atomic MDB2 repository for MFA factors and recovery codes.
 *
 * @author Derek Keats
 */
class Mdb2MfaRepository implements MfaRepositoryInterface
{
    private $db;
    public function __construct($connection) { $this->db = $connection; }

    public function findActiveTotpForUser($userId)
    {
        $row = $this->fetchOne(
            'SELECT id,user_id,encrypted_secret,secret_nonce,last_accepted_step '
            . 'FROM tbl_auth_mfa_enrolments WHERE user_id=? '
            . "AND factor_type='totp' AND verified_at IS NOT NULL "
            . 'AND disabled_at IS NULL LIMIT 1',
            array((string) $userId)
        );
        return $row ? new MfaEnrolment($row['id'], $row['user_id'],
            'totp', $row['encrypted_secret'], $row['secret_nonce'],
            $row['last_accepted_step']) : null;
    }

    public function findPendingTotpById($enrolmentId, $userId)
    {
        $row = $this->fetchOne(
            'SELECT id,user_id,encrypted_secret,secret_nonce,last_accepted_step '
            . 'FROM tbl_auth_mfa_enrolments WHERE id=? AND user_id=? '
            . "AND factor_type='totp' AND verified_at IS NULL "
            . 'AND disabled_at IS NULL LIMIT 1',
            array((string) $enrolmentId, (string) $userId)
        );
        return $row ? new MfaEnrolment(
            $row['id'],
            $row['user_id'],
            'totp',
            $row['encrypted_secret'],
            $row['secret_nonce'],
            $row['last_accepted_step']
        ) : null;
    }

    public function storePendingTotp(array $enrolment)
    {
        $required = array('id', 'user_id', 'encrypted_secret',
            'secret_nonce', 'enrolled_at');
        foreach ($required as $key) {
            if (!isset($enrolment[$key]) || (string) $enrolment[$key] === '') {
                throw new InvalidArgumentException(
                    'Incomplete TOTP enrolment record.'
                );
            }
        }
        return $this->execute(
            'INSERT INTO tbl_auth_mfa_enrolments '
            . '(id,user_id,factor_type,encrypted_secret,secret_nonce,'
            . 'enrolled_at,verified_at,last_accepted_step,disabled_at) '
            . "VALUES (?,?,'totp',?,?,?,NULL,NULL,NULL)",
            array(
                (string) $enrolment['id'],
                (string) $enrolment['user_id'],
                (string) $enrolment['encrypted_secret'],
                (string) $enrolment['secret_nonce'],
                gmdate('Y-m-d H:i:s', (int) $enrolment['enrolled_at']),
            )
        ) === 1;
    }

    public function verifyPendingTotp($enrolmentId, $verifiedAt)
    {
        return $this->execute(
            'UPDATE tbl_auth_mfa_enrolments SET verified_at=? '
            . 'WHERE id=? AND factor_type=? AND verified_at IS NULL '
            . 'AND disabled_at IS NULL',
            array(
                gmdate('Y-m-d H:i:s', (int) $verifiedAt),
                (string) $enrolmentId,
                'totp',
            )
        ) === 1;
    }

    public function replaceRecoveryCodes($userId, array $codes, $createdAt)
    {
        if (!$codes) {
            throw new InvalidArgumentException(
                'At least one recovery code is required.'
            );
        }
        $this->db->beginTransaction();
        try {
            $this->execute(
                'DELETE FROM tbl_auth_mfa_recovery_codes WHERE user_id=?',
                array((string) $userId)
            );
            foreach ($codes as $code) {
                if (!is_array($code) || empty($code['id'])
                    || empty($code['code_hash'])) {
                    throw new InvalidArgumentException(
                        'Invalid recovery-code record.'
                    );
                }
                $changed = $this->execute(
                    'INSERT INTO tbl_auth_mfa_recovery_codes '
                    . '(id,user_id,code_hash,created_at,used_at) '
                    . 'VALUES (?,?,?,?,NULL)',
                    array(
                        (string) $code['id'],
                        (string) $userId,
                        (string) $code['code_hash'],
                        gmdate('Y-m-d H:i:s', (int) $createdAt),
                    )
                );
                if ($changed !== 1) {
                    throw new RuntimeException(
                        'Recovery-code persistence failed.'
                    );
                }
            }
            $this->db->commit();
            return true;
        } catch (Throwable $error) {
            $this->db->rollback();
            throw $error;
        }
    }

    public function disableTotpForUser($userId, $disabledAt)
    {
        return $this->execute(
            'UPDATE tbl_auth_mfa_enrolments SET disabled_at=? '
            . "WHERE user_id=? AND factor_type='totp' "
            . 'AND disabled_at IS NULL',
            array(
                gmdate('Y-m-d H:i:s', (int) $disabledAt),
                (string) $userId,
            )
        );
    }

    public function acceptTotpStep($enrolmentId, $step, $acceptedAt)
    {
        return $this->execute(
            'UPDATE tbl_auth_mfa_enrolments SET last_accepted_step=? '
            . 'WHERE id=? AND disabled_at IS NULL '
            . 'AND (last_accepted_step IS NULL OR last_accepted_step<?)',
            array((int) $step, (string) $enrolmentId, (int) $step)
        ) === 1;
    }

    public function consumeRecoveryCode($userId, $code, $usedAt)
    {
        $this->db->beginTransaction();
        try {
            $rows = $this->fetchAll(
                'SELECT id,code_hash FROM tbl_auth_mfa_recovery_codes '
                . 'WHERE user_id=? AND used_at IS NULL FOR UPDATE',
                array((string) $userId)
            );
            foreach ($rows as $row) {
                if (password_verify((string) $code, $row['code_hash'])) {
                    $changed = $this->execute(
                        'UPDATE tbl_auth_mfa_recovery_codes SET used_at=? '
                        . 'WHERE id=? AND used_at IS NULL',
                        array(gmdate('Y-m-d H:i:s', (int) $usedAt), $row['id'])
                    );
                    if ($changed !== 1) throw new RuntimeException('code reuse');
                    $this->db->commit();
                    return true;
                }
            }
            $this->db->rollback();
            return false;
        } catch (Throwable $error) {
            $this->db->rollback();
            throw $error;
        }
    }

    private function prepared($sql, array $args, $mode)
    {
        $statement = $this->db->prepare($sql, null, $mode);
        $result = $statement->execute($args);
        $statement->free();
        if (PEAR::isError($result)) throw new RuntimeException($result->getMessage());
        return $result;
    }
    private function fetchOne($sql, array $args)
    {
        $result = $this->prepared($sql, $args, MDB2_PREPARE_RESULT);
        $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC); $result->free();
        return $row ?: null;
    }
    private function fetchAll($sql, array $args)
    {
        $result = $this->prepared($sql, $args, MDB2_PREPARE_RESULT);
        $rows = $result->fetchAll(MDB2_FETCHMODE_ASSOC); $result->free();
        return $rows ?: array();
    }
    private function execute($sql, array $args)
    {
        return (int) $this->prepared($sql, $args, MDB2_PREPARE_MANIP);
    }
}
