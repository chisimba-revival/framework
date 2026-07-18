<?php
/**
 * Read-only user data required by native authentication.
 */
interface NativeUserRepositoryInterface
{
    /** @return array|null Canonical user record or null when not found. */
    public function findByUsername($username);

    /** @return array|null Canonical user record or null when not found. */
    public function findById($userId);

    /** @return bool */
    public function isUserActive($userId);

    /**
     * Persist a replacement password hash after a successful legacy-password
     * verification or when password_needs_rehash() returns true.
     *
     * @return bool
     */
    public function updatePasswordHash($userId, $passwordHash);

    /** @return bool */
    public function recordSuccessfulLogin($userId, array $context = array());

    /** @return bool */
    public function recordFailedLogin($username, array $context = array());
}
