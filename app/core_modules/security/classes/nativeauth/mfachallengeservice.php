<?php
/**
 * Provider-neutral MFA challenge orchestration.
 *
 * Atomic repository methods prevent replay and recovery-code reuse.
 *
 * @author Derek Keats
 */
class MfaChallengeService
{
    private $repository;
    private $totp;
    private $protector;

    public function __construct(
        MfaRepositoryInterface $repository,
        TotpService $totp,
        MfaSecretProtectorInterface $protector
    ) {
        $this->repository = $repository;
        $this->totp = $totp;
        $this->protector = $protector;
    }

    public function verifyTotp($userId, $code, $now)
    {
        $factor = $this->repository->findActiveTotpForUser($userId);
        if (!$factor) {
            return false;
        }
        $secret = $this->protector->reveal(
            $factor->ciphertext,
            $factor->nonce
        );
        $step = $this->totp->verify(
            $secret,
            $code,
            $now,
            $factor->lastAcceptedStep
        );
        if ($step === false) {
            return false;
        }
        return $this->repository->acceptTotpStep(
            $factor->id,
            $step,
            $now
        );
    }

    public function verifyRecoveryCode($userId, $code, $now)
    {
        return $this->repository->consumeRecoveryCode(
            $userId,
            $code,
            $now
        );
    }
}
