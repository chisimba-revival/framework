<?php
require_once dirname(__FILE__) . '/../../app/core_modules/security/classes/nativeauth/totpservice.php';
require_once dirname(__FILE__) . '/../../app/core_modules/security/classes/nativeauth/csrftokenservice.php';
require_once dirname(__FILE__) . '/../../app/core_modules/security/classes/nativeauth/pendingauthenticationservice.php';

class V38SessionBackend
{
    public $values = array();
    public function getSession($name, $default = null) {
        return array_key_exists($name, $this->values)
            ? $this->values[$name] : $default;
    }
    public function setSession($name, $value) { $this->values[$name] = $value; }
    public function unsetSession($name) { unset($this->values[$name]); }
}

function v38_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
        exit(1);
    }
}

$totp = new TotpService();
$secret = $totp->generateSecret();
v38_assert((bool) preg_match('/^[A-Z2-7]{32}$/', $secret),
    'Generated TOTP secret is not canonical Base32.');
v38_assert($secret !== $totp->generateSecret(),
    'TOTP secret generation repeated a value.');

$now = 1700000000;
$backend = new V38SessionBackend();
$csrf = new CsrfTokenService($backend, 300, function () use (&$now) {
    return $now;
});
$token = $csrf->issue('mfa.challenge');
v38_assert($csrf->consume('mfa.challenge', $token),
    'Valid CSRF token was rejected.');
v38_assert(!$csrf->consume('mfa.challenge', $token),
    'CSRF token replay was accepted.');
$token = $csrf->issue('mfa.enrol');
$now += 301;
v38_assert(!$csrf->consume('mfa.enrol', $token),
    'Expired CSRF token was accepted.');

$now = 1700000000;
$pending = new PendingAuthenticationService(
    $backend,
    300,
    function () use (&$now) { return $now; }
);
$record = $pending->begin('user-1', 'alice', true, array(
    'ip' => '127.0.0.1',
    'password' => 'must-not-survive',
));
v38_assert(!isset($record->metadata['password']),
    'Pending authentication retained forbidden metadata.');
v38_assert($pending->consume('wrong') === null,
    'Wrong transaction identifier was accepted.');
v38_assert($pending->peek() === null,
    'Failed consumption did not clear the transaction.');
$record = $pending->begin('user-1', 'alice', false);
$now += 301;
v38_assert($pending->peek() === null,
    'Expired pending authentication remained usable.');

echo "PASS: V38 security primitive tests." . PHP_EOL;
