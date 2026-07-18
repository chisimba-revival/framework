<?php
/**
 * Immutable-style result object returned by a native authentication attempt.
 */
class NativeAuthenticationResult
{
    const STATUS_SUCCESS = 'success';
    const STATUS_INVALID_CREDENTIALS = 'invalid_credentials';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_LOCKED = 'locked';
    const STATUS_ERROR = 'error';

    private $status;
    private $userId;
    private $username;
    private $message;
    private $metadata;

    public function __construct(
        $status,
        $userId = null,
        $username = null,
        $message = '',
        array $metadata = array()
    ) {
        $this->status = (string) $status;
        $this->userId = $userId === null ? null : (string) $userId;
        $this->username = $username === null ? null : (string) $username;
        $this->message = (string) $message;
        $this->metadata = $metadata;
    }

    public function isSuccess()
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }
}
