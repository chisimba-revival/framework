<?php
require_once dirname(__FILE__)
    . '/../../app/core_modules/security/classes/nativeauth/localpasswordprovider.php';

class FakeUserRepository implements NativeUserRepositoryInterface
{
    public $failed = array();

    private $users = array(
        'active' => array(
            'user_id' => 'user-1',
            'username' => 'active',
            'password_hash' => 'valid-hash',
            'is_active' => true,
        ),
        'inactive' => array(
            'user_id' => 'user-2',
            'username' => 'inactive',
            'password_hash' => 'valid-hash',
            'is_active' => false,
        ),
        'legacy' => array(
            'user_id' => 'user-3',
            'username' => 'legacy',
            'password_hash' => 'legacy-hash',
            'is_active' => true,
        ),
    );

    public function findByUsername($username)
    {
        return isset($this->users[$username])
            ? $this->users[$username]
            : null;
    }

    public function findById($userId)
    {
        foreach ($this->users as $user) {
            if ($user['user_id'] === $userId) {
                return $user;
            }
        }

        return null;
    }

    public function isUserActive($userId)
    {
        $user = $this->findById($userId);

        return is_array($user) && !empty($user['is_active']);
    }

    public function updatePasswordHash($userId, $passwordHash)
    {
        return true;
    }

    public function recordSuccessfulLogin(
        $userId,
        array $context = array()
    ) {
        return true;
    }

    public function recordFailedLogin(
        $username,
        array $context = array()
    ) {
        $this->failed[] = array($username, $context);

        return true;
    }
}

class FakePasswordVerifier implements NativePasswordVerifierInterface
{
    public function verify(
        $plainTextPassword,
        $storedHash,
        array $userRecord = array()
    ) {
        return $plainTextPassword === 'correct'
            && in_array($storedHash, array('valid-hash', 'legacy-hash'), true);
    }

    public function needsRehash($storedHash)
    {
        return $storedHash === 'legacy-hash';
    }

    public function createHash($plainTextPassword)
    {
        return 'new-hash';
    }

    public function identifyHashScheme($storedHash)
    {
        return $storedHash === 'legacy-hash'
            ? 'sha1'
            : 'password_hash';
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

$users = new FakeUserRepository();
$provider = new LocalPasswordProvider(
    $users,
    new FakePasswordVerifier()
);

assertTrue(
    $provider->getProviderId() === 'local',
    'provider exposes stable local identifier'
);

$result = $provider->authenticate('active', 'correct');
assertTrue($result->isSuccess(), 'valid active account authenticates');
assertTrue($result->getUserId() === 'user-1', 'canonical user ID is returned');
assertTrue(
    $result->getMetadata()['password_rehash_required'] === false,
    'modern password hash does not request migration'
);

$result = $provider->authenticate('legacy', 'correct');
assertTrue($result->isSuccess(), 'valid legacy hash authenticates');
assertTrue(
    $result->getMetadata()['password_rehash_required'] === true,
    'legacy hash requests migration'
);

$result = $provider->authenticate('active', 'wrong');
assertTrue(
    $result->getStatus()
        === CanonicalAuthenticationResult::STATUS_INVALID_CREDENTIALS,
    'wrong password returns generic invalid-credentials status'
);

$result = $provider->authenticate('missing', 'correct');
assertTrue(
    $result->getStatus()
        === CanonicalAuthenticationResult::STATUS_INVALID_CREDENTIALS,
    'unknown account returns same generic status as wrong password'
);

$result = $provider->authenticate('inactive', 'correct');
assertTrue(
    $result->getStatus()
        === CanonicalAuthenticationResult::STATUS_INACTIVE,
    'inactive account is rejected'
);

assertTrue(
    count($users->failed) === 2,
    'failed-login recording occurs for wrong password and unknown account'
);

echo "ALL LOCAL PASSWORD PROVIDER TESTS PASSED\n";
