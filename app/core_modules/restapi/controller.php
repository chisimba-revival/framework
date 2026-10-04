<?php
/**
 * REST API Module Controller
 * 
 * Handles all /api/v1/* routes with RESTful conventions
 */
require_once __DIR__ . '/classes/restcontroller_class_inc.php';

class restapi extends RestController
{
    protected $db;
    
    public function dispatch($action)
    {
        // The action from engine will be the full route path
        // e.g., "users/123/posts" for GET /api/v1/users/123/posts
        return parent::dispatch($action);
    }

    // ============================================================
    // AUTHENTICATION ENDPOINTS
    // ============================================================

    /**
     * GET /api/v1/auth/login
     * Issue the abuse-protection evidence a login submission must carry.
     *
     * The canonical GuardedLoginApplicationService refuses any login that has
     * no signed evidence (AbuseProtectionService::evaluate() rejects with
     * reason 'form_evidence'), and rejects a submission faster than the
     * configured minimum dwell time. The web login page satisfies this by
     * rendering hidden fields issued here in the page request. A stateless API
     * client has no such page, so it must fetch the evidence first and submit
     * it back. This mirrors the web contract rather than weakening it: the
     * signature is HMAC'd with the installation master key, so evidence cannot
     * be minted by the client.
     *
     * Public endpoint: it reveals no user data and no secret material, only a
     * short-lived signed assertion.
     */
    public function GET_auth_login(): void
    {
        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();
        $evidence = $stack['abuse']->issueFormEvidence('native.login');

        if (!is_array($evidence)
            || !isset($evidence['issued_at'], $evidence['nonce'], $evidence['signature'])
        ) {
            throw new RuntimeException('Login evidence could not be issued');
        }

        // The canonical login also consumes a CSRF token for the "begin" step
        // (AuthenticationApplicationService::CSRF_BEGIN, which is the same
        // 'native_auth_begin' context the web login page uses). Issuing it here
        // keeps that control intact instead of relaxing it for the API.
        //
        // The class is a plain collaborator, not a ChisimbaObject, so it cannot
        // be loaded with getObject(). Load the class and read the constant from
        // the canonical source rather than duplicating the literal here.
        $this->objEngine->loadClass(
            'authenticationapplicationservice',
            'security'
        );
        $csrfContext = constant(
            'AuthenticationApplicationService::CSRF_BEGIN'
        );

        $csrfToken = $stack['csrf']->issue($csrfContext);

        if (!is_string($csrfToken) || $csrfToken === '') {
            throw new RuntimeException('Login CSRF token could not be issued');
        }

        $settings = $this->getObject('dbsysconfig', 'sysconfig');

        $this->respond([
            'csrf_token' => $csrfToken,
            'issued_at' => (int) $evidence['issued_at'],
            'nonce' => (string) $evidence['nonce'],
            'signature' => (string) $evidence['signature'],
            // Tell the client how long it must wait before submitting, so it
            // does not have to guess and retry. These mirror the defaults the
            // composition root applies when the settings are unset.
            'minimum_seconds' => (int) $settings->getValue(
                'login_abuse_minimum_seconds',
                'security',
                1
            ),
            'maximum_seconds' => (int) $settings->getValue(
                'login_abuse_maximum_seconds',
                'security',
                3600
            ),
        ]);
    }

