<?php
/**
 * Creates, verifies, rotates and revokes persistent-login credentials.
 *
 * Only the random verifier is sent to the browser. Persistence receives its
 * password hash. Successful restoration must create a fresh native session.
 *
 * @author Derek Keats
 */
class PersistentLoginService
{
    const DEFAULT_LIFETIME_DAYS = 30;

    private $repository;
    private $lifetimeDays;
    private $idFactory;

    public function __construct(
        PersistentLoginRepositoryInterface $repository,
        $lifetimeDays = self::DEFAULT_LIFETIME_DAYS,
        $idFactory = null
    ) {
        $this->repository = $repository;
        $this->lifetimeDays = max(1, (int) $lifetimeDays);
        $this->idFactory = $idFactory;
    }

    public function issue($userId, $now)
    {
        $token = $this->newToken($userId, $now);
        $this->repository->store($token['record']);
        return $token['cookie'];
    }

    public function restoreAndRotate($cookie, $now)
    {
        $parts = explode(':', (string) $cookie, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return false;
        }
        $record = $this->repository->findActiveBySelector($parts[0], $now);
        if (!$record || !password_verify($parts[1], $record['verifier_hash'])) {
            if ($record) {
                $this->repository->revoke($record['id'], $now);
            }
            return false;
        }
        $replacement = $this->newToken($record['user_id'], $now);
        if (!$this->repository->rotate(
            $record['id'],
            $replacement['record'],
            $now
        )) {
            return false;
        }
        return array(
            'user_id' => $record['user_id'],
            'cookie' => $replacement['cookie'],
        );
    }

    public function revokeAllForUser($userId, $now)
    {
        return $this->repository->revokeAllForUser($userId, $now);
    }

    private function newToken($userId, $now)
    {
        $selector = bin2hex(random_bytes(16));
        $verifier = bin2hex(random_bytes(32));
        $id = $this->idFactory
            ? call_user_func($this->idFactory)
            : bin2hex(random_bytes(16));
        return array(
            'cookie' => $selector . ':' . $verifier,
            'record' => array(
                'id' => $id,
                'user_id' => (string) $userId,
                'selector' => $selector,
                'verifier_hash' => password_hash(
                    $verifier,
                    PASSWORD_DEFAULT
                ),
                'issued_at' => (int) $now,
                'expires_at' => (int) $now
                    + ($this->lifetimeDays * 86400),
            ),
        );
    }
}
