<?php
/**
 * Contract for the future native Chisimba authentication service.
 *
 * This interface is deliberately independent of LiveUser. Implementations must
 * preserve the observable login behaviour documented for Milestone 8 before
 * they can be activated.
 */
interface NativeAuthenticationServiceInterface
{
    /**
     * Authenticate a user without modifying the current session.
     *
     * @param string $username
     * @param string $password
     * @param array  $context  Request metadata such as IP and user agent.
     *
     * @return NativeAuthenticationResult
     */
    public function authenticate($username, $password, array $context = array());

    /**
     * Complete login after successful authentication.
     *
     * @param NativeAuthenticationResult $result
     * @param array                      $context
     *
     * @return bool
     */
    public function establishAuthenticatedSession(
        NativeAuthenticationResult $result,
        array $context = array()
    );

    /**
     * End the current authenticated session.
     *
     * @return bool
     */
    public function logout();

    /**
     * Return the authenticated user identifier, or null for an anonymous user.
     *
     * @return string|null
     */
    public function getAuthenticatedUserId();

    /**
     * Determine whether the current request has an authenticated user.
     *
     * @return bool
     */
    public function isAuthenticated();
}
