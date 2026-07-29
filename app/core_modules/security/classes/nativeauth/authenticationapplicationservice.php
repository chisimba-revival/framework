<?php
/**
 * Application boundary for the password-to-MFA authentication transaction.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
class AuthenticationApplicationService
{
    const CSRF_BEGIN = 'native_auth_begin';
    const CSRF_TOTP = 'native_auth_totp';
    const CSRF_RECOVERY = 'native_auth_recovery';

    private $transactions;
    private $csrf;

    public function __construct($transactions, $csrf)
    {
        $this->requireMethod($transactions, 'begin');
        $this->requireMethod($transactions, 'completeTotp');
        $this->requireMethod($transactions, 'completeRecoveryCode');
        $this->requireMethod($csrf, 'issue');
        $this->requireMethod($csrf, 'consume');
        $this->transactions = $transactions;
        $this->csrf = $csrf;
    }

    public function issueBeginToken()
    {
        return $this->csrf->issue(self::CSRF_BEGIN);
    }

    public function begin(
        $csrfToken,
        $userId,
        $username,
        $mfaRequired,
        $remember,
        array $metadata = array()
    ) {
        if (!$this->csrf->consume(self::CSRF_BEGIN, $csrfToken)) {
            return array('status' => 'invalid_request');
        }

        $result = $this->transactions->begin(
            $userId,
            $username,
            (bool) $remember,
            (bool) $mfaRequired,
            $metadata
        );

        if ($result['status']
            === AuthenticationTransactionCoordinator::STATUS_MFA_REQUIRED) {
            $result['csrf_token'] = $this->csrf->issue(self::CSRF_TOTP);
            $result['recovery_csrf_token'] = $this->csrf->issue(
                self::CSRF_RECOVERY
            );
        }

        return $result;
    }

    public function completeTotp($csrfToken, $transactionId, $code)
    {
        if (!$this->csrf->consume(self::CSRF_TOTP, $csrfToken)) {
            return false;
        }

        return $this->transactions->completeTotp($transactionId, $code);
    }

    public function completeRecoveryCode($csrfToken, $transactionId, $code)
    {
        if (!$this->csrf->consume(self::CSRF_RECOVERY, $csrfToken)) {
            return false;
        }

        return $this->transactions->completeRecoveryCode(
            $transactionId,
            $code
        );
    }

    private function requireMethod($object, $method)
    {
        if (!is_object($object) || !method_exists($object, $method)) {
            throw new InvalidArgumentException(
                'Authentication dependency does not satisfy its contract.'
            );
        }
    }
}
