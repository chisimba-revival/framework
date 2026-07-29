<?php
/**
 * Atomic persistence contract for rotating persistent-login tokens.
 *
 * Concrete adapters must consume and replace a token in one transaction.
 *
 * @author Derek Keats
 */
interface PersistentLoginRepositoryInterface
{
    public function store(array $record);

    public function findActiveBySelector($selector, $now);

    public function rotate($id, array $replacement, $usedAt);

    public function revoke($id, $revokedAt);

    public function revokeAllForUser($userId, $revokedAt);

    public function purgeExpired($now);
}
