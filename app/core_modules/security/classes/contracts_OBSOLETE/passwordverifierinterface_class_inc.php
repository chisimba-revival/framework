<?php

/**
 * Password verification contract.
 *
 * No implementation is provided until the actual tbl_users.pass formats
 * have been safely inventoried.
 */
interface PasswordVerifierInterface
{
    /**
     * Verify a supplied password against a stored legacy value.
     *
     * @param string $password
     * @param string $storedValue
     * @param array  $user
     * @return bool
     */
    public function verify(
        $password,
        $storedValue,
        array $user = array()
    );

    /**
     * Determine whether the stored value should be upgraded.
     *
     * @param string $storedValue
     * @return bool
     */
    public function needsRehash($storedValue);

    /**
     * Generate a modern password hash.
     *
     * @param string $password
     * @return string
     */
    public function hash($password);
}
