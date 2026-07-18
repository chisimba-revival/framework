<?php
require_once dirname(__FILE__) . '/nativeauthenticationserviceinterface.php';
require_once dirname(__FILE__) . '/nativeauthenticationresult.php';
require_once dirname(__FILE__) . '/nativeuserrepositoryinterface.php';
require_once dirname(__FILE__) . '/nativegrouprepositoryinterface.php';
require_once dirname(__FILE__) . '/nativepermissionrepositoryinterface.php';
require_once dirname(__FILE__) . '/nativesessionserviceinterface.php';
require_once dirname(__FILE__) . '/nativepasswordverifierinterface.php';

/**
 * Non-active implementation skeleton for Milestone 8.
 *
 * IMPORTANT: Nothing in the current Chisimba login path instantiates this
 * class. It must remain dormant until comparison tests demonstrate behavioural
 * parity and a separately reviewed feature-flag integration is committed.
 */
class NativeAuthenticationService implements NativeAuthenticationServiceInterface
{
    private $users;
    private $groups;
    private $permissions;
    private $sessions;
    private $passwords;

    public function __construct(
        NativeUserRepositoryInterface $users,
        NativeGroupRepositoryInterface $groups,
        NativePermissionRepositoryInterface $permissions,
        NativeSessionServiceInterface $sessions,
        NativePasswordVerifierInterface $passwords
    ) {
        $this->users = $users;
        $this->groups = $groups;
        $this->permissions = $permissions;
        $this->sessions = $sessions;
        $this->passwords = $passwords;
    }

    public function authenticate($username, $password, array $context = array())
    {
        $normalisedUsername = trim((string) $username);
        $user = $this->users->findByUsername($normalisedUsername);

        if (!is_array($user)) {
            $this->users->recordFailedLogin($normalisedUsername, $context);
            return new NativeAuthenticationResult(
                NativeAuthenticationResult::STATUS_INVALID_CREDENTIALS
            );
        }

        $userId = isset($user['user_id']) ? $user['user_id'] : null;
        $storedHash = isset($user['password_hash']) ? $user['password_hash'] : '';

        if ($userId === null || !$this->users->isUserActive($userId)) {
            return new NativeAuthenticationResult(
                NativeAuthenticationResult::STATUS_INACTIVE,
                $userId,
                $normalisedUsername
            );
        }

        if (!$this->passwords->verify((string) $password, $storedHash, $user)) {
            $this->users->recordFailedLogin($normalisedUsername, $context);
            return new NativeAuthenticationResult(
                NativeAuthenticationResult::STATUS_INVALID_CREDENTIALS
            );
        }

        return new NativeAuthenticationResult(
            NativeAuthenticationResult::STATUS_SUCCESS,
            $userId,
            $normalisedUsername,
            '',
            array(
                'group_ids' => $this->groups->getGroupIdsForUser($userId),
                'permissions' => $this->permissions
                    ->getEffectivePermissionsForUser($userId),
                'password_rehash_required' => $this->passwords
                    ->needsRehash($storedHash),
            )
        );
    }

    public function establishAuthenticatedSession(
        NativeAuthenticationResult $result,
        array $context = array()
    ) {
        if (!$result->isSuccess() || $result->getUserId() === null) {
            return false;
        }

        if (!$this->sessions->regenerateIdentifier()) {
            return false;
        }

        $attributes = array(
            'username' => $result->getUsername(),
            'native_auth_metadata' => $result->getMetadata(),
        );

        if (!$this->sessions->establish($result->getUserId(), $attributes)) {
            return false;
        }

        $this->users->recordSuccessfulLogin($result->getUserId(), $context);
        return true;
    }

    public function logout()
    {
        return $this->sessions->destroy();
    }

    public function getAuthenticatedUserId()
    {
        return $this->sessions->getUserId();
    }

    public function isAuthenticated()
    {
        return $this->sessions->isAuthenticated();
    }
}