    /**
     * POST /api/v1/auth/login
     * Login with username/password, returns access + refresh tokens.
     *
     * Credential verification is delegated entirely to the canonical security
     * composition root (NativeAuthWebComposition -> GuardedLoginApplicationService
     * -> auth_database -> LocalPasswordProvider -> NativePasswordVerifier). This
     * controller must never read tbl_users or hash passwords itself.
     *
     * The canonical provider resolves the identifier by username only; email is
     * deliberately not accepted as a login identifier.
     *
     * The csrf_token, abuse_issued_at, abuse_nonce and abuse_signature fields
     * are required: they come from GET /api/v1/auth/login. The optional
     * honeypot field `website` must be absent or empty.
     */
    public function POST_auth_login(): void
    {
        $data = $this->validate($this->getRequestData(), [
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
            'csrf_token' => 'required|string|max:255',
            'abuse_issued_at' => 'required|integer|min:1',
            'abuse_nonce' => 'required|string|max:64',
            'abuse_signature' => 'required|string|max:128',
            'website' => 'sometimes|string|max:255'
        ]);

        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();

        $metadata = [
            'ip' => $this->getClientIp(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT'])
                ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255)
                : ''
        ];

        $remember = !empty($data['remember']);

        $result = $stack['guarded_login']->begin(
            (string) $data['csrf_token'],
            trim($data['username']),
            $data['password'],
            $remember,
            $metadata,
            [
                'issued_at' => (int) $data['abuse_issued_at'],
                'nonce' => (string) $data['abuse_nonce'],
                'signature' => (string) $data['abuse_signature'],
                'website' => isset($data['website']) ? (string) $data['website'] : '',
            ]
        );

        $status = isset($result['status']) ? $result['status'] : '';

        // Abuse protection refuses before any credential is checked, so these
        // reasons say nothing about the account or the password. They are
        // reported distinctly because they are different client mistakes:
        // a caller who posts inside the abuse-evidence window needs to know it
        // was the timing, not that their password is wrong.
        if ($status !== 'complete') {
            $reason = isset($result['reason']) ? (string) $result['reason'] : '';
            $retryAfter = isset($result['retry_after']) ? (int) $result['retry_after'] : 0;

            if ($reason === 'rate_limit') {
                throw new RateLimitException(
                    'Too many login attempts. Try again later.',
                    $retryAfter > 0 ? $retryAfter : 60
                );
            }

            if ($reason === 'form_evidence') {
                throw new ValidationException([
                    'abuse_evidence' => 'Login evidence is invalid or expired. '
                        . 'Request it again with GET /api/v1/auth/login and post '
                        . 'after the advertised minimum_seconds has elapsed.'
                ]);
            }

            if ($reason === 'honeypot') {
                throw new ValidationException([
                    'website' => 'This field must be left empty.'
                ]);
            }

            // MFA and enrolment are interactive flows. This API issues stateless
            // bearer tokens, so anything other than a completed login fails closed
            // rather than granting partial access. Reaching here means the request
            // itself was rejected, so do not imply the password was wrong.
            if ($reason !== '') {
                throw new AuthenticationException('Login could not be completed');
            }

            throw new AuthenticationException('Invalid credentials');
        }

        $userId = $stack['sessions']->getUserId();

        if (empty($userId)) {
            throw new AuthenticationException('Invalid credentials');
        }

        $userService = $this->getObject('userservice', 'security');
        $userRow = $userService->findByUserId($userId);

        if (!is_array($userRow)) {
            throw new AuthenticationException('Invalid credentials');
        }

        // Issue tokens
        $options = [];
        if ($remember) {
            $options['ttl'] = 86400 * 30; // 30 days for remember me
        }

        $tokens = $this->auth->issueTokens($userId, $options);

        $this->respondCreated([
            'user' => $this->presentUser($userRow),
            'tokens' => $tokens
        ]);
    }

    /**
     * Present a canonical tbl_users row in the API's public user shape.
     *
     * `role` is deliberately NOT sourced from tbl_users.accesslevel: the canonical
     * userservice cannot write that column (it is absent from normaliseWriteData)
     * and role data belongs to the permission identity / group services.
     *
     * @param array $userRow Canonical userservice row.
     * @return array
     */
    private function presentUser(array $userRow): array
    {
        return [
            'id' => isset($userRow['userid']) ? $userRow['userid'] : '',
            'username' => isset($userRow['username']) ? $userRow['username'] : '',
            'email' => isset($userRow['emailaddress']) ? $userRow['emailaddress'] : '',
            'fullname' => trim(
                (isset($userRow['firstname']) ? $userRow['firstname'] : '') . ' '
                . (isset($userRow['surname']) ? $userRow['surname'] : '')
            ),
            'is_active' => isset($userRow['isactive']) ? (int) $userRow['isactive'] : 0
        ];
    }

    /**
     * Best-effort client IP for abuse evaluation only.
     *
     * Deliberately ignores X-Forwarded-For: the abuse policy trusts this value,
     * and a client-supplied header must not be able to influence it.
     *
     * @return string
     */
    private function getClientIp(): string
    {
        return isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    }

