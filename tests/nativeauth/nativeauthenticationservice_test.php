<?php
require_once dirname(__FILE__)
    . '/../../app/core_modules/security/classes/nativeauth/authenticationproviderregistry.php';
require_once dirname(__FILE__)
    . '/../../app/core_modules/security/classes/nativeauth/nativeauthenticationservice.php';

class FakeProvider implements AuthenticationProviderInterface
{
    public $context = array();
    private $result;

    public function __construct(CanonicalAuthenticationResult $result)
    {
        $this->result = $result;
    }

    public function getProviderId()
    {
        return 'local';
    }

    public function authenticate(
        $identifier,
        $secret,
        array $context = array()
    ) {
        $this->context = $context;
        return $this->result;
    }
}

class FakeSessions implements NativeSessionServiceInterface
{
    public $established = array();
    public $destroyed = false;

    public function establish($userId, array $attributes = array())
    {
        $this->established = array($userId, $attributes);
        return true;
    }

    public function destroy()
    {
        $this->destroyed = true;
        return true;
    }

    public function regenerateIdentifier()
    {
        return true;
    }

    public function getUserId()
    {
        return empty($this->established) ? null : $this->established[0];
    }

    public function isAuthenticated()
    {
        return !empty($this->established) && !$this->destroyed;
    }

    public function get($name, $default = null)
    {
        return $default;
    }

    public function set($name, $value)
    {
    }

    public function remove($name)
    {
    }
}

class AlwaysMfaPolicy
{
    public function requiresChallenge(
        CanonicalAuthenticationResult $identity,
        array $context = array()
    ) {
        return true;
    }
}

class FakeMfaChallenges
{
    public function createChallenge(
        CanonicalAuthenticationResult $identity,
        array $context = array()
    ) {
        return array('challenge_id' => 'challenge-1');
    }

    public function verifyChallenge(
        CanonicalAuthenticationResult $identity,
        $challengeId,
        $response,
        array $context = array()
    ) {
        return true;
    }
}

function assertTrue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

$success = CanonicalAuthenticationResult::success(
    'local',
    'user-1',
    'derek',
    array(),
    array('password_rehash_required' => false)
);

$provider = new FakeProvider($success);
$registry = new AuthenticationProviderRegistry(array($provider));
$sessions = new FakeSessions();
$service = new NativeAuthenticationService($registry, $sessions);

assertTrue(
    $registry->getEnabledProviderIds() === array('local'),
    'registry preserves enabled provider order'
);
assertTrue(
    $registry->getProvider('local') === $provider,
    'registry resolves provider by stable ID'
);

$result = $service->authenticate(
    'local',
    'derek',
    'correct',
    array(
        'ip' => '127.0.0.1',
        'password' => 'must-not-propagate',
        'token' => 'must-not-propagate',
    )
);

assertTrue($result->isSuccess(), 'service returns successful provider result');
assertTrue(
    isset($provider->context['ip']),
    'non-secret request context reaches provider'
);
assertTrue(
    !isset($provider->context['password'])
        && !isset($provider->context['token']),
    'secret context fields are removed before provider call'
);
assertTrue(
    $service->establishAuthenticatedSession($result),
    'successful identity establishes session'
);
assertTrue(
    $sessions->established[0] === 'user-1',
    'session receives canonical user ID'
);
assertTrue(
    $sessions->established[1]['provider'] === 'local',
    'session receives provider identity'
);
assertTrue($service->isAuthenticated(), 'service exposes session state');
assertTrue(
    $service->getAuthenticatedUserId() === 'user-1',
    'service restores authenticated user ID'
);
assertTrue($service->logout(), 'service delegates logout');
assertTrue($sessions->destroyed, 'session service performs logout');

$missing = $service->authenticate('missing', 'derek', 'correct');
assertTrue(
    $missing->getStatus() === CanonicalAuthenticationResult::STATUS_ERROR,
    'unavailable provider fails closed'
);

$failureProvider = new FakeProvider(
    CanonicalAuthenticationResult::failure(
        'local',
        CanonicalAuthenticationResult::STATUS_INVALID_CREDENTIALS
    )
);
$failureService = new NativeAuthenticationService(
    new AuthenticationProviderRegistry(array($failureProvider)),
    new FakeSessions()
);
$failure = $failureService->authenticate('local', 'derek', 'wrong');
assertTrue(
    !$failure->isSuccess(),
    'provider failure is returned without session establishment'
);
assertTrue(
    !$failureService->establishAuthenticatedSession($failure),
    'failed identity cannot establish session'
);

$mfaService = new NativeAuthenticationService(
    $registry,
    new FakeSessions(),
    new AlwaysMfaPolicy(),
    new FakeMfaChallenges()
);
$mfaResult = $mfaService->authenticate('local', 'derek', 'correct');
assertTrue($mfaResult->requiresMfa(), 'MFA policy blocks direct session creation');
assertTrue(
    isset($mfaResult->getMetadata()['mfa_challenge']['challenge_id']),
    'MFA challenge descriptor is returned'
);
assertTrue(
    !$mfaService->establishAuthenticatedSession($mfaResult),
    'MFA-required result cannot establish session'
);

echo "ALL AUTHENTICATION SERVICE TESTS PASSED\n";
