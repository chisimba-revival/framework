<?php
/**
 * Production readers for canonical MFA policy context facts.
 *
 * This boundary translates existing canonical service contracts into the
 * callables consumed by MfaPolicyContextResolver. It owns no persistence and
 * contains no policy decision.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
final class NativeAuthPolicyContextReaders
{
    private $users;
    private $groups;
    private $settings;
    private $factors;

    public function __construct($users, $groups, $settings, $factors)
    {
        $this->requireMethods($users, array('findByUserId'));
        $this->requireMethods(
            $groups,
            array('groupIdForName', 'isGroupMember')
        );
        $this->requireMethods($settings, array('getValue'));
        $this->requireMethods($factors, array('findActiveTotpForUser'));
        $this->users = $users;
        $this->groups = $groups;
        $this->settings = $settings;
        $this->factors = $factors;
    }

    public function userRecord($userId)
    {
        $record = $this->users->findByUserId($this->userId($userId));
        return is_array($record) ? $record : null;
    }

    public function isSiteAdministrator($userId)
    {
        $groupId = $this->groups->groupIdForName('Site Admin');
        if ($groupId === false || (int) $groupId <= 0) {
            throw new RuntimeException(
                'Canonical Site Admin group is unavailable.'
            );
        }
        return (bool) $this->groups->isGroupMember(
            $this->userId($userId),
            (int) $groupId
        );
    }

    public function hasActiveFactor($userId)
    {
        return $this->factors->findActiveTotpForUser(
            $this->userId($userId)
        ) !== null;
    }

    public function policyEnabledAt($userId)
    {
        $this->userId($userId);
        $value = $this->settings->getValue(
            'mfa_policy_enabled_at',
            'security',
            0
        );
        if (is_int($value) || ctype_digit((string) $value)) {
            return max(0, (int) $value);
        }
        $parsed = strtotime(trim((string) $value));
        return $parsed === false ? 0 : $parsed;
    }

    private function userId($userId)
    {
        if (!is_scalar($userId)) {
            throw new InvalidArgumentException('User identifier is invalid.');
        }
        $userId = trim((string) $userId);
        if ($userId === '' || strlen($userId) > 255
            || preg_match('/[\x00-\x1F\x7F]/', $userId)) {
            throw new InvalidArgumentException('User identifier is invalid.');
        }
        return $userId;
    }

    private function requireMethods($object, array $methods)
    {
        foreach ($methods as $method) {
            if (!is_object($object) || !method_exists($object, $method)) {
                throw new InvalidArgumentException(
                    'MFA policy reader dependency is invalid.'
                );
            }
        }
    }
}
