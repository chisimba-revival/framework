<?php
/**
 * Determines whether a verified identity requires an additional factor.
 */
interface MfaPolicyInterface
{
    /**
     * @param CanonicalAuthenticationResult $identity
     * @param array                         $context
     * @return bool
     */
    public function requiresChallenge(
        CanonicalAuthenticationResult $identity,
        array $context = array()
    );
}
