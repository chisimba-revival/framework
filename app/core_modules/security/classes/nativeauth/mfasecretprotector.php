<?php
require_once __DIR__ . '/mfasecretprotectorinterface.php';

/**
 * Authenticated encryption for MFA secrets.
 *
 * The installation key is injected and must not be stored in the MFA tables.
 *
 * @author Derek Keats
 */
class MfaSecretProtector implements MfaSecretProtectorInterface
{
    private $key;

    public function __construct($key)
    {
        if (!function_exists('sodium_crypto_secretbox')) {
            throw new RuntimeException('The Sodium extension is required.');
        }
        if (strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new InvalidArgumentException('Invalid MFA encryption key length.');
        }
        $this->key = $key;
    }

    public function protect($secret)
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return array(
            'ciphertext' => base64_encode(
                sodium_crypto_secretbox((string) $secret, $nonce, $this->key)
            ),
            'nonce' => base64_encode($nonce),
        );
    }

    public function reveal($ciphertext, $nonce)
    {
        $plain = sodium_crypto_secretbox_open(
            base64_decode($ciphertext, true),
            base64_decode($nonce, true),
            $this->key
        );
        if ($plain === false) {
            throw new RuntimeException('MFA secret authentication failed.');
        }
        return $plain;
    }
}
