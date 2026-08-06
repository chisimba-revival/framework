<?php
$root = dirname(__DIR__, 2);
require_once $root . '/app/classes/core/installationmasterkeyprovider.php';
$dir = sys_get_temp_dir() . '/chisimba-p338-' . bin2hex(random_bytes(8));
if (!mkdir($dir, 0700)) { throw new RuntimeException('test setup failed'); }
$path = $dir . '/installation.key';
$assert = function ($ok, $label) { if (!$ok) { fwrite(STDERR, "FAIL: $label\n"); exit(1); } };
try {
    $provider = new InstallationMasterKeyProvider($path);
    $assert($provider->ensureExists() === true, 'first call creates');
    $first = file_get_contents($path);
    $assert(base64_decode(trim($first), true) !== false, 'valid Base64');
    $assert(strlen(base64_decode(trim($first), true)) === 32, '32 bytes');
    $assert((fileperms($path) & 0777) === 0600, 'owner-only permissions');
    $assert($provider->ensureExists() === false, 'second call reuses');
    $assert(hash_equals($first, file_get_contents($path)), 'key stable');
    $mfa = $provider->deriveKey('mfa-encryption-v1');
    $abuse = $provider->deriveKey('abuse-protection-v1');
    $assert(strlen($mfa) === 32 && strlen($abuse) === 32, 'derived lengths');
    $assert(!hash_equals($mfa, $abuse), 'purpose separation');
    file_put_contents($path, 'invalid');
    $failed = false;
    try { $provider->ensureExists(); } catch (RuntimeException $e) { $failed = true; }
    $assert($failed, 'malformed key refused');
    $assert(file_get_contents($path) === 'invalid', 'malformed key not replaced');
    echo "P338_KEY_CONTRACT=PASS\n";
} finally {
    @unlink($path);
    @rmdir($dir);
}
