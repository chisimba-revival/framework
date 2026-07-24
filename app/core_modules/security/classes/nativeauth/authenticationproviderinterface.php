<?php
/**
 * Credential-verification boundary for a Chisimba authentication provider.
 *
 * Providers verify primary credentials only. They must not:
 * - create or mutate Chisimba sessions;
 * - create or update local users;
 * - assign groups, roles, permissions, or administrator status;
 * - redirect or render user interfaces.
 */
interface AuthenticationProviderInterface
{
    /**
     * Return the stable provider identifier, for example "local" or "ldap".
     *
     * @return string
     */
    public function getProviderId();

    /**
     * Verify supplied credentials without creating a session.
     *
     * @param string $identifier
     * @param string $secret
     * @param array  $context Non-secret request context.
     *
     * @return CanonicalAuthenticationResult
     */
    public function authenticate(
        $identifier,
        $secret,
        array $context = array()
    );
}
