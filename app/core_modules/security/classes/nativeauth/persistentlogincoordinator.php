<?php
/**
 * Connects remembered-login domain logic to the browser cookie boundary.
 *
 * @author Derek Keats
 */
class PersistentLoginCoordinator
{
    const COOKIE_NAME = 'chisimba_remember';
    private $service;
    private $policy;
    public function __construct(PersistentLoginService $service,
        PersistentLoginCookiePolicy $policy) {
        $this->service = $service; $this->policy = $policy;
    }
    public function issue($userId, $now, $lifetimeDays)
    {
        $value = $this->service->issue($userId, $now);
        return setcookie(self::COOKIE_NAME, $value,
            $this->policy->options($now + ((int) $lifetimeDays * 86400)));
    }
    public function restoreAndRotate($now)
    {
        if (empty($_COOKIE[self::COOKIE_NAME])) {
            return false;
        }
        $restored = $this->service->restoreAndRotate(
            $_COOKIE[self::COOKIE_NAME],
            $now
        );
        if (!$restored) {
            $this->clear();
            return false;
        }
        if (!setcookie(
            self::COOKIE_NAME,
            $restored['cookie'],
            $this->policy->options(
                $now + (PersistentLoginService::DEFAULT_LIFETIME_DAYS * 86400)
            )
        )) {
            return false;
        }
        return (string) $restored['user_id'];
    }

    public function revokeAllForUser($userId, $now)
    {
        $revoked = $this->service->revokeAllForUser($userId, $now);
        $this->clear();
        return $revoked;
    }

    public function clear()
    {
        unset($_COOKIE[self::COOKIE_NAME]);
        return setcookie(self::COOKIE_NAME, '',
            $this->policy->options(time() - 3600));
    }
}
