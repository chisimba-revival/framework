<?php
/**
 * Thin HTTP adapter for the guarded MFA application boundary.
 *
 * This class validates HTTP method, CSRF intent, and bounded scalar input.
 * MFA policy, secrets, factors, pending identity, persistence, and session
 * finalisation remain owned by MfaWebFlowService and its collaborators.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
class MfaWebControllerAdapter
{
    const CSRF_ENROL_START = 'native_mfa_enrol_start';
    const CSRF_ENROL_CONFIRM = 'native_mfa_enrol_confirm';
    const CSRF_CANCEL = 'native_mfa_cancel';

    private $flow;
    private $csrf;

    public function __construct($flow, $csrf)
    {
        foreach (array(
            'afterPassword', 'beginEnrolment', 'confirmEnrolment',
            'completeTotp', 'completeRecoveryCode', 'cancel',
            'currentChallenge',
        ) as $method) {
            if (!is_object($flow) || !method_exists($flow, $method)) {
                throw new InvalidArgumentException(
                    'MFA controller flow dependency is invalid.'
                );
            }
        }
        foreach (array('issue', 'consume') as $method) {
            if (!is_object($csrf) || !method_exists($csrf, $method)) {
                throw new InvalidArgumentException(
                    'MFA controller CSRF dependency is invalid.'
                );
            }
        }
        $this->flow = $flow;
        $this->csrf = $csrf;
    }

    public function enrolmentPage($userId, $issuer, $accountLabel)
    {
        return array(
            'view' => 'enrolment',
            'csrf_token' => $this->csrf->issue(self::CSRF_ENROL_START),
            'user_id' => $this->scalar($userId, 128),
            'issuer' => $this->scalar($issuer, 128),
            'account_label' => $this->scalar($accountLabel, 255),
        );
    }

    public function startEnrolment(
        $method,
        $csrfToken,
        $userId,
        $issuer,
        $accountLabel
    ) {
        if (!$this->post($method)
            || !$this->csrf->consume(self::CSRF_ENROL_START, $csrfToken)) {
            return $this->invalid();
        }
        $result = $this->flow->beginEnrolment(
            $this->scalar($userId, 128),
            $this->scalar($issuer, 128),
            $this->scalar($accountLabel, 255)
        );
        $result['view'] = 'enrolment';
        $result['csrf_token'] = $this->csrf->issue(
            self::CSRF_ENROL_CONFIRM
        );
        return $result;
    }

    public function confirmEnrolment(
        $method,
        $csrfToken,
        $userId,
        $enrolmentId,
        $code
    ) {
        if (!$this->post($method)
            || !$this->csrf->consume(self::CSRF_ENROL_CONFIRM, $csrfToken)) {
            return $this->invalid();
        }
        $result = $this->flow->confirmEnrolment(
            $this->scalar($userId, 128),
            $this->scalar($enrolmentId, 128),
            $this->digits($code)
        );
        if ($result === false) {
            return array('view' => 'enrolment', 'status' => 'invalid_code');
        }
        return array(
            'view' => 'recovery_codes',
            'status' => 'complete',
            'recovery_codes' => $result['recovery_codes'],
        );
    }

    public function challengePage()
    {
        $challenge = $this->flow->currentChallenge();
        if ($challenge === null) {
            return array('view' => 'expired', 'status' => 'expired');
        }
        return array_merge($challenge, array(
            'view' => 'challenge',
            'totp_csrf_token' => $this->csrf->issue(
                AuthenticationApplicationService::CSRF_TOTP
            ),
            'recovery_csrf_token' => $this->csrf->issue(
                AuthenticationApplicationService::CSRF_RECOVERY
            ),
            'cancel_csrf_token' => $this->csrf->issue(self::CSRF_CANCEL),
        ));
    }

    public function completeTotp(
        $method,
        $csrfToken,
        $transactionId,
        $code
    ) {
        if (!$this->post($method)) {
            return $this->invalid();
        }
        return $this->completion(
            $this->flow->completeTotp(
                $this->scalar($csrfToken, 256),
                $this->scalar($transactionId, 128),
                $this->digits($code)
            )
        );
    }

    public function completeRecovery(
        $method,
        $csrfToken,
        $transactionId,
        $code
    ) {
        if (!$this->post($method)) {
            return $this->invalid();
        }
        return $this->completion(
            $this->flow->completeRecoveryCode(
                $this->scalar($csrfToken, 256),
                $this->scalar($transactionId, 128),
                $this->scalar($code, 128)
            )
        );
    }

    public function cancel($method, $csrfToken)
    {
        if (!$this->post($method)
            || !$this->csrf->consume(self::CSRF_CANCEL, $csrfToken)) {
            return $this->invalid();
        }
        $this->flow->cancel();
        return array('view' => 'cancelled', 'status' => 'cancelled');
    }

    private function completion($success)
    {
        return $success
            ? array('view' => 'complete', 'status' => 'complete')
            : array('view' => 'challenge', 'status' => 'invalid_code');
    }

    private function invalid()
    {
        return array('view' => 'invalid', 'status' => 'invalid_request');
    }

    private function post($method)
    {
        return strtoupper((string) $method) === 'POST';
    }

    private function digits($value)
    {
        $value = preg_replace('/[\s-]+/', '', (string) $value);
        return preg_match('/^[0-9]{6,8}$/D', $value) ? $value : '';
    }

    private function scalar($value, $maximum)
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }
        $value = trim((string) $value);
        return strlen($value) <= $maximum ? $value : '';
    }
}
