<?php
/**
 * Password verification contract for password_hash()-compatible credentials.
 *
 * Legacy password formats are outside this contract and are never accepted.
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
     * Return password_hash for a supported credential, otherwise unknown.
     *
     * @return string
     */
    public function identifyHashScheme($storedHash);
}
