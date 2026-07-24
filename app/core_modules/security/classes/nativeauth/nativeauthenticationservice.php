<?php
require_once dirname(__FILE__) . '/nativeauthenticationserviceinterface.php';
require_once dirname(__FILE__) . '/authenticationproviderregistryinterface.php';
require_once dirname(__FILE__) . '/nativesessionserviceinterface.php';
require_once dirname(__FILE__) . '/canonicalauthenticationresult.php';

/**
 * Canonical authentication orchestrator.
 *
 * This service coordinates credential verification, optional MFA policy, and
 * authenticated-session establishment. Policy evaluation and presentation
 * remain outside this service.
 */
class NativeAuthenticationService
    implements NativeAuthenticationServiceInterface
{
    private $providers;
    private $sessions;
    private $mfaPolicy;
    private $mfaChallenges;

    public function __construct(
        AuthenticationProviderRegistryInterface $providers,
        NativeSessionServiceInterface $sessions,
        $mfaPolicy = null,
        $mfaChallenges = null
    ) {
        if ($mfaPolicy !== null
            && !method_exists($mfaPolicy, 'requiresChallenge')) {
            throw new InvalidArgumentException(
                'MFA policy must implement requiresChallenge().'
            );
        }

        if ($mfaChallenges !== null
            && (!method_exists($mfaChallenges, 'createChallenge')
                || !method_exists($mfaChallenges, 'verifyChallenge'))) {
            throw new InvalidArgumentException(
                'MFA challenge service has an invalid contract.'
            );
        }

        $this->providers = $providers;
        $this->sessions = $sessions;
        $this->mfaPolicy = $mfaPolicy;
        $this->mfaChallenges = $mfaChallenges;
    }

    public function authenticate(
        $providerId,
        $identifier,
        $secret,
        array $context = array()
    ) {
        $provider = $this->providers->getProvider($providerId);

        if (!$provider instanceof AuthenticationProviderInterface) {
            return CanonicalAuthenticationResult::failure(
                (string) $providerId,
                CanonicalAuthenticationResult::STATUS_ERROR,
                'provider_unavailable'
            );
        }

        $result = $provider->authenticate(
            $identifier,
            $secret,
            $this->sanitiseContext($context)
        );

        if (!$result instanceof CanonicalAuthenticationResult) {
            return CanonicalAuthenticationResult::failure(
                (string) $providerId,
                CanonicalAuthenticationResult::STATUS_ERROR,
                'invalid_provider_result'
            );
        }

        if (!$result->isSuccess()) {
            return $result;
        }

        if ($this->mfaPolicy !== null
            && $this->mfaPolicy->requiresChallenge($result, $context)) {
            $metadata = $result->getMetadata();

            if ($this->mfaChallenges !== null) {
                $metadata['mfa_challenge'] =
                    $this->mfaChallenges->createChallenge($result, $context);
            }

            return CanonicalAuthenticationResult::mfaRequired(
                $result->getProviderId(),
                $result->getUserId(),
                $result->getUsername(),
                $result->getAttributes(),
                $metadata
            );
        }

        return $result;
    }

    public function establishAuthenticatedSession(
        CanonicalAuthenticationResult $result,
        array $context = array()
    ) {
        if (!$result->isSuccess()) {
            return false;
        }

        return $this->sessions->establish(
            $result->getUserId(),
            array(
                'username' => $result->getUsername(),
                'provider' => $result->getProviderId(),
                'metadata' => $result->getMetadata(),
            )
        );
    }

    public function logout()
    {
        return $this->sessions->destroy();
    }

    public function getAuthenticatedUserId()
    {
        return $this->sessions->getUserId();
    }

    public function isAuthenticated()
    {
        return $this->sessions->isAuthenticated();
    }

    private function sanitiseContext(array $context)
    {
        foreach (array(
            'password',
            'passwd',
            'secret',
            'token',
            'access_token',
            'refresh_token',
            'authorization'
        ) as $secretKey) {
            unset($context[$secretKey]);
        }

        return $context;
    }
}
