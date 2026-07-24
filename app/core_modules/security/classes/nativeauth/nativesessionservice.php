<?php
require_once dirname(__FILE__) . '/nativesessionserviceinterface.php';

/**
 * Authentication-session adapter for Chisimba's engine session API.
 *
 * The injected backend must expose getSession(), setSession(), and
 * unsetSession(). A regeneration callback is injected for testability; the
 * production default uses session_regenerate_id(true).
 */
class NativeSessionService implements NativeSessionServiceInterface
{
    const KEY_AUTHENTICATED = 'isLoggedIn';
    const KEY_USER_ID = 'userid';
    const KEY_USERNAME = 'username';
    const KEY_PROVIDER = 'nativeAuthProvider';
    const KEY_METADATA = 'nativeAuthMetadata';
    const KEY_AUTHENTICATED_AT = 'nativeAuthenticatedAt';

    private $backend;
    private $regenerator;
    private $clock;

    public function __construct(
        $backend,
        $regenerator = null,
        $clock = null
    ) {
        foreach (array('getSession', 'setSession', 'unsetSession') as $method) {
            if (!is_object($backend) || !method_exists($backend, $method)) {
                throw new InvalidArgumentException(
                    'Session backend must implement ' . $method . '().'
                );
            }
        }

        if ($regenerator !== null && !is_callable($regenerator)) {
            throw new InvalidArgumentException(
                'Session regenerator must be callable.'
            );
        }

        if ($clock !== null && !is_callable($clock)) {
            throw new InvalidArgumentException('Clock must be callable.');
        }

        $this->backend = $backend;
        $this->regenerator = $regenerator;
        $this->clock = $clock;
    }

    public function establish($userId, array $attributes = array())
    {
        $userId = trim((string) $userId);

        if ($userId === '') {
            return false;
        }

        if (!$this->regenerateIdentifier()) {
            return false;
        }

        $username = isset($attributes['username'])
            ? trim((string) $attributes['username'])
            : '';
        $provider = isset($attributes['provider'])
            ? trim((string) $attributes['provider'])
            : '';
        $metadata = isset($attributes['metadata'])
            && is_array($attributes['metadata'])
            ? $attributes['metadata']
            : array();

        try {
            $this->set(self::KEY_AUTHENTICATED, true);
            $this->set(self::KEY_USER_ID, $userId);

            if ($username !== '') {
                $this->set(self::KEY_USERNAME, $username);
            } else {
                $this->remove(self::KEY_USERNAME);
            }

            if ($provider !== '') {
                $this->set(self::KEY_PROVIDER, $provider);
            } else {
                $this->remove(self::KEY_PROVIDER);
            }

            $this->set(self::KEY_METADATA, $metadata);
            $this->set(self::KEY_AUTHENTICATED_AT, $this->now());
        } catch (Throwable $exception) {
            $this->clearAuthenticationKeys();

            return false;
        }

        return $this->isAuthenticated()
            && $this->getUserId() === $userId;
    }

    public function destroy()
    {
        try {
            $this->clearAuthenticationKeys();
        } catch (Throwable $exception) {
            return false;
        }

        /*
         * Rotate the identifier after clearing identity as defence in depth.
         * Logged-out state remains valid even if rotation is unavailable.
         */
        $this->regenerateIdentifier();

        return !$this->isAuthenticated() && $this->getUserId() === null;
    }

    public function regenerateIdentifier()
    {
        if ($this->regenerator !== null) {
            return (bool) call_user_func($this->regenerator);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        return session_regenerate_id(true);
    }

    public function getUserId()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        $userId = trim((string) $this->get(self::KEY_USER_ID, ''));

        return $userId === '' ? null : $userId;
    }

    public function isAuthenticated()
    {
        return $this->get(self::KEY_AUTHENTICATED, false) === true
            && trim((string) $this->get(self::KEY_USER_ID, '')) !== '';
    }

    public function get($name, $default = null)
    {
        return $this->backend->getSession((string) $name, $default);
    }

    public function set($name, $value)
    {
        $this->backend->setSession((string) $name, $value);
    }

    public function remove($name)
    {
        $this->backend->unsetSession((string) $name);
    }

    private function clearAuthenticationKeys()
    {
        foreach ($this->authenticationKeys() as $key) {
            $this->remove($key);
        }
    }

    private function authenticationKeys()
    {
        return array(
            self::KEY_AUTHENTICATED,
            self::KEY_USER_ID,
            self::KEY_USERNAME,
            self::KEY_PROVIDER,
            self::KEY_METADATA,
            self::KEY_AUTHENTICATED_AT,
        );
    }

    private function now()
    {
        if ($this->clock !== null) {
            return (int) call_user_func($this->clock);
        }

        return time();
    }
}
