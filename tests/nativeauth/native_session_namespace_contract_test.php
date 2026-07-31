<?php
require_once dirname(__DIR__, 2)
    . '/app/core_modules/security/classes/nativeauth/nativesessionservice.php';

function p110Assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

class P110ModuleScopedBackend
{
    public $values = array();
    public $calls = array();

    public function getSession($name, $default = null, $module = '_MODULE_')
    {
        $this->calls[] = array('get', $module, $name);
        $key = $module . '~' . $name;
        return array_key_exists($key, $this->values)
            ? $this->values[$key]
            : $default;
    }

    public function setSession($name, $value, $module = '_MODULE_')
    {
        $this->calls[] = array('set', $module, $name);
        $this->values[$module . '~' . $name] = $value;
    }

    public function unsetSession($name, $module = '_MODULE_')
    {
        $this->calls[] = array('unset', $module, $name);
        unset($this->values[$module . '~' . $name]);
    }
}

$backend = new P110ModuleScopedBackend();
$writer = new NativeSessionService(
    $backend,
    function () { return true; },
    function () { return 1785488400; }
);

p110Assert(
    $writer->establish('canonical-user', array('username' => 'admin')),
    'canonical login establishes the authentication session'
);
p110Assert(
    isset($backend->values['security~isLoggedIn'])
        && isset($backend->values['security~userid']),
    'authentication state is owned by the security session namespace'
);
p110Assert(
    !isset($backend->values['_MODULE_~isLoggedIn'])
        && !isset($backend->values['_MODULE_~userid']),
    'authentication state never depends on the constructing module namespace'
);

$reader = new NativeSessionService($backend, function () { return true; });
p110Assert(
    $reader->isAuthenticated()
        && $reader->getUserId() === 'canonical-user',
    'a consumer constructed outside security reads the same canonical identity'
);

foreach ($backend->calls as $call) {
    p110Assert(
        $call[1] === NativeSessionService::AUTH_SESSION_MODULE,
        'every canonical authentication-session operation names security explicitly'
    );
}

p110Assert(
    $reader->destroy(),
    'canonical logout clears the fixed authentication namespace'
);
p110Assert(
    !isset($backend->values['security~isLoggedIn'])
        && !isset($backend->values['security~userid']),
    'logout removes canonical identity from the security namespace'
);

echo "ALL CANONICAL AUTH SESSION NAMESPACE TESTS PASSED\n";
