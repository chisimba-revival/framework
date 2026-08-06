<?php
/**
 * Canonical, idempotent initial-administrator provisioning.
 *
 * This coordinator owns no table. GroupService provisions installation groups,
 * UserProvisioningService creates the local user, IdentityService owns the
 * permission identity, and GroupService owns Site Admin membership.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
class initialadminprovisioningservice extends ChisimbaObject
{
    private $objUserProvisioningService;
    private $objUserService;
    private $objIdentityService;
    private $objGroupService;
    private $objPermissionService;

    public function init()
    {
        $this->objUserProvisioningService = $this->getObject(
            'userprovisioningservice',
            'security'
        );
        $this->objUserService = $this->getObject('userservice', 'security');
        $this->objIdentityService = $this->getObject(
            'identityservice',
            'security'
        );
        $this->objGroupService = $this->getObject(
            'groupservice',
            'groupadmin'
        );
        $this->objPermissionService = $this->getObject(
            'permissionservice',
            'security'
        );
    }

    /**
     * Create or verify the one canonical initial administrator.
     *
     * The default password is installation policy retained from the existing
     * installer contract. It is transformed only by AuthenticationService
     * inside UserProvisioningService; no pre-hashed credential is seeded.
     */
    public function ensureInitialAdministrator($userId = '1')
    {
        $userId = trim((string) $userId);
        if ($userId !== '1') {
            return $this->result(false, 'invalid_bootstrap_user');
        }

        $groupDefinitions = $this->loadInstallerGroupDefinitions();
        if (!is_array($groupDefinitions)) {
            return $this->result(false, 'group_manifest_invalid');
        }
        $groups = $this->objGroupService->ensureGroups($groupDefinitions);
        if (!is_array($groups) || empty($groups['ok'])) {
            return $this->result(
                false,
                is_array($groups) && isset($groups['code'])
                    ? $groups['code']
                    : 'group_provisioning_failure'
            );
        }
        if (!isset($groups['groups']['Site Admin'])
            || !isset($groups['groups']['Guest'])) {
            return $this->result(false, 'baseline_group_missing');
        }

        $user = $this->objUserService->findByUserId($userId);
        $usernameUser = $this->objUserService->findByUsername('admin');
        if ($user === null && $usernameUser === null) {
            $created = $this->objUserProvisioningService->createLocalUser(
                array(
                    'userId' => $userId,
                    'username' => 'admin',
                    'firstName' => 'Site',
                    'surname' => 'Administrator',
                    'howCreated' => 'installer',
                    'isActive' => true,
                ),
                'a'
            );
            if (!is_array($created) || empty($created['ok'])) {
                return $this->result(
                    false,
                    is_array($created) && isset($created['code'])
                        ? $created['code']
                        : 'administrator_create_failed'
                );
            }
            $user = $this->objUserService->findByUserId($userId);
            $usernameUser = $this->objUserService->findByUsername('admin');
        }

        if (!is_array($user)
            || !is_array($usernameUser)
            || !isset($user['userid'], $user['username'])
            || !isset($usernameUser['userid'])
            || (string) $user['userid'] !== $userId
            || strtolower((string) $user['username']) !== 'admin'
            || (string) $usernameUser['userid'] !== $userId) {
            return $this->result(false, 'bootstrap_user_conflict');
        }

        $permissionUserId = $this->objIdentityService
            ->ensurePermissionIdentity($userId);
        if ($permissionUserId === null) {
            return $this->result(false, 'permission_identity_failed');
        }

        $groupId = $this->positiveInteger(
            $this->objGroupService->groupIdForName('Site Admin')
        );
        if ($groupId === null) {
            return $this->result(false, 'site_admin_group_missing');
        }
        $membership = $this->objGroupService->addBootstrapMember(
            $groupId,
            $userId,
            'Site Admin'
        );
        if (!is_array($membership)
            || (empty($membership['ok'])
                && (!isset($membership['code'])
                    || $membership['code'] !== 'already_member'))) {
            $membershipCode = is_array($membership)
                && isset($membership['code'])
                ? (string) $membership['code']
                : 'unknown_failure';
            return $this->result(
                false,
                'site_admin_membership_failed:' . $membershipCode
            );
        }

        if (!$this->objPermissionService
            ->ensureAllDefinedRightsForGroup($groupId)) {
            return $this->result(
                false,
                'site_admin_permission_grants_failed'
            );
        }

        return $this->result(
            true,
            'initial_administrator_ready',
            $userId,
            $permissionUserId,
            $groupId
        );
    }

    private function loadInstallerGroupDefinitions()
    {
        $manifest = dirname(dirname(dirname(dirname(__FILE__))))
            . '/installer/config/permission-groups.xml';
        if (!is_readable($manifest) || !function_exists('simplexml_load_file')) {
            return false;
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_file(
            $manifest,
            'SimpleXMLElement',
            LIBXML_NONET | LIBXML_NOBLANKS
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($xml === false || $xml->getName() !== 'permissionGroups') {
            return false;
        }

        $definitions = array();
        $required = array('Site Admin' => false, 'Guest' => false);
        foreach ($xml->group as $group) {
            $name = trim((string) $group['name']);
            $description = trim((string) $group['description']);
            $isRequired = strtolower(trim((string) $group['required'])) === 'true';
            if (array_key_exists($name, $required) && $isRequired) {
                $required[$name] = true;
            }
            $definitions[] = array(
                'name' => $name,
                'description' => $description,
            );
        }
        if (!$required['Site Admin']
            || !$required['Guest']
            || count($definitions) === 0) {
            return false;
        }
        return $definitions;
    }

    private function positiveInteger($value)
    {
        if (!is_scalar($value) || !preg_match('/^[1-9]\d*$/', (string) $value)) {
            return null;
        }
        return (int) $value;
    }

    private function result(
        $ok,
        $code,
        $userId = null,
        $permissionUserId = null,
        $groupId = null
    ) {
        return array(
            'ok' => (bool) $ok,
            'code' => (string) $code,
            'userId' => $userId,
            'permissionUserId' => $permissionUserId,
            'groupId' => $groupId,
        );
    }
}
?>
