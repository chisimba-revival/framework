<?php
/**
 * Maps an externally verified identity to a local Chisimba user.
 *
 * This boundary deliberately separates provisioning from credential
 * verification. Local-password authentication normally returns an existing
 * local user and does not require provisioning.
 */
interface IdentityProvisioningServiceInterface
{
    /**
     * Resolve or provision a local user for a verified provider identity.
     *
     * @param CanonicalAuthenticationResult $verifiedIdentity
     * @param array                         $context
     *
     * @return CanonicalAuthenticationResult
     */
    public function resolveLocalIdentity(
        CanonicalAuthenticationResult $verifiedIdentity,
        array $context = array()
    );
}
