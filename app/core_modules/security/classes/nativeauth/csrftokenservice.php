<?php
/**
 * Single-use, expiring CSRF tokens stored through Chisimba's session API.
 *
 * Only a hash is retained in the session. Validation consumes the token,
 * whether it succeeds or fails, so a submitted credential cannot be replayed.
 *
 * @author Derek Keats
 */
class CsrfTokenService
{
    const SESSION_KEY = 'nativeAuthCsrfTokens';

    private $backend;
    private $clock;
    private $lifetime;

    public function __construct($backend, $lifetime = 900, $clock = null)
    {
        foreach (array('getSession', 'setSession', 'unsetSession') as $method) {
            if (!is_object($backend) || !method_exists($backend, $method)) {
                throw new InvalidArgumentException(
                    'CSRF session backend must implement ' . $method . '().'
                );
            }
        }
        if ($clock !== null && !is_callable($clock)) {
            throw new InvalidArgumentException('CSRF clock must be callable.');
        }
        $this->backend = $backend;
        $this->lifetime = max(60, min(3600, (int) $lifetime));
        $this->clock = $clock;
    }

    public function issue($context)
    {
        $context = $this->normaliseContext($context);
        $token = bin2hex(random_bytes(32));
        $tokens = $this->purgeExpired($this->load());
        $tokens[$context] = array(
            'hash' => hash('sha256', $token),
            'expires_at' => $this->now() + $this->lifetime,
        );
        $this->backend->setSession(self::SESSION_KEY, $tokens);
        return $token;
    }

    public function consume($context, $token)
    {
        $context = $this->normaliseContext($context);
        $tokens = $this->purgeExpired($this->load());
        $record = isset($tokens[$context]) ? $tokens[$context] : null;
        unset($tokens[$context]);
        if ($tokens) {
            $this->backend->setSession(self::SESSION_KEY, $tokens);
        } else {
            $this->backend->unsetSession(self::SESSION_KEY);
        }
        return is_array($record)
            && isset($record['hash'], $record['expires_at'])
            && (int) $record['expires_at'] >= $this->now()
            && is_string($token)
            && hash_equals($record['hash'], hash('sha256', $token));
    }

    private function load()
    {
        $tokens = $this->backend->getSession(self::SESSION_KEY, array());
        return is_array($tokens) ? $tokens : array();
    }

    private function purgeExpired(array $tokens)
    {
        $now = $this->now();
        foreach ($tokens as $context => $record) {
            if (!is_array($record)
                || !isset($record['expires_at'])
                || (int) $record['expires_at'] < $now) {
                unset($tokens[$context]);
            }
        }
        return $tokens;
    }

    private function normaliseContext($context)
    {
        $context = trim((string) $context);
        if (!preg_match('/^[a-z0-9._-]{1,80}$/i', $context)) {
            throw new InvalidArgumentException('Invalid CSRF token context.');
        }
        return $context;
    }

    private function now()
    {
        return $this->clock === null
            ? time()
            : (int) call_user_func($this->clock);
    }
}
