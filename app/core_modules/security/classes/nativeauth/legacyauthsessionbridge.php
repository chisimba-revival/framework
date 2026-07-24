<?php
require_once dirname(__FILE__) . '/nativesessionserviceinterface.php';

/**
 * Defers session ownership to the existing Chisimba authentication pipeline.
 *
 * During the first native credential-verification rollout, authenticate and
 * abauth remain responsible for session creation and compatibility keys.
 */
class LegacyAuthSessionBridge implements NativeSessionServiceInterface
{
    public function establish($userId, array $attributes = array())
    {
        return false;
    }

    public function destroy()
    {
        return false;
    }

    public function regenerateIdentifier()
    {
        return false;
    }

    public function getUserId()
    {
        return null;
    }

    public function isAuthenticated()
    {
        return false;
    }

    public function get($name, $default = null)
    {
        return $default;
    }

    public function set($name, $value)
    {
    }

    public function remove($name)
    {
    }
}
