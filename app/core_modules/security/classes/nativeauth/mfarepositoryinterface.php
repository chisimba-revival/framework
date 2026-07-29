<?php
/**
 * Atomic repository contract for MFA factors and recovery codes.
 *
 * @author Derek Keats
 */
interface MfaRepositoryInterface
{
    public function findActiveTotpForUser($userId);

    public function findPendingTotpById($enrolmentId, $userId);

    public function storePendingTotp(array $enrolment);

    public function verifyPendingTotp($enrolmentId, $verifiedAt);

    public function replaceRecoveryCodes($userId, array $codes, $createdAt);

    public function disableTotpForUser($userId, $disabledAt);

    public function acceptTotpStep($enrolmentId, $step, $acceptedAt);

    public function consumeRecoveryCode($userId, $code, $usedAt);
}