    /**
     * POST /api/v1/auth/refresh
     * Exchange a refresh token for a new token pair
     *
     * The refresh token is rotated, and the session identifier is rotated with
     * it. The session must not outlive the credential that created it, so
     * retiring the old refresh token retires the old session identifier too;
     * the identity is preserved because the same account is still signed in.
     * Without this, a cookie captured under the previous token pair would
     * remain usable after that pair was revoked.
     */
    public function POST_auth_refresh(): void
    {
        $data = $this->validate($this->getRequestData(), [
            'refresh_token' => 'required|string'
        ]);

        $tokens = $this->auth->refreshAccessToken($data['refresh_token']);
        
        if (!$tokens) {
            throw new AuthenticationException('Invalid or expired refresh token');
        }

        // Do not hand out a token pair for an account that no longer exists or
        // has been deactivated. requireAuth() re-checks this on every call, so
        // the new tokens would be useless, but issuing them anyway lets a token
        // belonging to a deleted account keep rotating forever and turns this
        // endpoint into an oracle for whether a token is valid. Revoke what was
        // just issued and fail closed instead.
        // validateJWT() returns [$userId, $payload], so the subject is the
        // first element, not a 'sub' key.
        $validated = $this->auth->validateJWT($tokens['access_token']);
        $refreshedUserId = is_array($validated) ? (string) ($validated[0] ?? '') : '';

        if ($refreshedUserId === '') {
            throw new AuthenticationException('Invalid or expired refresh token');
        }

        $userService = $this->getObject('userservice', 'security');
        $refreshedUser = $userService->findByUserId($refreshedUserId);

        if (!is_array($refreshedUser) || !isset($refreshedUser['userid'])) {
            $this->auth->revokeAllUserTokens($refreshedUserId);
            throw new AuthenticationException('Invalid or expired refresh token');
        }

        if (isset($refreshedUser['isactive']) && !$refreshedUser['isactive']) {
            $this->auth->revokeAllUserTokens($refreshedUserId);
            throw new AuthenticationException('Invalid or expired refresh token');
        }

        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();

        if ($stack['sessions']->isAuthenticated()) {
            $stack['sessions']->regenerateIdentifier();
        }

        $this->respond($tokens);
    }

    /**
     * POST /api/v1/auth/logout
     * Revoke refresh token and destroy the session (requires authentication)
     *
     * Revoking the token is not enough on its own. The canonical file services
     * resolve the caller from the PHP session, so a cookie captured before
     * logout would keep granting file and folder access long after the token
     * it was issued alongside had been revoked. The session therefore only
     * exists for as long as the credential that created it: destroying the
     * token destroys the session, via NativeSessionService::destroy(), which
     * clears the authentication keys and then regenerates the session
     * identifier so a captured cookie cannot be replayed.
     */
    public function POST_auth_logout(): void
    {
        $this->requireAuth();
        
        $data = $this->validate($this->getRequestData(), [
            'refresh_token' => 'sometimes|string',
            'revoke_all' => 'sometimes|boolean'
        ]);

        if (!empty($data['revoke_all'])) {
            $this->auth->revokeAllUserTokens($this->currentUser['id']);
        } elseif (!empty($data['refresh_token'])) {
            $refreshHash = hash('sha256', $data['refresh_token']);
            $this->auth->revokeRefreshToken($refreshHash);
        }

        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();

        if (!$stack['sessions']->destroy()) {
            throw new RuntimeException('Session could not be destroyed');
        }

        $this->respond(['message' => 'Logged out successfully']);
    }

    /**
     * GET /api/v1/auth/me
     * Get current authenticated user info
     */
    public function GET_auth_me(): void
    {
        $this->requireAuth();
        $this->respond($this->currentUser);
    }

    // ============================================================
    // API KEY MANAGEMENT
    // ============================================================

    /**
     * GET /api/v1/auth/api-keys
     * List user's API keys
     */
    public function GET_auth_api_keys(): void
    {
        $this->requireAuth();
        $keys = $this->auth->listApiKeys($this->currentUser['id']);
        $this->respond($keys);
    }

    /**
     * POST /api/v1/auth/api-keys
     * Create a new API key
     */
    public function POST_auth_api_keys(): void
    {
        $this->requireAuth();
        
        $data = $this->validate($this->getRequestData(), [
            'name' => 'required|string|max:100',
            'scopes' => 'sometimes|array',
            'expires_at' => 'sometimes|string' // ISO 8601 date
        ]);

        $result = $this->auth->createApiKey(
            $this->currentUser['id'],
            $data['name'],
            $data['scopes'] ?? [],
            $data['expires_at'] ?? null
        );

        $this->respondCreated($result);
    }

