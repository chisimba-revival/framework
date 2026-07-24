<?php
/**
 * Registry for configured authentication providers.
 */
interface AuthenticationProviderRegistryInterface
{
    /**
     * Return a configured provider by stable identifier.
     *
     * @param string $providerId
     * @return AuthenticationProviderInterface|null
     */
    public function getProvider($providerId);

    /**
     * Return enabled provider identifiers in configured evaluation order.
     *
     * @return array
     */
    public function getEnabledProviderIds();
}
