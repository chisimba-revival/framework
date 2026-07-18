<?php
/**
 * Provider-neutral authentication result.
 *
 * This object describes authentication outcome and the identity attributes
 * required to establish the traditional Chisimba user session. It performs no
 * session writes and has no dependency on LiveUser.
 */
class CanonicalAuthenticationResult
{
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILURE = 'failure';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_ERROR = 'error';

    private $status;
    private $provider;
    private $userId;
    private $username;
    private $identity;
    private $groups;
    private $roles;
    private $permissions;
    private $metadata;
    private $reason;

    private function __construct(
        $status,
        $provider,
        $userId,
        $username,
        array $identity,
        array $groups,
        array $roles,
        array $permissions,
        array $metadata,
        $reason
    ) {
        $this->status = (string) $status;
        $this->provider = (string) $provider;
        $this->userId = $userId === null ? null : (string) $userId;
        $this->username = $username === null ? null : (string) $username;
        $this->identity = $identity;
        $this->groups = array_values($groups);
        $this->roles = array_values($roles);
        $this->permissions = array_values($permissions);
        $this->metadata = $metadata;
        $this->reason = $reason === null ? null : (string) $reason;
    }

    public static function success(
        $provider,
        $userId,
        $username,
        array $identity = array(),
        array $groups = array(),
        array $roles = array(),
        array $permissions = array(),
        array $metadata = array()
    ) {
        if ($userId === null || trim((string) $userId) === '') {
            throw new InvalidArgumentException(
                'Successful authentication requires a user ID.'
            );
        }

        if ($username === null || trim((string) $username) === '') {
            throw new InvalidArgumentException(
                'Successful authentication requires a username.'
            );
        }

        return new self(
            self::STATUS_SUCCESS,
            $provider,
            $userId,
            $username,
            $identity,
            $groups,
            $roles,
            $permissions,
            $metadata,
            null
        );
    }

    public static function failure($provider, $reason, array $metadata = array())
    {
        return new self(
            self::STATUS_FAILURE,
            $provider,
            null,
            null,
            array(),
            array(),
            array(),
            array(),
            $metadata,
            $reason
        );
    }

    public static function inactive(
        $provider,
        $userId,
        $username,
        array $identity = array(),
        array $metadata = array()
    ) {
        return new self(
            self::STATUS_INACTIVE,
            $provider,
            $userId,
            $username,
            $identity,
            array(),
            array(),
            array(),
            $metadata,
            'inactive'
        );
    }

    public static function error($provider, $reason, array $metadata = array())
    {
        return new self(
            self::STATUS_ERROR,
            $provider,
            null,
            null,
            array(),
            array(),
            array(),
            array(),
            $metadata,
            $reason
        );
    }

    public function isSuccess()
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isInactive()
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getIdentity()
    {
        return $this->identity;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getPermissions()
    {
        return $this->permissions;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Return the provider-neutral comparison representation.
     */
    public function toSnapshotArray()
    {
        return array(
            'authenticated' => $this->isSuccess(),
            'status' => $this->status,
            'provider' => $this->provider,
            'user_id' => $this->userId,
            'username' => $this->username,
            'identity' => $this->identity,
            'groups' => $this->groups,
            'roles' => $this->roles,
            'permissions' => $this->permissions,
            'metadata' => $this->metadata,
            'reason' => $this->reason,
        );
    }

    /**
     * Return the fields historically consumed by storeUserSession().
     *
     * Password values are intentionally absent.
     */
    public function toLegacyUserRecord()
    {
        if (!$this->isSuccess()) {
            throw new LogicException(
                'Only successful results can produce a legacy user record.'
            );
        }

        $identity = $this->identity;

        return array(
            'username' => $this->username,
            'userid' => $this->userId,
            'title' => isset($identity['title'])
                ? (string) $identity['title'] : '',
            'firstname' => isset($identity['first_name'])
                ? (string) $identity['first_name'] : '',
            'surname' => isset($identity['surname'])
                ? (string) $identity['surname'] : '',
            'creationdate' => isset($identity['creation_date'])
                ? $identity['creation_date'] : null,
            'emailaddress' => isset($identity['email_address'])
                ? (string) $identity['email_address'] : '',
            'logins' => isset($identity['login_count'])
                ? $identity['login_count'] : 0,
            'isactive' => isset($identity['is_active'])
                && $identity['is_active'] ? '1' : '0',
            'accesslevel' => isset($identity['access_level'])
                ? (string) $identity['access_level'] : '',
        );
    }
}
