<?php
require_once dirname(__FILE__) . '/nativepasswordverifierinterface.php';

/**
 * Password verifier for comparison and migration planning.
 *
 * Supported verification:
 * - password_hash() formats recognised by password_get_info()
 * - 32-character hexadecimal MD5
 * - 40-character hexadecimal SHA-1
 * - portable crypt() hashes beginning with $1$, $2, $5$, or $6$
 *
 * Plaintext and unknown formats are never accepted.
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

        if ($scheme === 'password_hash') {
            return password_verify($plainTextPassword, $storedHash);
        }

        if ($scheme === 'md5') {
            return hash_equals(
                strtolower($storedHash),
                md5($plainTextPassword)
            );
        }

        if ($scheme === 'sha1') {
            return hash_equals(
                strtolower($storedHash),
                sha1($plainTextPassword)
            );
        }

        if ($scheme === 'crypt') {
            $candidate = crypt($plainTextPassword, $storedHash);
            return is_string($candidate)
                && strlen($candidate) === strlen($storedHash)
                && hash_equals($storedHash, $candidate);
        }

        return false;
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

        if (preg_match('/\A[0-9a-f]{32}\z/i', $storedHash) === 1) {
            return 'md5';
        }

        if (preg_match('/\A[0-9a-f]{40}\z/i', $storedHash) === 1) {
            return 'sha1';
        }

        if (preg_match('/\A\$(?:1|2[abxy]?|5|6)\$/', $storedHash) === 1) {
            return 'crypt';
        }

        return 'unknown';
    }
}