    /**
     * DELETE /api/v1/auth/api-keys/{id}
     * Revoke an API key
     */
    public function DELETE_auth_api_keys_by_id($id): void
    {
        $this->requireAuth();
        
        if (!$this->auth->revokeApiKey($this->currentUser['id'], (int)$id)) {
            throw new NotFoundException('API key not found');
        }

        $this->respondNoContent();
    }

    // ============================================================
    // USERS RESOURCE
    // ============================================================

    /**
     * GET /api/v1/users
     * List users with pagination.
     *
     * Reads go through UserService::listUsers(). That canonical read has no
     * LIMIT/OFFSET and returns no role column, so pagination is applied over the
     * returned set and role data is left to the permission services. The `role`
     * query filter is therefore rejected rather than silently ignored.
     */
    public function GET_users(): void
    {
        $this->requireAuth();
        $this->requirePermission('users.view');

        $p = $this->getPagination();

        if ($this->input('role')) {
            throw new ValidationException([
                'role' => 'Filtering by role is not supported. Role data comes '
                    . 'from the permission identity and group services.'
            ]);
        }

        $userService = $this->getObject('userservice', 'security');
        $rows = $userService->listUsers((string) $this->input('search'), false);

        if (!is_array($rows)) {
            $rows = [];
        }

        $total = count($rows);
        $slice = array_slice($rows, $p['offset'], $p['pageSize']);

        $users = [];
        foreach ($slice as $row) {
            if (is_array($row)) {
                $users[] = $this->presentUser($row);
            }
        }

        $this->respondPaginated($users, $p['page'], $p['pageSize'], $total);
    }

    /**
     * POST /api/v1/users
     * Create a new user (admin only)
     */
    public function POST_users(): void
    {
        $this->requireAuth();
        $this->requirePermission('users.create');

        $data = $this->validate($this->getRequestData(), [
            'username' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'fullname' => 'sometimes|string|max:100',
            'is_active' => 'sometimes|boolean'
        ]);

        $names = $this->splitFullName(isset($data['fullname']) ? $data['fullname'] : '');
        if ($names === null) {
            throw new ValidationException([
                'fullname' => 'A first name is required so the user can be provisioned'
            ]);
        }

        $userService = $this->getObject('userservice', 'security');
        $userid = (string) $data['username'];

        if (!$userService->userIdAvailable($userid)) {
            throw new ConflictException('User identifier is already in use');
        }

        if (!$userService->usernameAvailable($userid)) {
            throw new ConflictException('Username is already in use');
        }

        $email = strtolower(trim((string) $data['email']));
        if (!$userService->emailAvailable($email)) {
            throw new ConflictException('Email address is already in use');
        }

        $provisioning = $this->getObject('userprovisioningservice', 'security');

        $result = $provisioning->createLocalUser([
            'userid' => $userid,
            'username' => $userid,
            'firstname' => $names['firstname'],
            'surname' => $names['surname'],
            'emailaddress' => $email,
            'isactive' => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
            'howcreated' => 'registration-service'
        ], $data['password']);

        if (empty($result['ok'])) {
            $this->raiseForUserServiceCode(
                isset($result['code']) ? $result['code'] : 'create_failed',
                'Could not create the user'
            );
        }

        $record = $userService->findByUserId($result['userId']);

        if (!is_array($record)) {
            throw new NotFoundException('User');
        }

        $this->respondCreated($this->presentUser($record));
    }

    /**
     * GET /api/v1/users/{id}
     * Get a single user
     */
    public function GET_users_by_id($id): void
    {
        $this->requireAuth();

        // Users can view their own profile, admins can view any
        if ($id !== $this->currentUser['id'] && !$this->can('users.view')) {
            throw new AuthorizationException('Cannot view other users');
        }

        $userService = $this->getObject('userservice', 'security');
        $record = $userService->findByUserId($id);

        if (!is_array($record) || !isset($record['userid'])) {
            throw new NotFoundException('User');
        }

        $this->respond($this->presentUser($record));
    }

