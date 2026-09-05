<?php
/**
 * Verify that an idle page can still submit its session-bound logout token.
 */

require_once dirname(__DIR__)
    . '/classes/nativeauth/csrftokenservice.php';

class LogoutCsrfSessionBackend
{
    private $values = array();

    public function getSession($key, $default = null)
    {
        return array_key_exists($key, $this->values)
            ? $this->values[$key]
            : $default;
    }

    public function setSession($key, $value)
    {
        $this->values[$key] = $value;
    }

    public function unsetSession($key)
    {
        unset($this->values[$key]);
    }
}

$now = 1000;
$backend = new LogoutCsrfSessionBackend();
$csrf = new CsrfTokenService($backend, 900, function () use (&$now) {
    return $now;
});

$normalToken = $csrf->issue('ordinary_form');
$logoutToken = $csrf->issueForSession('native_auth_logout');
$now += 901;

if ($csrf->consume('ordinary_form', $normalToken)) {
    fwrite(STDERR, "FAIL: ordinary CSRF token survived its lifetime\n");
    exit(1);
}
if (!$csrf->consume('native_auth_logout', $logoutToken)) {
    fwrite(STDERR, "FAIL: logout token expired while its session remained active\n");
    exit(1);
}
if ($csrf->consume('native_auth_logout', $logoutToken)) {
    fwrite(STDERR, "FAIL: logout token was not single-use\n");
    exit(1);
}

echo "PASS: logout token remains valid for an idle session and is single-use.\n";
