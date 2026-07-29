<?php
/**
 * Regression contract for conditional MFA-key loading.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
$root = dirname(__DIR__, 2);
require_once $root . '/app/core_modules/security/classes/nativeauth/'
    . 'mfasecretprotector.php';
require_once $root . '/app/core_modules/security/classes/nativeauth/'
    . 'deferredmfasecretprotector.php';

function assertLazyMfa($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

class CountingMfaKeyProvider
{
    public $calls = 0;

    public function getKey()
    {
        $this->calls++;
        return random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}

$provider = new CountingMfaKeyProvider();
$protector = new DeferredMfaSecretProtector($provider);
assertLazyMfa($provider->calls === 0,
    'composition does not load the MFA key');

$protected = $protector->protect('test-secret');
assertLazyMfa($provider->calls === 1,
    'first MFA operation loads the key exactly once');
assertLazyMfa(
    $protector->reveal($protected['ciphertext'], $protected['nonce'])
        === 'test-secret',
    'deferred protector preserves authenticated encryption'
);
assertLazyMfa($provider->calls === 1,
    'subsequent MFA operations reuse the loaded key');

$factory = file_get_contents(
    $root . '/app/core_modules/security/classes/nativeauth/'
    . 'nativeauthwebcompositionfactory.php'
);
assertLazyMfa(
    strpos($factory, '(new InstallationMfaKeyProvider())->getKey()') === false,
    'composition factory contains no eager MFA-key read'
);
assertLazyMfa(
    strpos($factory, 'new DeferredMfaSecretProtector(') !== false,
    'composition factory uses the deferred MFA protector'
);

echo "PASS: MFA key is loaded only when MFA cryptography is used\n";