    /**
     * PATCH /api/v1/users/{id}
     * Update a user (partial update)
     */
    public function PATCH_users_by_id($id): void
    {
        $this->requireAuth();

        // Users can update their own profile, admins can update any
        if ($id !== $this->currentUser['id'] && !$this->can('users.edit')) {
            throw new AuthorizationException('Cannot edit other users');
        }

        $data = $this->validate($this->getRequestData(), [
            'email' => 'sometimes|email',
            'fullname' => 'sometimes|string|max:100',
            'is_active' => 'sometimes|boolean',
            'password' => 'sometimes|string|min:8'
        ]);

        $userService = $this->getObject('userservice', 'security');
        $record = $userService->findByUserId($id);

        if (!is_array($record) || !isset($record['userid'])) {
            throw new NotFoundException('User');
        }

        $update = [];

        if (isset($data['email'])) {
            $email = strtolower(trim((string) $data['email']));
            if (!$userService->emailAvailable($email, $id)) {
                throw new ConflictException('Email address is already in use');
            }
            $update['emailaddress'] = $email;
        }

        if (isset($data['fullname'])) {
            $names = $this->splitFullName($data['fullname']);
            if ($names === null) {
                throw new ValidationException([
                    'fullname' => 'A first name is required'
                ]);
            }
            $update['firstname'] = $names['firstname'];
            $update['surname'] = $names['surname'];
        }

        if (isset($data['is_active'])) {
            // Account status is administrative, not profile data. The
            // self-or-permission check above lets a user edit their own
            // record without holding users.edit, so without this gate anyone
            // could deactivate or reactivate their own account. Refuse
            // outright rather than silently dropping the field, because a
            // caller that believes they set it must be told they did not.
            if (!$this->can('users.edit')) {
                throw new AuthorizationException(
                    'Changing account status requires the users.edit permission'
                );
            }

            $update['isactive'] = $data['is_active'] ? 1 : 0;
        }

        if (!empty($update)) {
            $result = $userService->updateUser($id, $update);

            if (empty($result['ok'])) {
                $this->raiseForUserServiceCode(
                    isset($result['code']) ? $result['code'] : 'update_failed',
                    'Could not update the user'
                );
            }
        }

        if (isset($data['password'])) {
            $authService = $this->getObject('authenticationservice', 'security');

            try {
                $hash = $authService->createPasswordHash($data['password']);
            } catch (InvalidArgumentException $exception) {
                throw new ValidationException([
                    'password' => 'Password could not be processed'
                ]);
            }

            $passwordResult = $userService->updatePasswordHash($id, $hash);

            if (empty($passwordResult['ok'])) {
                $this->raiseForUserServiceCode(
                    isset($passwordResult['code'])
                        ? $passwordResult['code']
                        : 'password_update_failed',
                    'Could not update the password'
                );
            }
        }

        $record = $userService->findByUserId($id);

        $this->respond($this->presentUser($record));
    }

    /**
     * DELETE /api/v1/users/{id}
     * Delete a user (admin only)
     *
     * There is no general-purpose user delete in the canonical services, so this
     * only removes accounts that the provisioning rollback path still considers
     * safe to remove: the permission identity and the tbl_users record must both
     * still match the identifiers captured at creation. Anything else fails
     * closed with an actionable message rather than deleting a live account.
     *
     * The account's refresh tokens are revoked first. Without that, a refresh
     * token issued before deletion stays live and keeps minting new access
     * tokens for a user record that no longer exists.
     *
     * This is not a full deprovisioning. It does not remove API keys, MFA
     * enrolments or persistent-login rows, and it does not check whether the
     * account has ever been used to sign in; a real offboarding path belongs in
     * the canonical security services, not here.
     */
    public function DELETE_users_by_id($id): void
    {
        $this->requireAuth();
        $this->requirePermission('users.delete');

        if ($id === $this->currentUser['id']) {
            throw new ConflictException('Cannot delete your own account');
        }

        $userService = $this->getObject('userservice', 'security');
        $record = $userService->findByUserId($id);

        if (!is_array($record) || !isset($record['userid'], $record['id'])) {
            throw new NotFoundException('User');
        }

        $identityService = $this->getObject('identityservice', 'security');
        $permissionUserId = $identityService->permissionUserIdForUser($id);

        if ($permissionUserId === null) {
            throw new ConflictException(
                'User has no permission identity, so the account cannot be '
                . 'safely removed'
            );
        }

        // Revoke the account's refresh tokens before anything is removed. If the
        // account were deleted while a refresh token stayed live, that token
        // could keep minting valid access tokens for a user that no longer
        // exists, indefinitely, because each refresh rotates and reissues.
        // Revoking first means a failure here leaves the account untouched.
        $authService = $this->getObject('tokenauthservice', 'security');
        $authService->revokeAllUserTokens((string) $id);

        if (!$identityService->rollbackProvisionedIdentity($id, $permissionUserId)) {
            throw new ConflictException(
                'Permission identity could not be removed, so the account was '
                . 'left untouched'
            );
        }

        $rollback = $userService->rollbackProvisionedUser($id, $record['id']);

        if (empty($rollback['ok'])) {
            // The user record is gone; report the guard that rejected it.
            throw new ConflictException(
                'Permission identity was removed but the user record could not '
                . 'be removed (' . $rollback['code'] . ')'
            );
        }

        $this->respondNoContent();
    }

