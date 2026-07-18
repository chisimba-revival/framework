<?php
/**
 * Password verification contract supporting gradual migration from legacy
 * hashes to password_hash()-compatible hashes.
 */
interface NativePasswordVerifierInterface
{
    /**
     * @param string $plainTextPassword
     * @param string $storedHash
     * @param array  $userRecord
     *
     * @return bool
     */
    public function verify($plainTextPassword, $storedHash, array $userRecord = array());

    /** @return bool */
    public function needsRehash($storedHash);

    /** @return string */
    public function createHash($plainTextPassword);

    /**
     * Return a stable identifier such as password_hash, md5, sha1, or unknown.
     *
     * @return string
     */
    public function identifyHashScheme($storedHash);
}
