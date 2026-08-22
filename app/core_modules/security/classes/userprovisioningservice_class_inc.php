<?php
/**
 * Canonical orchestration boundary for creating a complete Chisimba user.
 *
 * Table ownership remains with UserService, IdentityService and GroupService.
 * AuthenticationService owns credential transformation. This class coordinates
 * those services without writing any owned table directly.
 *
 * @author Derek Keats
 * @category  Chisimba
 * @package   security
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
class userprovisioningservice extends ChisimbaObject
{
    private $objUserService;
    private $objAuthenticationService;
    private $objIdentityService;
    private $objGroupService;

    public function init()
    {
        $this->objUserService = $this->getObject('userservice', 'security');
        $this->objAuthenticationService = $this->getObject(
            'authenticationservice',
            'security'
        );
        $this->objIdentityService = $this->getObject(
            'identityservice',
            'security'
        );
        $this->objGroupService = $this->getObject(
            'groupservice',
            'groupadmin'
        );
    }

    /**
     * Create a local user, permission identity and initial Guest membership.
     *
     * @return array Result with ok, code, userId and storageId keys.
     */
    public function createLocalUser(array $input, $plainTextPassword)
    {
        try {
            $passwordHash = $this->objAuthenticationService
                ->createPasswordHash($plainTextPassword);
        } catch (InvalidArgumentException $exception) {
            return $this->result(false, 'invalid_password');
        }

        return $this->createLocalUserWithPasswordHash($input, $passwordHash);
    }

    /**
     * Create a complete local user from an already transformed credential.
     *
     * This supports verified-registration workflows that must never retain
     * plaintext between the initial request and later account provisioning.
     */
    public function createLocalUserWithPasswordHash(array $input, $passwordHash)
    {
        if (!is_scalar($passwordHash)
            || $this->objAuthenticationService->passwordHashScheme(
                (string) $passwordHash
            ) !== 'password_hash') {
            return $this->result(false, 'invalid_password_hash');
        }

        $input['passwordHash'] = (string) $passwordHash;
        $created = $this->objUserService->createUser($input);
        if (empty($created['ok'])) {
            return $this->result(
                false,
                isset($created['code']) ? $created['code'] : 'create_failed'
            );
        }

        $userId = (string) $created['userId'];
        $user = $this->objUserService->findByUserId($userId);
        if (!is_array($user) || empty($user['id'])) {
            return $this->result(false, 'created_user_not_found', $userId);
        }
        $storageId = (string) $user['id'];

        /*
         * A newly allocated userid must not inherit an orphan permission
         * identity. Refuse provisioning rather than treating it as ours.
         */
        if ($this->objIdentityService->hasPermissionIdentity($userId)) {
            $this->objUserService->rollbackProvisionedUser(
                $userId,
                $storageId
            );
            return $this->result(false, 'preexisting_identity', $userId);
        }

        $permissionUserId = $this->objIdentityService
            ->ensurePermissionIdentity($userId);
        if ($permissionUserId === null) {
            $this->objUserService->rollbackProvisionedUser(
                $userId,
                $storageId
            );
            return $this->result(false, 'identity_create_failed', $userId);
        }

        $guestGroupId = $this->objGroupService->groupIdForName('Guest');
        if ($guestGroupId === false || $guestGroupId === null) {
            return $this->compensate(
                'guest_group_not_found',
                $userId,
                $storageId,
                $permissionUserId,
                null
            );
        }

        if ((string) $userId === '1') {
            $membership = $this->objGroupService->addBootstrapMember(
                $guestGroupId,
                $userId,
                'Guest'
            );
        } else {
            $membership = $this->objGroupService->ensureMembership(
                $guestGroupId,
                $permissionUserId
            );
        }
        $membershipReady = (string) $userId === '1'
            ? (!empty($membership['ok'])
                || (($membership['code'] ?? '') === 'already_member'))
            : $membership === true;
        if (!$membershipReady) {
            return $this->compensate(
                'guest_membership_failed',
                $userId,
                $storageId,
                $permissionUserId,
                $guestGroupId
            );
        }

        return $this->result(true, 'user_created', $userId, $storageId);
    }

    /**
     * Compensate only records allocated inside the current provisioning call.
     */
    private function compensate(
        $failureCode,
        $userId,
        $storageId,
        $permissionUserId,
        $groupId
    ) {
        if ($groupId !== null) {
            if ((string) $userId === '1') {
                $membershipRemoved = $this->objGroupService->removeBootstrapMember(
                    $groupId,
                    $userId,
                    'Guest'
                );
                $membershipRemovedReady = !empty($membershipRemoved['ok'])
                    || (($membershipRemoved['code'] ?? '') === 'not_a_member');
            } else {
                $membershipRemovedReady = $this->objGroupService->removeMembership(
                    $groupId,
                    $permissionUserId
                );
            }
            if (!$membershipRemovedReady) {
                return $this->result(
                    false,
                    $failureCode . '_rollback_membership_failed',
                    $userId,
                    $storageId
                );
            }
        }

        if ($this->objGroupService->hasAnyMembership($userId)) {
            return $this->result(
                false,
                $failureCode . '_rollback_memberships_remain',
                $userId,
                $storageId
            );
        }

        $identityRemoved = $this->objIdentityService
            ->rollbackProvisionedIdentity($userId, $permissionUserId);
        if (!$identityRemoved) {
            return $this->result(
                false,
                $failureCode . '_rollback_identity_failed',
                $userId,
                $storageId
            );
        }

        $userRemoved = $this->objUserService->rollbackProvisionedUser(
            $userId,
            $storageId
        );
        if (empty($userRemoved['ok'])) {
            return $this->result(
                false,
                $failureCode . '_rollback_user_failed',
                $userId,
                $storageId
            );
        }

        return $this->result(false, $failureCode, $userId);
    }

    private function result($ok, $code, $userId = null, $storageId = null)
    {
        return array(
            'ok' => (bool) $ok,
            'code' => (string) $code,
            'userId' => $userId,
            'storageId' => $storageId,
        );
    }
}
