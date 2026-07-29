<?php
/**
 * Guarded application boundary for MFA enrollment and login web routes.
 *
 * Controllers pass credential proofs and user input into this class. They do
 * not handle TOTP secrets, recovery-code persistence, pending identity, or
 * authenticated-session finalisation themselves.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
class MfaWebFlowService
{
    const STATUS_COMPLETE = 'complete';
    const STATUS_CHALLENGE = 'mfa_required';
    const STATUS_ENROLMENT = 'mfa_enrolment_required';
    const STATUS_INVALID = 'invalid_request';
    const STATUS_CANCELLED = 'cancelled';

    private $authentication;
    private $enrolment;
    private $provisioning;
    private $pending;
    private $factors;

    public function __construct(
        $authentication,
        $enrolment,
        $provisioning,
        $pending,
        $factors
    ) {
        $this->requireMethods($authentication, array(
            'begin', 'completeTotp', 'completeRecoveryCode',
        ));
        $this->requireMethods($enrolment, array('begin', 'confirm'));
        $this->requireMethods($provisioning, array('build'));
        $this->requireMethods($pending, array('peek', 'clear'));
        $this->requireMethods($factors, array('findActiveTotpForUser'));
        $this->authentication = $authentication;
        $this->enrolment = $enrolment;
        $this->provisioning = $provisioning;
        $this->pending = $pending;
        $this->factors = $factors;
    }

    /**
     * Continue from a successful, non-finalising password proof.
     */
    public function afterPassword(
        $csrfToken,
        array $proof,
        $remember,
        array $policy,
        array $metadata = array()
    ) {
        $result = isset($proof['result']) ? $proof['result'] : null;
        if (!is_object($result)
            || !method_exists($result, 'isSuccess')
            || !$result->isSuccess()) {
            return array('status' => self::STATUS_INVALID);
        }

        $userId = trim((string) $result->getUserId());
        $username = trim((string) $result->getUsername());
        if ($userId === '' || $username === '') {
            return array('status' => self::STATUS_INVALID);
        }

        $required = !empty($policy['required']);
        $factor = $this->factors->findActiveTotpForUser($userId);
        if ($required && !$factor) {
            /*
             * No authenticated or pending-login identity is created here.
             * Enrollment has its own CSRF-protected route and confirmation.
             */
            return array(
                'status' => self::STATUS_ENROLMENT,
                'user_id' => $userId,
                'username' => $username,
            );
        }

        return $this->authentication->begin(
            $csrfToken,
            $userId,
            $username,
            $required && (bool) $factor,
            (bool) $remember,
            $this->safeMetadata($metadata, $result)
        );
    }

    /**
     * Create enrollment data for an authenticated enrollment-authorisation
     * route. The returned secret must only be rendered on that response.
     */
    public function beginEnrolment($userId, $issuer, $accountLabel)
    {
        $created = $this->enrolment->begin($userId);
        if (!is_array($created)
            || empty($created['enrolment_id'])
            || empty($created['secret'])) {
            throw new RuntimeException('MFA enrollment could not be started.');
        }
        $provisioning = $this->provisioning->build(
            $created['secret'],
            $issuer,
            $accountLabel
        );
        unset($created['secret']);
        return array_merge($created, $provisioning);
    }

    public function confirmEnrolment($userId, $enrolmentId, $code)
    {
        $result = $this->enrolment->confirm(
            $userId,
            $enrolmentId,
            $code
        );
        if (!is_array($result) || empty($result['recovery_codes'])) {
            return false;
        }
        return $result;
    }

    public function completeTotp($csrfToken, $transactionId, $code)
    {
        return $this->authentication->completeTotp(
            $csrfToken,
            $transactionId,
            $code
        );
    }

    public function completeRecoveryCode($csrfToken, $transactionId, $code)
    {
        return $this->authentication->completeRecoveryCode(
            $csrfToken,
            $transactionId,
            $code
        );
    }

    public function cancel()
    {
        $this->pending->clear();
        return array('status' => self::STATUS_CANCELLED);
    }

    public function currentChallenge()
    {
        $transaction = $this->pending->peek();
        if ($transaction === null) {
            return null;
        }
        return array(
            'transaction_id' => $transaction->id,
            'username' => $transaction->username,
            'expires_at' => $transaction->expiresAt,
        );
    }

    private function safeMetadata(array $metadata, $result)
    {
        $safe = array_intersect_key($metadata, array_flip(array(
            'ip', 'user_agent', 'return_url',
        )));
        $safe['username'] = $result->getUsername();
        $safe['provider'] = $result->getProviderId();
        return $safe;
    }

    private function requireMethods($object, array $methods)
    {
        foreach ($methods as $method) {
            if (!is_object($object) || !method_exists($object, $method)) {
                throw new InvalidArgumentException(
                    'MFA web-flow dependency is invalid.'
                );
            }
        }
    }
}
