<?php
require_once dirname(__FILE__) . '/nativepasswordverifierinterface.php';

/**
 * Password verifier for modern password_hash() credentials only.
 *
 * Legacy MD5, SHA-1, crypt, and plaintext credentials are never accepted.
 *
 * @author Derek Keats
 */
class NativePasswordVerifier implements NativePasswordVerifierInterface
{
    private $algorithm;
    private $options;

    public function __construct($algorithm = PASSWORD_DEFAULT, array $options = array())
    {
        $this->algorithm = $algorithm;
        $this->options = $options;
    }

    public function verify($plainTextPassword, $storedHash, array $userRecord = array())
    {
        $plainTextPassword = (string) $plainTextPassword;
        $storedHash = trim((string) $storedHash);
        $scheme = $this->identifyHashScheme($storedHash);

        return $scheme === 'password_hash'
            && password_verify($plainTextPassword, $storedHash);
    }

    public function needsRehash($storedHash)
    {
        $storedHash = trim((string) $storedHash);

        if ($this->identifyHashScheme($storedHash) !== 'password_hash') {
            return true;
        }

        return password_needs_rehash(
            $storedHash,
            $this->algorithm,
            $this->options
        );
    }

    public function createHash($plainTextPassword)
    {
        $hash = password_hash(
            (string) $plainTextPassword,
            $this->algorithm,
            $this->options
        );

        if (!is_string($hash) || $hash === '') {
            throw new RuntimeException('password_hash() failed.');
        }

        return $hash;
    }

    public function identifyHashScheme($storedHash)
    {
        $storedHash = trim((string) $storedHash);

        if ($storedHash === '') {
            return 'unknown';
        }

        $info = password_get_info($storedHash);
        if (isset($info['algo']) && $info['algo'] !== 0) {
            return 'password_hash';
        }

        return 'unknown';
    }
}
