<?php
/**
 * Coordinates password proof, MFA verification, and final session creation.
 *
 * No authenticated identity or remembered credential is created while an MFA
 * transaction is pending. A pending transaction is consumed exactly once,
 * after successful factor verification and before final session creation.
 *
 * @author Derek Keats
 */
class AuthenticationTransactionCoordinator
{
    const STATUS_COMPLETE = 'complete';
    const STATUS_MFA_REQUIRED = 'mfa_required';

    private $pending;
    private $challenges;
    private $sessions;
    private $persistent;
    private $clock;
    private $rememberDays;

    public function __construct(
        $pending,
        $challenges,
        $sessions,
        $persistent,
        $rememberDays = 30,
        $clock = null
    ) {
        $this->requireMethod($pending, 'begin');
        $this->requireMethod($pending, 'peek');
        $this->requireMethod($pending, 'consume');
        $this->requireMethod($challenges, 'verifyTotp');
        $this->requireMethod($challenges, 'verifyRecoveryCode');
        $this->requireMethod($sessions, 'establish');
        $this->requireMethod($persistent, 'issue');
        if ($clock !== null && !is_callable($clock)) {
            throw new InvalidArgumentException(
                'Authentication transaction clock must be callable.'
            );
        }

        $this->pending = $pending;
        $this->challenges = $challenges;
        $this->sessions = $sessions;
        $this->persistent = $persistent;
        $this->clock = $clock;
        $this->rememberDays = max(1, min(365, (int) $rememberDays));
    }

    /**
     * Continue after a successful primary credential check.
     *
     * @return array Transaction status and optional opaque transaction ID.
     */
    public function begin(
        $userId,
        $username,
        $remember,
        $mfaRequired,
        array $attributes = array()
    ) {
        $this->assertIdentity($userId, $username);
        $attributes = $this->safeAttributes($attributes);

        if ($mfaRequired) {
            $transaction = $this->pending->begin(
                $userId,
                $username,
                (bool) $remember,
                $attributes
            );
            return array(
                'status' => self::STATUS_MFA_REQUIRED,
                'transaction_id' => $transaction->id,
            );
        }

        $this->finalise($userId, (bool) $remember, $attributes);
        return array(
            'status' => self::STATUS_COMPLETE,
            'transaction_id' => null,
        );
    }

    public function completeTotp($transactionId, $code)
    {
        return $this->complete($transactionId, $code, 'verifyTotp');
    }

    public function completeRecoveryCode($transactionId, $code)
    {
        return $this->complete(
            $transactionId,
            $code,
            'verifyRecoveryCode'
        );
    }

    private function complete($transactionId, $code, $verificationMethod)
    {
        $transaction = $this->pending->peek();
        if ($transaction === null
            || !is_string($transactionId)
            || !hash_equals($transaction->id, $transactionId)) {
            return false;
        }

        $verified = $this->challenges->{$verificationMethod}(
            $transaction->userId,
            $code,
            $this->now()
        );
        if (!$verified) {
            return false;
        }

        $transaction = $this->pending->consume($transactionId);
        if ($transaction === null) {
            return false;
        }

        $this->finalise(
            $transaction->userId,
            $transaction->remember,
            $transaction->metadata
        );
        return true;
    }

    private function finalise($userId, $remember, array $attributes)
    {
        if (!$this->sessions->establish($userId, $attributes)) {
            throw new RuntimeException(
                'Canonical authenticated session could not be established.'
            );
        }
        if ($remember
            && !$this->persistent->issue(
                $userId,
                $this->now(),
                $this->rememberDays
            )) {
            $this->sessions->destroy();
            throw new RuntimeException(
                'Remembered-login credential could not be issued.'
            );
        }
    }

    private function safeAttributes(array $attributes)
    {
        foreach (array(
            'password',
            'pass',
            'totp_secret',
            'mfa_secret',
            'recovery_code',
            'recovery_codes',
            'permissions',
            'roles',
            'groups',
            'is_admin',
        ) as $forbidden) {
            if (array_key_exists($forbidden, $attributes)) {
                throw new InvalidArgumentException(
                    'Forbidden authentication transaction attribute.'
                );
            }
        }
        return $attributes;
    }

    private function assertIdentity($userId, $username)
    {
        if (trim((string) $userId) === ''
            || trim((string) $username) === '') {
            throw new InvalidArgumentException(
                'Authentication transaction identity is incomplete.'
            );
        }
    }

    private function requireMethod($object, $method)
    {
        if (!is_object($object) || !method_exists($object, $method)) {
            throw new InvalidArgumentException(
                'Authentication transaction dependency is invalid.'
            );
        }
    }

    private function now()
    {
        return $this->clock === null
            ? time()
            : (int) call_user_func($this->clock);
    }
}
