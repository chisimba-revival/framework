<?php
/**
 * Provider-neutral MFA enforcement policy contract.
 *
 * Primary authentication providers never establish the final Chisimba
 * session. Core evaluates this policy after identity proof and before session
 * establishment.
 *
 * @author Derek Keats
 */
interface MfaEnforcementPolicyInterface
{
    public function requiresChallenge($result, array $context = array());

    public function evaluate($result, array $context = array());
}
