<?php
require_once dirname(__FILE__) . '/mfaenforcementpolicyinterface.php';

/**
 * Configurable provider-neutral MFA enforcement policy.
 *
 * Site Administrators and all other users have independent enforcement
 * settings. A required user receives a grace period measured from the later
 * of account creation or the applicable policy-enforcement timestamp.
 *
 * @author Derek Keats
 */
class ConfigurableMfaEnforcementPolicy
    implements MfaEnforcementPolicyInterface
{
    const STATUS_NOT_REQUIRED = 'not_required';
    const STATUS_ENROLLED = 'enrolled';
    const STATUS_GRACE = 'grace';
    const STATUS_CHALLENGE_REQUIRED = 'challenge_required';
    const DEFAULT_GRACE_DAYS = 7;

    private $requireAdministrators;
    private $requireOtherUsers;
    private $graceDays;

    public function __construct(
        $requireAdministrators,
        $requireOtherUsers,
        $graceDays = self::DEFAULT_GRACE_DAYS
    ) {
        $this->requireAdministrators = (bool) $requireAdministrators;
        $this->requireOtherUsers = (bool) $requireOtherUsers;
        $this->graceDays = max(0, (int) $graceDays);
    }

    public function requiresChallenge($result, array $context = array())
    {
        $evaluation = $this->evaluate($result, $context);
        return $evaluation['status'] === self::STATUS_CHALLENGE_REQUIRED;
    }

    public function evaluate($result, array $context = array())
    {
        $required = !empty($context['is_site_administrator'])
            ? $this->requireAdministrators
            : $this->requireOtherUsers;

        if (!$required) {
            return $this->response(self::STATUS_NOT_REQUIRED, null);
        }

        if (!empty($context['mfa_enrolled'])) {
            return $this->response(
                self::STATUS_CHALLENGE_REQUIRED,
                null
            );
        }

        $start = $this->enforcementStart($context);
        $deadline = $start + ($this->graceDays * 86400);
        $now = isset($context['now'])
            ? (int) $context['now']
            : time();

        if ($now < $deadline) {
            return $this->response(self::STATUS_GRACE, $deadline);
        }

        return $this->response(
            self::STATUS_CHALLENGE_REQUIRED,
            $deadline
        );
    }

    private function enforcementStart(array $context)
    {
        $created = isset($context['account_created_at'])
            ? (int) $context['account_created_at']
            : 0;
        $enabled = isset($context['policy_enabled_at'])
            ? (int) $context['policy_enabled_at']
            : 0;
        $start = max($created, $enabled);

        if ($start <= 0) {
            throw new InvalidArgumentException(
                'MFA enforcement requires an account or policy start time.'
            );
        }

        return $start;
    }

    private function response($status, $deadline)
    {
        return array(
            'status' => $status,
            'deadline' => $deadline,
        );
    }
}
