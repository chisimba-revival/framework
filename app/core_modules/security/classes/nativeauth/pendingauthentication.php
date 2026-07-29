<?php
/**
 * Immutable pending-authentication transaction.
 *
 * This record contains no password, TOTP secret, recovery code, or final
 * authenticated-session marker.
 *
 * @author Derek Keats
 */
class PendingAuthentication
{
    public $id;
    public $userId;
    public $username;
    public $remember;
    public $issuedAt;
    public $expiresAt;
    public $metadata;

    public function __construct(
        $id,
        $userId,
        $username,
        $remember,
        $issuedAt,
        $expiresAt,
        array $metadata = array()
    ) {
        $this->id = (string) $id;
        $this->userId = (string) $userId;
        $this->username = (string) $username;
        $this->remember = (bool) $remember;
        $this->issuedAt = (int) $issuedAt;
        $this->expiresAt = (int) $expiresAt;
        $this->metadata = $metadata;
    }
}
