<?php
/**
 * Provider-neutral primary-authentication result.
 *
 * This object contains identity proof only. Authorisation data such as groups,
 * roles, permissions, and administrator state is intentionally excluded.
 */
class CanonicalAuthenticationResult
{
    const STATUS_SUCCESS = 'success';
    const STATUS_INVALID_CREDENTIALS = 'invalid_credentials';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_LOCKED = 'locked';
    const STATUS_MFA_REQUIRED = 'mfa_required';
    const STATUS_ERROR = 'error';

    private $status;
    private $providerId;
    private $userId;
    private $username;
    private $attributes;
    private $metadata;
    private $reason;

    private function __construct(
        $status,
        $providerId,
        $userId,
        $username,
        array $attributes,
        array $metadata,
        $reason
    ) {
        $this->status = (string) $status;
        $this->providerId = (string) $providerId;
        $this->userId = $userId === null ? null : (string) $userId;
        $this->username = $username === null ? null : (string) $username;
        $this->attributes = $attributes;
        $this->metadata = $metadata;
        $this->reason = $reason === null ? null : (string) $reason;
    }

    public static function success(
        $providerId,
        $userId,
        $username,
        array $attributes = array(),
        array $metadata = array()
    ) {
        if (trim((string) $userId) === '' || trim((string) $username) === '') {
            throw new InvalidArgumentException(
                'Successful authentication requires user ID and username.'
            );
        }

        return new self(
            self::STATUS_SUCCESS,
            $providerId,
            $userId,
            $username,
            $attributes,
            $metadata,
            null
        );
    }

    public static function failure(
        $providerId,
        $status = self::STATUS_INVALID_CREDENTIALS,
        $reason = null,
        array $metadata = array()
    ) {
        $allowed = array(
            self::STATUS_INVALID_CREDENTIALS,
            self::STATUS_INACTIVE,
            self::STATUS_LOCKED,
            self::STATUS_ERROR,
        );

        if (!in_array($status, $allowed, true)) {
            throw new InvalidArgumentException(
                'Unsupported authentication failure status.'
            );
        }

        return new self(
            $status,
            $providerId,
            null,
            null,
            array(),
            $metadata,
            $reason
        );
    }

    public static function mfaRequired(
        $providerId,
        $userId,
        $username,
        array $attributes = array(),
        array $metadata = array()
    ) {
        return new self(
            self::STATUS_MFA_REQUIRED,
            $providerId,
            $userId,
            $username,
            $attributes,
            $metadata,
            'mfa_required'
        );
    }

    public function isSuccess()
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function requiresMfa()
    {
        return $this->status === self::STATUS_MFA_REQUIRED;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getProviderId()
    {
        return $this->providerId;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function getReason()
    {
        return $this->reason;
    }
}
