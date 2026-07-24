<?php
require_once dirname(__FILE__) . '/authenticationproviderregistryinterface.php';
require_once dirname(__FILE__) . '/authenticationproviderinterface.php';

/**
 * In-memory registry of configured authentication providers.
 */
class AuthenticationProviderRegistry
    implements AuthenticationProviderRegistryInterface
{
    private $providers = array();
    private $enabledProviderIds = array();

    public function __construct(array $providers = array())
    {
        foreach ($providers as $provider) {
            $this->register($provider);
        }
    }

    public function register(AuthenticationProviderInterface $provider)
    {
        $providerId = trim((string) $provider->getProviderId());

        if ($providerId === '') {
            throw new InvalidArgumentException(
                'Authentication provider ID must not be empty.'
            );
        }

        if (isset($this->providers[$providerId])) {
            throw new InvalidArgumentException(
                'Authentication provider already registered: ' . $providerId
            );
        }

        $this->providers[$providerId] = $provider;
        $this->enabledProviderIds[] = $providerId;
    }

    public function getProvider($providerId)
    {
        $providerId = trim((string) $providerId);

        return isset($this->providers[$providerId])
            ? $this->providers[$providerId]
            : null;
    }

    public function getEnabledProviderIds()
    {
        return $this->enabledProviderIds;
    }
}
