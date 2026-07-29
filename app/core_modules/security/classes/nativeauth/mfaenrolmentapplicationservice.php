<?php
/**
 * Creates and confirms encrypted TOTP enrolments.
 *
 * Plain TOTP secrets and recovery codes are returned only to the immediate
 * enrollment response. They are never written to authenticated-session or
 * pending-login state.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
class MfaEnrolmentApplicationService
{
    private $repository;
    private $totp;
    private $protector;
    private $recovery;
    private $clock;

    public function __construct(
        MfaRepositoryInterface $repository,
        TotpService $totp,
        MfaSecretProtectorInterface $protector,
        RecoveryCodeService $recovery,
        $clock = null
    ) {
        if ($clock !== null && !is_callable($clock)) {
            throw new InvalidArgumentException('Enrollment clock is invalid.');
        }
        $this->repository = $repository;
        $this->totp = $totp;
        $this->protector = $protector;
        $this->recovery = $recovery;
        $this->clock = $clock;
    }

    public function begin($userId)
    {
        $userId = trim((string) $userId);
        if ($userId === '') {
            throw new InvalidArgumentException('Enrollment identity is empty.');
        }

        $secret = $this->totp->generateSecret();
        $protected = $this->protector->protect($secret);
        $id = bin2hex(random_bytes(16));
        $stored = $this->repository->storePendingTotp(array(
            'id' => $id,
            'user_id' => $userId,
            'encrypted_secret' => $protected['ciphertext'],
            'secret_nonce' => $protected['nonce'],
            'enrolled_at' => $this->now(),
        ));
        if (!$stored) {
            throw new RuntimeException('Pending MFA enrollment was not stored.');
        }

        return array('enrolment_id' => $id, 'secret' => $secret);
    }

    public function confirm($userId, $enrolmentId, $code)
    {
        $factor = $this->repository->findPendingTotpById(
            (string) $enrolmentId,
            (string) $userId
        );
        if (!$factor) {
            return false;
        }

        $secret = $this->protector->reveal(
            $factor->ciphertext,
            $factor->nonce
        );
        $step = $this->totp->verify($secret, $code, $this->now(), null);
        if ($step === false) {
            return false;
        }
        if (!$this->repository->verifyPendingTotp(
            $factor->id,
            $this->now()
        )) {
            return false;
        }
        if (!$this->repository->acceptTotpStep(
            $factor->id,
            $step,
            $this->now()
        )) {
            throw new RuntimeException('Initial TOTP step was not recorded.');
        }

        $codes = $this->recovery->generate(10);
        $records = array();
        foreach ($codes['hashes'] as $hash) {
            $records[] = array(
                'id' => bin2hex(random_bytes(16)),
                'code_hash' => $hash,
            );
        }
        if (!$this->repository->replaceRecoveryCodes(
            (string) $userId,
            $records,
            $this->now()
        )) {
            throw new RuntimeException('Recovery codes were not stored.');
        }

        return array('recovery_codes' => $codes['plain']);
    }

    private function now()
    {
        return $this->clock === null
            ? time()
            : (int) call_user_func($this->clock);
    }
}
