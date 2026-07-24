<?php
/**
 * Creates and verifies multi-factor authentication challenges.
 */
interface MfaChallengeServiceInterface
{
    /**
     * Create a challenge for a verified identity.
     *
     * @return array Provider-neutral challenge descriptor.
     */
    public function createChallenge(
        CanonicalAuthenticationResult $identity,
        array $context = array()
    );

    /**
     * Verify a submitted challenge response.
     *
     * @return bool
     */
    public function verifyChallenge(
        CanonicalAuthenticationResult $identity,
        $challengeId,
        $response,
        array $context = array()
    );
}
