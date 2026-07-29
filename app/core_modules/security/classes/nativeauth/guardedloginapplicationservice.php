<?php
/**
 * Sole application boundary from primary credential proof to guarded MFA flow.
 *
 * The credential verifier proves a password without creating authenticated
 * state. The policy context resolver supplies user facts; the policy decides
 * enforcement. Controllers only pass bounded request values and route results.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
final class GuardedLoginApplicationService
{
    const STATUS_INVALID = 'invalid_request';

    private $credentials;
    private $policy;
    private $policyContext;
    private $flow;

    public function __construct(
        $credentials,
        $policy,
        $policyContext,
        $flow
    ) {
        $this->requireMethod($credentials, 'verifyCredentials');
        $this->requireMethod($policy, 'evaluate');
        $this->requireMethod($policyContext, 'resolve');
        $this->requireMethod($flow, 'afterPassword');
        $this->credentials = $credentials;
        $this->policy = $policy;
        $this->policyContext = $policyContext;
        $this->flow = $flow;
    }

    public function begin(
        $csrfToken,
        $username,
        $password,
        $remember,
        array $metadata = array()
    ) {
        $username = trim((string) $username);
        if ($username === '' || !is_string($password)) {
            return array('status' => self::STATUS_INVALID);
        }

        $proof = $this->credentials->verifyCredentials(
            $username,
            $password
        );
        if (!is_array($proof)
            || !isset($proof['result'])
            || !is_object($proof['result'])
            || !method_exists($proof['result'], 'isSuccess')
            || !$proof['result']->isSuccess()) {
            return array('status' => self::STATUS_INVALID);
        }

        $context = $this->policyContext->resolve($proof['result']);
        if (!is_array($context)) {
            throw new RuntimeException('MFA policy context is invalid.');
        }
        $evaluation = $this->policy->evaluate(
            $proof['result'],
            $context
        );
        if (!is_array($evaluation) || empty($evaluation['status'])) {
            throw new RuntimeException('MFA policy evaluation is invalid.');
        }

        $required = $evaluation['status']
            === ConfigurableMfaEnforcementPolicy::STATUS_CHALLENGE_REQUIRED;
        $result = $this->flow->afterPassword(
            $csrfToken,
            $proof,
            (bool) $remember,
            array('required' => $required),
            $this->safeMetadata($metadata)
        );
        $result['mfa_policy_status'] = $evaluation['status'];
        if (isset($evaluation['deadline'])
            && $evaluation['deadline'] !== null) {
            $result['mfa_grace_deadline'] = (int) $evaluation['deadline'];
        }
        return $result;
    }

    private function safeMetadata(array $metadata)
    {
        return array_intersect_key($metadata, array_flip(array(
            'ip', 'user_agent', 'return_url',
        )));
    }

    private function requireMethod($object, $method)
    {
        if (!is_object($object) || !method_exists($object, $method)) {
            throw new InvalidArgumentException(
                'Guarded login dependency is invalid.'
            );
        }
    }
}
