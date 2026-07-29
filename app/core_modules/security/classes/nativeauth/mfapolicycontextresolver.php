<?php
/**
 * Resolves canonical facts required by the configurable MFA policy.
 *
 * The injected readers remain the owners of user, group, factor and setting
 * data. This service only assembles and validates the policy context.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
final class MfaPolicyContextResolver
{
    private $userReader;
    private $administratorReader;
    private $factorReader;
    private $policyStartReader;
    private $clock;

    public function __construct(
        $userReader,
        $administratorReader,
        $factorReader,
        $policyStartReader,
        $clock = null
    ) {
        foreach (array(
            $userReader, $administratorReader, $factorReader,
            $policyStartReader,
        ) as $reader) {
            if (!is_callable($reader)) {
                throw new InvalidArgumentException(
                    'MFA policy context reader is invalid.'
                );
            }
        }
        if ($clock !== null && !is_callable($clock)) {
            throw new InvalidArgumentException(
                'MFA policy context clock is invalid.'
            );
        }
        $this->userReader = $userReader;
        $this->administratorReader = $administratorReader;
        $this->factorReader = $factorReader;
        $this->policyStartReader = $policyStartReader;
        $this->clock = $clock;
    }

    public function resolve($authenticationResult)
    {
        if (!is_object($authenticationResult)
            || !method_exists($authenticationResult, 'getUserId')) {
            throw new InvalidArgumentException(
                'Canonical authentication result is required.'
            );
        }
        $userId = trim((string) $authenticationResult->getUserId());
        if ($userId === '') {
            throw new InvalidArgumentException(
                'Authenticated user identifier is empty.'
            );
        }
        $user = call_user_func($this->userReader, $userId);
        if (!is_array($user)) {
            throw new RuntimeException('Canonical user record was not found.');
        }
        $created = $this->timestamp(
            isset($user['creationdate']) ? $user['creationdate'] : null
        );
        $enabled = $this->timestamp(
            call_user_func($this->policyStartReader, $userId)
        );
        if ($created <= 0 && $enabled <= 0) {
            throw new RuntimeException(
                'MFA policy has no valid enforcement start time.'
            );
        }
        return array(
            'is_site_administrator' => (bool) call_user_func(
                $this->administratorReader,
                $userId
            ),
            'mfa_enrolled' => (bool) call_user_func(
                $this->factorReader,
                $userId
            ),
            'account_created_at' => $created,
            'policy_enabled_at' => $enabled,
            'now' => $this->clock === null
                ? time()
                : (int) call_user_func($this->clock),
        );
    }

    private function timestamp($value)
    {
        if (is_int($value) || ctype_digit((string) $value)) {
            return max(0, (int) $value);
        }
        $parsed = strtotime((string) $value);
        return $parsed === false ? 0 : $parsed;
    }
}
