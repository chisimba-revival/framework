<?php

class FakeLiveUserForLoginState
{
    private $loggedIn;

    public function __construct($loggedIn)
    {
        $this->loggedIn = $loggedIn;
    }

    public function isLoggedIn()
    {
        return $this->loggedIn;
    }
}

class LoginStateCompatibilityHarness
{
    public $objLu;
    private $session = array();

    public function __construct($legacyState, array $session = array())
    {
        $this->objLu = new FakeLiveUserForLoginState($legacyState);
        $this->session = $session;
    }

    public function getSession($name)
    {
        return array_key_exists($name, $this->session)
            ? $this->session[$name]
            : null;
    }

    public function isLoggedIn()
    {
        // NATIVE_LOGIN_STATE_COMPATIBILITY
        $nativeFlag = getenv('CHISIMBA_NATIVE_AUTH_LOGIN');
        $nativeEnabled = in_array(
            strtolower(trim((string) $nativeFlag)),
            array('1', 'true', 'yes', 'on'),
            true
        );

        if ($nativeEnabled && $this->getSession('isLoggedIn') === TRUE) {
            return TRUE;
        }

        return $this->objLu->isLoggedIn();
    }
}

function expectTrue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

putenv('CHISIMBA_NATIVE_AUTH_LOGIN=1');
$native = new LoginStateCompatibilityHarness(false, array('isLoggedIn' => true));
expectTrue(
    $native->isLoggedIn() === true,
    'native compatibility session is accepted while feature flag is enabled'
);

$notAuthenticated = new LoginStateCompatibilityHarness(false, array());
expectTrue(
    $notAuthenticated->isLoggedIn() === false,
    'missing native session does not authenticate a request'
);

putenv('CHISIMBA_NATIVE_AUTH_LOGIN=0');
$disabled = new LoginStateCompatibilityHarness(false, array('isLoggedIn' => true));
expectTrue(
    $disabled->isLoggedIn() === false,
    'native compatibility state is ignored when feature flag is disabled'
);

$legacy = new LoginStateCompatibilityHarness(true, array());
expectTrue(
    $legacy->isLoggedIn() === true,
    'legacy LiveUser login remains available as rollback'
);

echo "ALL NATIVE LOGIN-STATE COMPATIBILITY TESTS PASSED\n";
