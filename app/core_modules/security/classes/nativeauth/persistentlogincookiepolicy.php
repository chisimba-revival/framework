<?php
/**
 * Security policy for the persistent-login browser cookie.
 *
 * @author Derek Keats
 */
class PersistentLoginCookiePolicy
{
    public function options($expiresAt)
    {
        return array(
            'expires' => (int) $expiresAt,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        );
    }
}