    /**
     * Split a full name into canonical first/surname parts.
     *
     * @param string $fullName
     * @return array|null Null when no first name could be determined.
     */
    private function splitFullName(string $fullName): ?array
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return null;
        }

        $parts = preg_split('/\s+/', $fullName, 2);

        return [
            'firstname' => $parts[0],
            'surname' => isset($parts[1]) ? trim($parts[1]) : ''
        ];
    }

    /**
     * Translate a canonical service result code into the matching API error.
     *
     * Unknown codes fail closed as a conflict rather than being reported as
     * success.
     *
     * @param string $code
     * @param string $fallbackMessage
     * @throws ValidationException
     * @throws ConflictException
     * @throws NotFoundException
     */
    private function raiseForUserServiceCode(string $code, string $fallbackMessage): void
    {
        $fieldMap = [
            'userid_taken' => 'username',
            'username_taken' => 'username',
            'email_taken' => 'email',
            'invalid_password' => 'password',
            'invalid_password_hash' => 'password',
            'missing_password_hash' => 'password'
        ];

        if (isset($fieldMap[$code])) {
            $errors = [$fieldMap[$code] => 'This value is already in use'];

            if ($code === 'invalid_password' || $code === 'missing_password_hash') {
                $errors = ['password' => 'Password could not be processed'];
            }

            throw new ValidationException($errors);
        }

        if ($code === 'user_not_found' || $code === 'created_user_not_found') {
            throw new NotFoundException('User');
        }

        throw new ConflictException($fallbackMessage . ' (' . $code . ')');
    }

    // ============================================================
    // MODULES RESOURCE
    // ============================================================

    /**
     * GET /api/v1/modules
     * List installed modules
     */
    public function GET_modules(): void
    {
        $this->requireAuth();
        $this->requirePermission('modules.view');
        
        $p = $this->getPagination();
        
        $where = [];
        $params = [];
        
        // Only filter on columns that tbl_modules actually has. The schema is
        // id, module_id, module_authors, module_releasedate, module_version,
        // module_path, isAdmin, isVisible, hasAdminPage, isContextAware,
        // dependsContext, puid. There is no module_name and no module_category,
        // so those names produced an MDB2 "no such field" failure at prepare()
        // and every filtered request returned a misleading 409.
        if ($search = $this->input('search')) {
            $where[] = '(module_id LIKE ? OR module_authors LIKE ? OR module_path LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($this->input('is_visible') !== null) {
            $where[] = 'isVisible = ?';
            $params[] = $this->input('is_visible') ? 1 : 0;
        }

        // tbl_modules has no category column, so this filter cannot be served.
        // Say so plainly rather than surfacing it as a database conflict.
        if ($this->input('category') !== null) {
            throw new ValidationException([
                'category' => 'Modules are not categorised; this filter is not supported'
            ]);
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Count
        $countSql = "SELECT COUNT(*) as cnt FROM tbl_modules $whereSql";
        // MDB2 needs the bound types declared: an undeclared placeholder makes
        // execute() look up options['datatype_map'][null] and fail.
        $stmt = $this->getDb()->prepare(
            $countSql,
            array_fill(0, max(count($params), 1), 'text')
        );
        if (PEAR::isError($stmt)) {
            error_log("GET_modules count prepare error: " . $stmt->getMessage());
            throw new ConflictException('Database error');
        }
        $result = $stmt->execute($params);
        if (PEAR::isError($result)) {
            error_log("GET_modules count execute error: " . $result->getMessage());
            throw new ConflictException('Database error');
        }
        $totalRow = $result->fetchRow();
        $total = (int)($totalRow['cnt'] ?? 0);

// List
        $sql = "
            SELECT id as module_id, module_id as module_key, module_version, module_path, 
                   isVisible as module_isvisible, hasAdminPage as module_hasadminpage, 
                   '' as module_description
            FROM tbl_modules
            $whereSql
            ORDER BY module_id
            LIMIT ? OFFSET ?
        ";
        $params[] = $p['pageSize'];
        $params[] = $p['offset'];

        $stmt = $this->getDb()->prepare($sql, array_merge(
            array_fill(0, count($params) - 2, 'text'),
            ['integer', 'integer']
        ));
        if (PEAR::isError($stmt)) {
            error_log("GET_modules list prepare error: " . $stmt->getMessage());
            throw new ConflictException('Database error');
        }
        $result = $stmt->execute($params);
        if (PEAR::isError($result)) {
            error_log("GET_modules list execute error: " . $result->getMessage());
            throw new ConflictException('Database error');
        }
        
        $modules = [];
        while ($row = $result->fetchRow()) {
            $modules[] = $row;
        }

        $this->respondPaginated($modules, $p['page'], $p['pageSize'], $total);
    }

    /**
     * GET /api/v1/modules/{id}
     * Get module details
     */
    public function GET_modules_by_id($id): void
    {
        $this->requireAuth();
        $this->requirePermission('modules.view');
        
        $module = $this->getObject('modules', 'modulecatalogue');
        $record = $module->getRow('module_id', $id);
        
        if (!$record || !isset($record['module_id'])) {
            throw new NotFoundException('Module');
        }

        $this->respond($record);
    }

    /**
     * POST /api/v1/modules/{id}/install
     * Install a module
     */
    public function POST_modules_by_id_install($id): void
    {
        $this->requireAuth();
        $this->requirePermission('modules.install');

        $registerdata = $this->loadModuleRegisterFile($id);

        // Reject an install the catalogue would treat as a no-op rather than
        // silently reporting success for a module that is already registered.
        $modules = $this->getObject('modules', 'modulecatalogue');
        if ($modules->checkIfRegistered($id)) {
            throw new ConflictException('Module is already installed: ' . $id);
        }

        $moduleAdmin = $this->getObject('modulesadmin', 'modulecatalogue');
        if (!$moduleAdmin->installModule($registerdata)) {
            throw new ConflictException(
                'Installation failed for module ' . $id,
                ['catalogueError' => $moduleAdmin->getLastError()]
            );
        }

        $this->respondCreated(['message' => 'Module installed successfully', 'module_id' => $id]);
    }

    /**
     * POST /api/v1/modules/{id}/uninstall
     * Uninstall a module
     */
    public function POST_modules_by_id_uninstall($id): void
    {
        $this->requireAuth();
        $this->requirePermission('modules.uninstall');

        $registerdata = $this->loadModuleRegisterFile($id);

        $mandatoryPolicy = $this->getObject('mandatorymodulepolicy', 'modulecatalogue');
        if ($mandatoryPolicy->isMandatory($id)) {
            throw new ConflictException('Mandatory core module cannot be uninstalled: ' . $id);
        }

        $moduleAdmin = $this->getObject('modulesadmin', 'modulecatalogue');
        if (!$moduleAdmin->uninstallModule($id, $registerdata)) {
            throw new ConflictException(
                'Uninstallation failed for module ' . $id,
                ['catalogueError' => $moduleAdmin->getLastError()]
            );
        }

        $this->respond(['message' => 'Module uninstalled successfully', 'module_id' => $id]);
    }

    /**
     * Load and validate a module's register.conf through the module catalogue.
     *
     * Both installModule() and uninstallModule() consume the parsed register
     * data rather than a module id, so the lookup belongs here.
     *
     * @param string $id The module id.
     * @return array The parsed register data.
     * @throws NotFoundException If the module has no readable register file.
     * @throws ConflictException If the register file cannot be parsed.
     */
    private function loadModuleRegisterFile($id): array
    {
        $moduleFile = $this->getObject('modulefile', 'modulecatalogue');
        $filepath = $moduleFile->findRegisterFile($id);

        if (!$filepath) {
            throw new NotFoundException('Module not found: ' . $id);
        }

        $registerdata = $moduleFile->readRegisterFile($filepath);

        if (!is_array($registerdata) || !isset($registerdata['MODULE_ID'])) {
            throw new ConflictException('Invalid register file for module: ' . $id);
        }

        return $registerdata;
    }

    // ============================================================
    // FILES RESOURCE (using filemanager module)
    // ============================================================

    /**
     * The file-type policies the filemanager understands.
     *
     * fileapi::listUserFiles() and uploadUserFile() take a policy name and
     * reject anything else with an 'unknown_policy' error, so the value is
     * validated here to turn that into an actionable validation failure
     * instead of a conflict. The names mirror fileapi::filePolicy().
     */
    private const FILE_POLICIES = array('image', 'audio', 'video', 'pdf', 'assignment', 'zip');

    /**
     * Resolve and validate the requested file policy.
     *
     * @param string $field The field to report errors against
     * @return string A policy name fileapi will accept
     * @throws ValidationException When the policy is absent or unsupported
     */
    private function requireFilePolicy(string $field = 'policy'): string
    {
        $policy = strtolower(trim((string) $this->input($field)));

        if ($policy === '') {
            throw new ValidationException([
                $field => 'A file policy is required. Supported: '
                    . implode(', ', self::FILE_POLICIES),
            ]);
        }

        if (!in_array($policy, self::FILE_POLICIES, true)) {
            throw new ValidationException([
                $field => 'Unsupported file policy. Supported: '
                    . implode(', ', self::FILE_POLICIES),
            ]);
        }

        return $policy;
    }

    /**
     * GET /api/v1/files
     * List the authenticated user's files of one type.
     *
     * Ownership comes from the caller's authenticated session, never from a
     * submitted user id: fileapi resolves the folder against the session, so
     * passing a user id here would both break the signature and let a client
     * choose whose files to list.
     *
     * fileapi has no paginated or searchable listing, so the returned
     * collection is sliced here in the same way the users resource does.
     */
    public function GET_files(): void
    {
        $this->requireAuth();
        $this->requirePermission('files.view');

        $policy = $this->requireFilePolicy();

        $fileApi = $this->getObject('fileapi', 'filemanager');

        $result = $fileApi->listUserFiles(
            $policy,
            $this->input('folder_id'),
            (string) $this->input('location', '')
        );

        if (empty($result['ok'])) {
            throw new ConflictException(
                'Failed to list files',
                array('fileApiCode' => $this->fileApiErrorCode($result))
            );
        }

        // fileapi returns its payload flat, with 'files' and 'folders' as
        // siblings of 'folder', not nested under a 'data' key.
        $files = isset($result['files']) && is_array($result['files'])
            ? $result['files']
            : array();
        $folders = isset($result['folders']) && is_array($result['folders'])
            ? $result['folders']
            : array();

        $p = $this->getPagination();
        $total = count($files);

        $this->respondPaginated(
            array(
                'policy' => $policy,
                'files' => array_slice($files, $p['offset'], $p['pageSize']),
                'folders' => $folders,
            ),
            $p['page'],
            $p['pageSize'],
            $total
        );
    }

    /**
     * Extract the machine-readable error code from a fileapi result.
     *
     * fileapi reports a specific reason in error['code'] (picker_root_not_found,
     * invalid_mimetype, upload_forbidden and so on). Its error['message'] comes
     * from the language service, and an incomplete or misconfigured language pack
     * turns that into an unhelpful error_languageitemmissing string, which
     * previously hid the real cause behind a generic "Failed to list files". The
     * code is stable and needs no translation, so it is passed through to the
     * client alongside the human-readable message.
     *
     * @param array $result The fileapi return value
     * @return string The error code, or 'unknown' when fileapi omitted it
     */
    private function fileApiErrorCode(array $result): string
    {
        if (isset($result['error']['code']) && is_string($result['error']['code'])) {
            return $result['error']['code'];
        }

        return 'unknown';
    }

    /**
     * POST /api/v1/files
     * Upload a file into the authenticated user's folder.
     *
     * fileapi reads the upload from $_FILES itself under the given input name
     * and enforces the policy's extension and mimetype allowlist, so the
     * policy must be supplied rather than inferred.
     */
    public function POST_files(): void
    {
        $this->requireAuth();
        $this->requirePermission('files.upload');

        $policy = $this->requireFilePolicy();

        $fileApi = $this->getObject('fileapi', 'filemanager');

        $result = $fileApi->uploadUserFile(
            $policy,
            $this->input('folder_id'),
            'file',
            (string) $this->input('location', '')
        );

        if (empty($result['ok'])) {
            throw new ConflictException(
                'Upload failed',
                array('fileApiCode' => $this->fileApiErrorCode($result))
            );
        }

        // fileapi returns its payload flat, so the whole result is the
        // response body minus the boolean envelope flag.
        unset($result['ok']);
        $this->respondCreated($result);
    }
}