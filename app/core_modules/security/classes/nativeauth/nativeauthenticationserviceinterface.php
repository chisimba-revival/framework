<?php
/**
 * Canonical Chisimba authentication orchestration contract.
 */
interface NativeAuthenticationServiceInterface
{
    /**
     * Verify primary credentials without establishing a session.
     *
     * @return CanonicalAuthenticationResult
     */
    public function authenticate(
        $providerId,
        $identifier,
        $secret,
        array $context = array()
    );

    /**
     * Establish the authenticated session after primary authentication and,
     * where required, successful MFA verification.
     *
     * @return bool
     */
    public function establishAuthenticatedSession(
        CanonicalAuthenticationResult $result,
        array $context = array()
    );

    /** @return bool */
    public function logout();

    /** @return string|null */
    public function getAuthenticatedUserId();

    /** @return bool */
    public function isAuthenticated();
}
