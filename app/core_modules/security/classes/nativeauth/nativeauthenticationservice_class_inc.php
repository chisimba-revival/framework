<?php

/**
 * Native PHP authentication service for Chisimba.
 *
 * IMPORTANT:
 * This class is scaffold-only and is not wired into the engine.
 * Its methods deliberately fail closed until repositories, password
 * verification and session behaviour have been implemented and tested.
 */
class NativeAuthenticationService implements AuthenticationServiceInterface
{
    /**
     * @var UserRepositoryInterface|null
     */
    protected $userRepository = null;

    /**
     * @var GroupRepositoryInterface|null
     */
    protected $groupRepository = null;

    /**
     * @var PermissionRepositoryInterface|null
     */
    protected $permissionRepository = null;

    /**
     * @var PasswordVerifierInterface|null
     */
    protected $passwordVerifier = null;

    /**
     * @var AuthSessionInterface|null
     */
    protected $session = null;

    /**
     * @var array
     */
    protected $errors = array();

    /**
     * Constructor.
     *
     * @param UserRepositoryInterface       $userRepository
     * @param GroupRepositoryInterface      $groupRepository
     * @param PermissionRepositoryInterface $permissionRepository
     * @param PasswordVerifierInterface     $passwordVerifier
     * @param AuthSessionInterface          $session
     */
    public function __construct(
        UserRepositoryInterface $userRepository,
        GroupRepositoryInterface $groupRepository,
        PermissionRepositoryInterface $permissionRepository,
        PasswordVerifierInterface $passwordVerifier,
        AuthSessionInterface $session
    ) {
        $this->userRepository = $userRepository;
        $this->groupRepository = $groupRepository;
        $this->permissionRepository = $permissionRepository;
        $this->passwordVerifier = $passwordVerifier;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->errors = array();

        return $this->session->start();
    }

    /**
     * {@inheritdoc}
     */
    public function login($identifier, $password)
    {
        $this->errors = array(
            'Native authentication is not enabled yet.',
        );

        /*
         * Fail closed until all of the following are verified:
         *
         * 1. identifier lookup precedence;
         * 2. stored password formats;
         * 3. inactive-account behaviour;
         * 4. session identity format;
         * 5. successful-login updates.
         */
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function logout()
    {
        $this->errors = array();

        return $this->session->destroy();
    }

    /**
     * {@inheritdoc}
     */
    public function isLoggedIn()
    {
        return is_array($this->session->getIdentity());
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUserId()
    {
        $identity = $this->session->getIdentity();

        if (!is_array($identity)) {
            return null;
        }

        if (array_key_exists('userid', $identity)) {
            return $identity['userid'];
        }

        if (array_key_exists('id', $identity)) {
            return $identity['id'];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
