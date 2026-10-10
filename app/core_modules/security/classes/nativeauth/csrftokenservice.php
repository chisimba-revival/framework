<?php
/**
 * Session-bound CSRF for authenticated forms; expiring tokens before login.
 *
 * Authenticated tokens survive editing, retries and concurrent tabs. They prove
 * request origin, not transaction uniqueness: mutations must use their domain
 * revision/idempotency checks. Anonymous tokens retain single-use validation.
 *
 * @author Derek Keats <derek@dkeats.com>
 */
class CsrfTokenService
{
    const SESSION_KEY = 'nativeAuthCsrfTokens';
    const MAX_TOKENS_PER_CONTEXT = 12;

    private $backend;
    private $clock;
    private $lifetime;
    private $identity;
    const AUTHENTICATED_KEY = 'nativeAuthenticatedFormCsrf';

    public function __construct($backend, $lifetime = 900, $clock = null, $identity = null)
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
        if ($identity !== null && !is_callable($identity)) {
            throw new InvalidArgumentException('CSRF identity resolver must be callable.');
        }
        $this->identity = $identity;
        $this->backend = $backend;
        $this->lifetime = max(60, min(3600, (int) $lifetime));
        $this->clock = $clock;
    }

    public function issue($context)
    {
        $identity = $this->authenticatedIdentity();
        if ($identity !== null) return $this->authenticatedToken($context, $identity, true);
        return $this->issueWithExpiry($context, $this->now() + $this->lifetime);
    }

    /**
     * Issue a token whose lifetime is bounded by the PHP session.
     *
     * Authenticated callers use the same login-bound form contract as issue().
     * Without an authenticated identity resolver, retain the legacy single-use
     * session token used by standalone/prelogin consumers.
     */
    public function issueForSession($context)
    {
        $identity = $this->authenticatedIdentity();
        if ($identity !== null) return $this->authenticatedToken($context, $identity, true);
        return $this->issueWithExpiry($context, PHP_INT_MAX);
    }

    private function issueWithExpiry($context, $expiresAt)
    {
        $context = $this->normaliseContext($context);
        $token = bin2hex(random_bytes(32));
        $tokens = $this->purgeExpired($this->load());
        $records = $this->recordsForContext($tokens, $context);
        $records[] = array(
            'hash' => hash('sha256', $token),
            'expires_at' => (int) $expiresAt,
        );
        $tokens[$context] = array_slice(
            $records,
            -self::MAX_TOKENS_PER_CONTEXT
        );
        $this->backend->setSession(self::SESSION_KEY, $tokens);
        return $token;
    }

    public function consume($context, $token)
    {
        $context = $this->normaliseContext($context);
        $identity = $this->authenticatedIdentity();
        if ($identity !== null && is_string($token)) {
            $expected = $this->authenticatedToken($context, $identity, false);
            if ($expected !== null && hash_equals($expected, $token)) return true;
        }
        $tokens = $this->purgeExpired($this->load());
        $records = $this->recordsForContext($tokens, $context);
        $submittedHash = is_string($token)
            ? hash('sha256', $token)
            : '';
        $matched = false;
        foreach ($records as $index => $record) {
            if ((int) $record['expires_at'] >= $this->now()
                && $submittedHash !== ''
                && hash_equals($record['hash'], $submittedHash)) {
                unset($records[$index]);
                $matched = true;
                break;
            }
        }
        if ($records) {
            $tokens[$context] = array_values($records);
        } else {
            unset($tokens[$context]);
        }
        if ($tokens) {
            $this->backend->setSession(self::SESSION_KEY, $tokens);
        } else {
            $this->backend->unsetSession(self::SESSION_KEY);
        }
        return $matched;
    }

    private function authenticatedIdentity()
    {
        $identity = $this->identity === null ? null : call_user_func($this->identity);
        return is_string($identity) && $identity !== '' ? $identity : null;
    }

    /** One secret per login, with context-separated tokens and constant storage. */
    private function authenticatedToken($context, $identity, $create)
    {
        $context = $this->normaliseContext($context);
        $record = $this->backend->getSession(self::AUTHENTICATED_KEY, null);
        if (!is_array($record) || ($record['identity'] ?? null) !== $identity
            || !is_string($record['secret'] ?? null)
            || !preg_match('/^[a-f0-9]{64}$/D', $record['secret'])) {
            if (!$create) return null;
            $record = array('identity' => $identity, 'secret' => bin2hex(random_bytes(32)));
            $this->backend->setSession(self::AUTHENTICATED_KEY, $record);
        }
        return hash_hmac('sha256', 'form:' . $context, hex2bin($record['secret']));
    }

    private function load()
    {
        $tokens = $this->backend->getSession(self::SESSION_KEY, array());
        return is_array($tokens) ? $tokens : array();
    }

    private function purgeExpired(array $tokens)
    {
        $now = $this->now();
        foreach (array_keys($tokens) as $context) {
            $records = array_values(array_filter(
                $this->recordsForContext($tokens, $context),
                function ($record) use ($now) {
                    return (int) $record['expires_at'] >= $now;
                }
            ));
            if ($records) {
                $tokens[$context] = $records;
            } else {
                unset($tokens[$context]);
            }
        }
        return $tokens;
    }

    /**
     * Read both the original single-record format and the concurrent format.
     */
    private function recordsForContext(array $tokens, $context)
    {
        if (!isset($tokens[$context]) || !is_array($tokens[$context])) {
            return array();
        }
        $value = $tokens[$context];
        if (isset($value['hash'], $value['expires_at'])) {
            $value = array($value);
        }
        $records = array();
        foreach ($value as $record) {
            if (is_array($record)
                && isset($record['hash'], $record['expires_at'])
                && is_string($record['hash'])) {
                $records[] = $record;
            }
        }
        return $records;
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
