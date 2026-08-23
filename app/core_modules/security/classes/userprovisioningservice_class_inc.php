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
    }

    /**
     * Create a local user and permission identity.
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

        return $this->result(true, 'user_created', $userId, $storageId);
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
