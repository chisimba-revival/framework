<?php

/**
 * Authentication service contract for Chisimba.
 *
 * This interface describes the small authentication surface required by
 * the framework. It intentionally contains no LiveUser implementation
 * details.
 *
 * The initial PHP 8.2 implementation must preserve the externally visible
 * behaviour of the frozen PHP 7.4 system.
 */
interface AuthenticationServiceInterface
{
    /**
     * Initialise authentication and restore any existing session.
     *
     * @return bool TRUE when initialisation succeeds
     */
    public function init();

    /**
     * Authenticate a user using a local identifier and password.
     *
     * The identifier may initially map to the legacy username or userid
     * fields. The precise lookup rule must be captured from PHP 7.4 tests.
     *
     * @param string $identifier
     * @param string $password
     * @return bool
     */
    public function login($identifier, $password);

    /**
     * End the authenticated session.
     *
     * @return bool
     */
    public function logout();

    /**
     * Determine whether the current request has an authenticated user.
     *
     * @return bool
     */
    public function isLoggedIn();

    /**
     * Return the current authenticated Chisimba user identifier.
     *
     * The final identifier type must be chosen after mapping the legacy
     * id, userid, puid and auth_user_id relationships.
     *
     * @return mixed|null
     */
    public function getCurrentUserId();

    /**
     * Return errors from the most recent authentication operation.
     *
     * @return array
     */
    public function getErrors();
}
