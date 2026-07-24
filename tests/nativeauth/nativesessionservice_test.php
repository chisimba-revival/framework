<?php
require_once dirname(__FILE__)
    . '/../../app/core_modules/security/classes/nativeauth/nativesessionservice.php';

class FakeSessionBackend
{
    public $values = array();
    public $writes = array();
    public $removals = array();

    public function getSession($name, $default = null)
    {
        return array_key_exists($name, $this->values)
            ? $this->values[$name]
            : $default;
    }

    public function setSession($name, $value)
    {
        $this->values[$name] = $value;
        $this->writes[] = $name;
    }

    public function unsetSession($name)
    {
        unset($this->values[$name]);
        $this->removals[] = $name;
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

$backend = new FakeSessionBackend();
$backend->values['skin'] = 'chisimba-reborn';

$regenerations = 0;
$regenerator = function () use (&$regenerations) {
    $regenerations++;
    return true;
};

$service = new NativeSessionService(
    $backend,
    $regenerator,
    function () {
        return 1784880000;
    }
);

assertTrue(!$service->isAuthenticated(), 'new session is anonymous');
assertTrue($service->getUserId() === null, 'anonymous session has no user ID');

$result = $service->establish(
    'user-1',
    array(
        'username' => 'derek',
        'provider' => 'local',
        'metadata' => array('password_rehash_required' => false),
    )
);

assertTrue($result, 'authenticated session is established');
assertTrue($regenerations === 1, 'identifier rotates before establishment');
assertTrue($service->isAuthenticated(), 'session reports authenticated');
assertTrue($service->getUserId() === 'user-1', 'canonical user ID is restored');
assertTrue(
    $service->get('username') === 'derek',
    'username is stored as identity data'
);
assertTrue(
    $service->get('nativeAuthProvider') === 'local',
    'provider identifier is stored'
);
assertTrue(
    $service->get('nativeAuthenticatedAt') === 1784880000,
    'authentication timestamp uses injected clock'
);
assertTrue(
    !array_key_exists('isAdmin', $backend->values),
    'session service does not store administrator state'
);
assertTrue(
    !array_key_exists('groups', $backend->values)
        && !array_key_exists('permissions', $backend->values),
    'session service does not store authorization data'
);

assertTrue(
    $service->establish('', array()) === false,
    'empty user ID fails closed'
);
assertTrue(
    $regenerations === 1,
    'invalid identity does not rotate or mutate session'
);

assertTrue($service->destroy(), 'logout clears authenticated identity');
assertTrue($regenerations === 2, 'identifier rotates again on logout');
assertTrue(!$service->isAuthenticated(), 'logout leaves anonymous session');
assertTrue($service->getUserId() === null, 'logout removes user ID');
assertTrue(
    $service->get('skin') === 'chisimba-reborn',
    'logout preserves unrelated session preferences'
);

$failingBackend = new FakeSessionBackend();
$failingService = new NativeSessionService(
    $failingBackend,
    function () {
        return false;
    }
);
assertTrue(
    !$failingService->establish('user-2'),
    'failed identifier rotation prevents authenticated state'
);
assertTrue(
    empty($failingBackend->values),
    'failed rotation writes no identity data'
);

echo "ALL AUTHENTICATION SESSION SERVICE TESTS PASSED\n";
