<?php
require_once dirname(__FILE__) . '/pendingauthentication.php';

/**
 * Creates and consumes short-lived pre-MFA authentication transactions.
 *
 * @author Derek Keats
 */
class PendingAuthenticationService
{
    const SESSION_KEY = 'nativePendingAuthentication';

    private $backend;
    private $clock;
    private $lifetime;

    public function __construct($backend, $lifetime = 300, $clock = null)
    {
        foreach (array('getSession', 'setSession', 'unsetSession') as $method) {
            if (!is_object($backend) || !method_exists($backend, $method)) {
                throw new InvalidArgumentException(
                    'Pending-auth backend must implement ' . $method . '().'
                );
            }
        }
        if ($clock !== null && !is_callable($clock)) {
            throw new InvalidArgumentException(
                'Pending-auth clock must be callable.'
            );
        }
        $this->backend = $backend;
        $this->lifetime = max(60, min(900, (int) $lifetime));
        $this->clock = $clock;
    }

    public function begin(
        $userId,
        $username,
        $remember,
        array $metadata = array()
    ) {
        $userId = trim((string) $userId);
        $username = trim((string) $username);
        if ($userId === '' || $username === '') {
            throw new InvalidArgumentException(
                'Pending authentication requires identity.'
            );
        }
        $now = $this->now();
        $record = new PendingAuthentication(
            bin2hex(random_bytes(32)),
            $userId,
            $username,
            (bool) $remember,
            $now,
            $now + $this->lifetime,
            $this->sanitiseMetadata($metadata)
        );
        $this->backend->setSession(self::SESSION_KEY, $this->toArray($record));
        return $record;
    }

    public function peek()
    {
        $record = $this->fromSession();
        if ($record === null || $record->expiresAt < $this->now()) {
            $this->clear();
            return null;
        }
        return $record;
    }

    public function consume($transactionId)
    {
        $record = $this->peek();
        $this->clear();
        return $record !== null
            && is_string($transactionId)
            && hash_equals($record->id, $transactionId)
            ? $record
            : null;
    }

    public function clear()
    {
        $this->backend->unsetSession(self::SESSION_KEY);
    }

    private function fromSession()
    {
        $data = $this->backend->getSession(self::SESSION_KEY, null);
        $required = array(
            'id', 'user_id', 'username', 'remember',
            'issued_at', 'expires_at', 'metadata',
        );
        if (!is_array($data)) {
            return null;
        }
        foreach ($required as $key) {
            if (!array_key_exists($key, $data)) {
                return null;
            }
        }
        return new PendingAuthentication(
            $data['id'],
            $data['user_id'],
            $data['username'],
            $data['remember'],
            $data['issued_at'],
            $data['expires_at'],
            is_array($data['metadata']) ? $data['metadata'] : array()
        );
    }

    private function toArray(PendingAuthentication $record)
    {
        return array(
            'id' => $record->id,
            'user_id' => $record->userId,
            'username' => $record->username,
            'remember' => $record->remember,
            'issued_at' => $record->issuedAt,
            'expires_at' => $record->expiresAt,
            'metadata' => $record->metadata,
        );
    }

    private function sanitiseMetadata(array $metadata)
    {
        $allowed = array('ip', 'user_agent', 'return_url');
        return array_intersect_key($metadata, array_flip($allowed));
    }

    private function now()
    {
        return $this->clock === null
            ? time()
            : (int) call_user_func($this->clock);
    }
}
