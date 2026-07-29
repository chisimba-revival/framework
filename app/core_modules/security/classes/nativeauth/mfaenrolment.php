<?php
/**
 * Immutable MFA enrolment domain record.
 *
 * Persistence adapters must atomically update lastAcceptedStep so a TOTP
 * time-step cannot be replayed.
 *
 * @author Derek Keats
 */
class MfaEnrolment
{
    public $id;
    public $userId;
    public $factorType;
    public $ciphertext;
    public $nonce;
    public $lastAcceptedStep;

    public function __construct(
        $id,
        $userId,
        $factorType,
        $ciphertext,
        $nonce,
        $lastAcceptedStep = null
    ) {
        $this->id = (string) $id;
        $this->userId = (string) $userId;
        $this->factorType = (string) $factorType;
        $this->ciphertext = (string) $ciphertext;
        $this->nonce = (string) $nonce;
        $this->lastAcceptedStep = $lastAcceptedStep === null
            ? null
            : (int) $lastAcceptedStep;
    }
}
