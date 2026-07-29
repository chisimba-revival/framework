<?php
require_once dirname(__FILE__) . '/../classes/nativeauth/totpservice.php';
require_once dirname(__FILE__) . '/../classes/nativeauth/mfasecretprotector.php';
require_once dirname(__FILE__) . '/../classes/nativeauth/recoverycodeservice.php';

function ensure($condition, $label)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $label . PHP_EOL);
        exit(1);
    }
}

$totp = new TotpService(30, 8, 0);
$secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
$step = 1;
$code = $totp->atStep($secret, $step);
ensure($code === '94287082', 'RFC 6238 SHA-1 test vector');
ensure($totp->verify($secret, $code, 59, null) === 1, 'valid TOTP');
ensure($totp->verify($secret, $code, 59, 1) === false, 'TOTP replay blocked');

$protector = new MfaSecretProtector(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
$protected = $protector->protect($secret);
ensure($protected['ciphertext'] !== $secret, 'secret encrypted');
ensure(
    $protector->reveal($protected['ciphertext'], $protected['nonce']) === $secret,
    'secret decrypts'
);

$recovery = new RecoveryCodeService();
$codes = $recovery->generate(3);
ensure(count($codes['plain']) === 3, 'recovery code count');
ensure(
    $recovery->matches($codes['plain'][0], $codes['hashes'][0]),
    'recovery code verifies'
);
ensure(
    !$recovery->matches($codes['plain'][1], $codes['hashes'][0]),
    'wrong recovery code rejected'
);
ensure($codes['plain'][0] !== $codes['hashes'][0], 'recovery code hashed');

printf("PASS: MFA cryptographic foundation\n");
