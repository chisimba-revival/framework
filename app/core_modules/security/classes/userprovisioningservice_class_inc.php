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

        $input['passwordHash'] = $passwordHash;
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

        $membership = ((string) $userId === '1')
            ? $this->objGroupService->addBootstrapMember(
                $guestGroupId,
                $userId,
                'Guest'
            )
            : $this->objGroupService->addMember(
                $guestGroupId,
                $userId
            );
        if (empty($membership['ok'])
            && (!isset($membership['code'])
                || $membership['code'] !== 'already_member')) {
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
            $membershipRemoved = ((string) $userId === '1')
                ? $this->objGroupService->removeBootstrapMember(
                    $groupId,
                    $userId,
                    'Guest'
                )
                : $this->objGroupService->removeMember(
                    $groupId,
                    $userId
                );
            $membershipCode = isset($membershipRemoved['code'])
                ? $membershipRemoved['code']
                : '';
            if (empty($membershipRemoved['ok'])
                && $membershipCode !== 'not_a_member') {
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
