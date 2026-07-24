<?php

/**
 * Repository contract for Chisimba user records.
 *
 * The existing tbl_users schema remains authoritative during the initial
 * PHP 8.2 migration.
 */
interface UserRepositoryInterface
{
    /**
     * Find a user by the Chisimba id field.
     *
     * @param mixed $id
     * @return array|null
     */
    public function findById($id);

    /**
     * Find a user by the legacy userid field.
     *
     * @param string $userId
     * @return array|null
     */
    public function findByUserId($userId);

    /**
     * Find a user by username.
     *
     * @param string $username
     * @return array|null
     */
    public function findByUsername($username);

    /**
     * Find a user by the numeric primary key.
     *
     * @param int $puid
     * @return array|null
     */
    public function findByPuid($puid);

    /**
     * Determine whether the account is active.
     *
     * @param array $user
     * @return bool
     */
    public function isActive(array $user);

    /**
     * Record a successful login.
     *
     * This must preserve the legacy meanings of logins and last_login.
     *
     * @param array $user
     * @return bool
     */
    public function recordSuccessfulLogin(array $user);
}
