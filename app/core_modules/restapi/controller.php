<?php
/**
 * REST API Module Controller
 *
 * Handles all /api/v1/* routes with RESTful conventions
 */
require_once __DIR__ . '/classes/restcontroller_class_inc.php';

class restapi extends RestController
{
    public function dispatch($action)
    {
        // The action from engine will be the full route path
        // e.g., "users/123/posts" for GET /api/v1/users/123/posts
        return parent::dispatch($action);
    }

    /**
     * POST /api/v1/simpleblog/posts: plain text in the caller's personal blog.
     * The API translates input; SimpleBlog retains all publishing decisions.
     * @author Derek Keats
     */
    public function POST_simpleblog_posts(): void
    {
        $this->requireAuth();
        if (!$this->getObject('modules', 'modulecatalogue')->checkIfRegistered('simpleblog')) {
            throw new NotFoundException('SimpleBlog is not installed');
        }
        $owner = (string) $this->currentUser['id'];
        $policy = $this->getObject('publishingpolicy', 'simpleblog');
        if (!$policy->canCreate('personal', $owner)) {
            throw new AuthorizationException('Personal publishing permission is required');
        }
        require_once __DIR__ . '/classes/textpostinput_class_inc.php';
        try {
            $input = TextPostInput::normalise(
                $this->getRequestData(),
                (string) ($_SERVER['CONTENT_TYPE'] ?? ''),
                !empty($_FILES)
            );
            $id = $this->getObject('publishingservice', 'simpleblog')->save(
                null, 'personal', $owner, $input
            );
        } catch (DomainException $error) {
            if ($error->getMessage() === 'forbidden') {
                throw new AuthorizationException('Personal publishing permission is required');
            }
            throw new ValidationException(['post' => 'Provide a title, plain-text content and draft or posted status; media and other fields are not supported.']);
        }
        $this->respondCreated(['id' => $id, 'status' => $input['status']]);
    }

    // ============================================================
    // AUTHENTICATION ENDPOINTS
    // ============================================================

    /**
     * GET /api/v1/auth/token
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
     *
     * Contract note: The field guiding service contract specifies this as
     * POST /api/v1/auth/token for the actual credential exchange. The GET
     * endpoint here provides the abuse evidence required before that exchange.
     */
    public function GET_auth_token(): void
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
     * POST /api/v1/auth/token
     * Exchange username/password for access + refresh tokens.
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
     * are required: they come from GET /api/v1/auth/token. The optional
     * honeypot field `website` must be absent or empty.
     *
     * Contract: This is the token exchange endpoint specified in the field
     * guiding service contract. On success an access token and a refresh token
     * are returned. When policy requires MFA, a challenge is returned instead
     * of a token, and there is no route that converts a challenge directly
     * into a token without satisfying it.
     */
public function POST_auth_token(): void
    {
        $data = $this->validate($this->getRequestData(), [
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
            'csrf_token' => 'required|string|max:255',
            'abuse_issued_at' => 'required|integer|min:1',
            'abuse_nonce' => 'required|string|max:64',
            'abuse_signature' => 'required|string|max:128',
            'website' => 'sometimes|string|max:255',
            'device_id' => 'sometimes|string|max:255'
        ]);



        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();

        $metadata = [
            'ip' => $this->getClientIp(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT'])
                ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255)
                : '',
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
                        . 'Request it again with GET /api/v1/auth/token and post '
                        . 'after the advertised minimum_seconds has elapsed.'
                ]);
            }

            if ($reason === 'honeypot') {
                throw new ValidationException([
                    'website' => 'This field must be left empty.'
                ]);
            }

            if ($reason === 'session_expired') {
                // The evidence token did not match this session, so the password
                // was never checked. That is not a rejected credential: it is a
                // recoverable proof-of-session failure the client can retry by
                // requesting fresh evidence. Kept distinct from the 401 so a
                // person is never told their password was wrong when it was not.
                throw new ValidationException([
                    'session_expired' => 'The sign-in session no longer matches '
                        . 'the evidence token, so the password was not checked. '
                        . 'Request fresh evidence with GET /api/v1/auth/token and '
                        . 'post it again keeping the session cookie.'
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

        // Include device_id if provided for family revocation on replay
        $deviceId = $this->input('device_id');
        if ($deviceId) {
            $options['device_id'] = $deviceId;
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
     * Exchange a refresh token and device identifier for a new token pair.
     *
     * The refresh token is rotated, and the session identifier is rotated with
     * it. The session must not outlive the credential that created it, so
     * retiring the old refresh token retires the old session identifier too;
     * the identity is preserved because the same account is still signed in.
     * Without this, a cookie captured under the previous token pair would
     * remain usable after that pair was revoked.
     *
     * Accepts a device identifier to enable whole-family revocation on replay.
     * Presenting an already-rotated refresh token revokes the whole family for
     * that user and device, on the assumption that reuse means the token was
     * captured.
     *
     * Contract: This is the refresh endpoint specified in the field guiding
     * service contract. It accepts a refresh token and a device identifier,
     * and returns a new access token. Refresh tokens rotate on every use.
     */
    public function POST_auth_refresh(): void
    {
        $data = $this->validate($this->getRequestData(), [
            'refresh_token' => 'required|string',
            'device_id' => 'required|string|max:255'
        ]);

        $tokens = $this->auth->refreshAccessToken($data['refresh_token'], $data['device_id']);

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
     *
     * Supports device-specific logout via device_id parameter.
     */
    public function POST_auth_logout(): void
    {
        $this->requireAuth();

        $data = $this->validate($this->getRequestData(), [
            'refresh_token' => 'sometimes|string',
            'revoke_all' => 'sometimes|boolean',
            'device_id' => 'sometimes|string|max:255'
        ]);

        if (!empty($data['revoke_all'])) {
            $this->auth->revokeAllUserTokens($this->currentUser['id']);
        } elseif (!empty($data['refresh_token'])) {
            $refreshHash = hash('sha256', $data['refresh_token']);
            $this->auth->revokeRefreshToken($refreshHash);
        } elseif (!empty($data['device_id'])) {
            // Device-specific logout
            $this->auth->revokeAllUserDeviceTokens($this->currentUser['id'], $data['device_id']);
        }

        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();

        if (!$stack['sessions']->destroy()) {
            throw new RuntimeException('Session could not be destroyed');
        }

        $this->respond(['message' => 'Logged out successfully']);
    }

    /**
     * GET /api/v1/auth/jwks
     * The public signing key, for services that verify tokens locally.
     *
     * Deliberately unauthenticated, and that is not an oversight. A verifier
     * that had to present a token in order to fetch the key could never
     * validate its first token, so requiring one here would make local
     * validation impossible rather than safer. The document contains public
     * values only — modulus and exponent — so there is nothing here to protect.
     *
     * The contract names /.well-known/jwks.json as the discovery path. This
     * router is rooted at /api/v1 and cannot serve a path at the site root, so
     * the document is served here first and the well-known path is an alias
     * in front of it. The alias has to exist before any third party is told to
     * depend on this URL, because a published path that later moves breaks
     * every verifier that cached it.
     *
     * @return void
     */
    public function GET_auth_jwks(): void
    {
        // Not $this->respond(), and deliberately.
        //
        // A JWKS is a standard document, not an application payload: RFC 7517
        // defines it as an object whose "keys" member is at the top level, and
        // every verifier written to that specification looks for it there.
        // Wrapping it in this API's {data, meta} envelope produced a 200 with
        // valid JSON that no conforming verifier could read — the field service
        // rejected it with "publishes no usable RS256 key", and it did so
        // without saying why, because from its side the document was simply
        // empty.
        //
        // That is the shape of failure this whole feature exists to prevent: a
        // status that says the thing worked, a body that is well-formed, and a
        // consumer that cannot proceed. An envelope is right for a resource and
        // wrong for a document whose meaning is defined by its own schema, so
        // the document is published as the document.
        //
        // The envelope stays everywhere else. Changing this one response is not a
        // precedent for changing the others; it is the one response that is not
        // describing an application resource.
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: public, max-age=300');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($this->keys->jwks(), JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * GET /api/v1/auth/me
     * Get current authenticated user info
     *
     * @deprecated Use GET /api/v1/user/profile instead. This endpoint is kept
     * for backward compatibility but will be removed in a future version.
     */
    public function GET_auth_me(): void
    {
        $this->requireAuth();
        $this->respond($this->currentUser);
    }

    /**
     * GET /api/v1/auth/login
     * Issue the abuse-protection evidence a login submission must carry.
     *
     * @deprecated Use GET /api/v1/auth/token instead. This endpoint is kept
     * for backward compatibility but will be removed in a future version.
     */
    public function GET_auth_login(): void
    {
        $this->GET_auth_token();
    }

    /**
     * POST /api/v1/auth/login
     * Login with username/password, returns access + refresh tokens.
     *
     * @deprecated Use POST /api/v1/auth/token instead. This endpoint is kept
     * for backward compatibility but will be removed in a future version.
     */
    public function POST_auth_login(): void
    {
        $this->POST_auth_token();
    }

    /**
     * GET /api/v1/user/profile
     * Get current authenticated user profile with context, grants, roles and epoch.
     *
     * Returns the caller's identity, active context, context grant set, roles,
     * effective scopes and identity epoch. It is the one call the mobile client
     * makes on every successful start, and the response is cacheable for the
     * access token's lifetime.
     *
     * The grant set is included because the client needs it to decide what the
     * user can see and which context to default to. It is present for display
     * and navigation; it does not authorise anything, because the token it
     * arrives with already did, and a client-supplied context is never trusted
     * server-side.
     *
     * Contract: This is the profile endpoint specified in the field guiding
     * service contract.
     */
    public function GET_user_profile(): void
    {
        $this->requireAuth();

        $userId = $this->currentUser['id'];

        // Get identity claims (includes active context, grant set, roles, epoch, scopes)
        $claims = $this->auth->identityClaims($userId);

        // Get user details
        $userService = $this->getObject('userservice', 'security');
        $userRow = $userService->findByUserId($userId);

        if (!is_array($userRow)) {
            throw new NotFoundException('User not found');
        }

        $profile = [
            'user' => $this->presentUser($userRow),
            'active_context' => $claims['ctx'],
            'context_grants' => $claims['ctxs'],
            'roles' => $claims['roles'],
            'scopes' => $claims['scope'],
            'identity_epoch' => $claims['epoch'],
            'account_active' => $claims['active']
        ];

        // Set cache headers for the access token lifetime (1 hour)
        header('Cache-Control: private, max-age=3600');

        $this->respond($profile);
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

        $scopes = $data['scopes'] ?? [];

        // Only names from the fixed vocabulary may be stored. A scope outside
        // it can never grant a route (scopeForRoute() only ever names a
        // member), so accepting one would be misleading rather than dangerous,
        // but a caller that believes it holds a scope it does not is a bug
        // waiting to surface later.
        foreach ($scopes as $scope) {
            if (!is_string($scope) || !in_array($scope, self::API_SCOPES, true)) {
                throw new ValidationException([
                    'scopes' => 'Unknown scope. Allowed: ' . implode(', ', self::API_SCOPES)
                ]);
            }
        }

        // A key minted under an API-key credential cannot exceed the scopes
        // that credential holds. Without this, any key holder could mint a
        // wider key for themselves and the scope list becomes advisory.
        if ($this->credentialType === 'api_key') {
            $held = $this->credentialScopes ?? [];
            $excess = array_diff($scopes, $held);
            if (!empty($excess)) {
                throw new ScopeException(
                    'An API key cannot mint a key with scopes it does not itself hold.',
                    null
                );
            }
        }

        $result = $this->auth->createApiKey(
            $this->currentUser['id'],
            $data['name'],
            $scopes,
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

            // If the user changed their own password, all other refresh tokens
            // are invalidated. The access token used for this call may still be
            // valid for its remaining lifetime, but a client that wishes to
            // continue must re-authenticate or rotate with a fresh login. If an
            // administrator changed another account's password, other sessions
            // should also be ended — a forced password change should revoke all
            // refresh sessions for the affected account.
            try {
                $authService->revokeAllUserTokens((string) $id);
            } catch (\Throwable $t) {
                // Best effort: we already updated the password. The rest is
                // accounting for active sessions; do not let it abort a
                // successful password change.
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
        $totalRow = $this->getDb()->selectPrepared($countSql, $params)[0] ?? [];
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

        $modules = $this->getDb()->selectPrepared($sql, $params);

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

    // ============================================================
    // SPECIES CATALOGUE ENDPOINTS
    //
    // The catalogue is the field guiding service's, and it stays there: species
    // are reference data that belongs beside the field data they describe, and
    // a device bundles them into its offline cache through one request. These
    // routes are a proxy over that service so a caller needs a single address
    // and a single token. The request carries the caller's own credentials
    // forward, and the field service validates them itself, so this proxy
    // gates anonymity and nothing more.
    // ============================================================

    /**
     * GET /api/v1/species
     * List the species catalogue, or search it.
     *
     * Search is passed through rather than pre-filtered here. The catalogue
     * searches case-insensitively across code, common name, scientific name
     * and description, and filtering twice would only drop what the upstream
     * would have returned.
     */
    public function GET_species(): void
    {
        $this->requireAuth();

        $this->respond($this->catalogueList('/api/v1/species', 'species'));
    }

    /**
     * GET /api/v1/species/{code}
     * One species from the catalogue, by code.
     *
     * The engine lowercases the whole route before the handler sees it, so the
     * code arrives here in whatever casing the client sent, lowercased. It is
     * forwarded untouched: the catalogue uppercases and trims it itself, so any
     * casing works and a code the catalogue does not hold is still an answer.
     *
     * An unknown code is the catalogue's own 404, passed through. Answering
     * "not found" for a code the caller asked about reveals nothing beyond
     * what the caller already had.
     */
    public function GET_species_by_id($id): void
    {
        $this->requireAuth();

        $code = trim((string) $id);
        if ($code === '' || strpos($code, '/') !== false) {
            throw new NotFoundException('Species');
        }

        $this->respond(
            $this->fetchFromFieldService('/api/v1/species/' . rawurlencode($code))
        );
    }

    /**
     * GET /api/v1/outings
     * List the outings the calling user may read.
     *
     * Nothing is searched or narrowed here beyond what the caller is entitled
     * to see: the upstream lists the outings in the caller's own context grants
     * and omits the rest, and it answers the same for an outing that does not
     * exist as for one in a context the caller may not read. Forwarding the
     * token is what carries that entitlement, so the list is the caller's own.
     */
    public function GET_outings(): void
    {
        $this->requireAuth();

        $this->respond($this->catalogueList('/api/v1/outings', 'outings'));
    }

    /**
     * GET /api/v1/competencies
     * List the competency catalogue.
     *
     * Same shape and same reasoning as the species list. Competencies are the
     * other reference list a field card needs: the behaviour and age/sex
     * options the device offers are derived from them, and a card that cannot
     * read them falls back to built-in defaults, so this is worth serving.
     */
    public function GET_competencies(): void
    {
        $this->requireAuth();

        $this->respond($this->catalogueList('/api/v1/competencies', 'competencies'));
    }

    /**
     * POST /api/v1/sync/push
     * Hand a batch of queued operations to the field service.
     *
     * The body is forwarded exactly as it arrived rather than being decoded and
     * re-encoded. The field service decodes strictly and refuses unknown
     * fields, so anything read into PHP and written back out risks dropping or
     * renaming a key on the way — and a dropped key is a change applied
     * incompletely, which is the one outcome worse than a refusal. Re-encoding
     * also loses nothing a caller sent and gains nothing a caller did not.
     *
     * The answer is passed through as it came. The device reads either a bare
     * body or one wrapped under "data", and the field service answers bare
     * with the per-operation results, so wrapping is left to respond() and the
     * results are not reshaped here.
     */
    public function POST_sync_push(): void
    {
        $this->requireAuth();

        $this->respond(
            $this->fetchFromFieldService(
                '/api/v1/sync/push',
                $this->rawRequestBody()
            )
        );
    }

    /**
     * POST /api/v1/sync/pull
     * Ask the field service for the next page of changes.
     *
     * A POST rather than a GET, because the request carries a cursor in its
     * body and a cursor is not something to put in a query string where a
     * proxy or an access log would keep it. An empty body is a first pull,
     * which the field service treats as ordinary rather than as an error.
     */
    public function POST_sync_pull(): void
    {
        $this->requireAuth();

        $this->respond(
            $this->fetchFromFieldService(
                '/api/v1/sync/pull',
                $this->rawRequestBody()
            )
        );
    }

    /**
     * The request body exactly as the client sent it.
     *
     * Read here rather than taken from getRequestData(), which has already
     * been through json_decode: a proxy needs the bytes, not a PHP array, and
     * `[]` and `{}` are both truthy-empty in ways a forwarded body is not.
     */
    private function rawRequestBody(): string
    {
        $raw = file_get_contents('php://input');

        return is_string($raw) ? $raw : '';
    }

    /**
     * Fetch one catalogue list from the field service and hand it back under
     * the key the caller reads, wrapped in Chisimba's own envelope.
     *
     * The three catalogue lists do not agree on a shape: species and
     * competencies answer bare ({"species": [...]}) while outings nests its
     * payload one level deeper under "data". Letting that reach a client
     * unchanged would put the list somewhere the caller is not looking, and a
     * client reading the documented key would see an empty list and report no
     * outings at all rather than a malformed response — a silent wrong answer,
     * which is the failure mode worth designing against. So the payload is
     * lifted out first and wrapped once, the same way, for all three.
     *
     * The result is always an array and never null. A device has to tell "the
     * catalogue is empty" from "this response was not the shape I expected",
     * and a null where a list belongs is the second of those.
     *
     * @param string $path Path on the field service, '/api/v1/...' with no
     *                     query string
     * @param string $key  The key the caller reads the list from
     * @return array A one-key array holding the list
     */
    private function catalogueList(string $path, string $key): array
    {
        // Every catalogue list in the contract takes the same search
        // parameter. Each list is free to ignore it, and a parameter the
        // upstream does not consult is harmless, while one it starts honouring
        // later needs no change here.
        $search = trim((string) $this->input('q', ''));
        if ($search !== '') {
            $path .= '?q=' . urlencode($search);
        }

        $upstream = $this->fetchFromFieldService($path);

        $payload = isset($upstream['data']) && is_array($upstream['data'])
            ? $upstream['data']
            : $upstream;

        return [$key => $payload[$key] ?? []];
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

    /**
     * Where the field guiding service is, as this installation reaches it.
     *
     * Environment first, then the installation's configuration file, then the
     * compose default — the same order and for the same reasons as
     * SigningKeyService resolving its key path. The environment is read first
     * because the field service address is the one setting an installation is
     * most likely to want to change, and an environment variable can only be
     * changed by recreating the container. Neither is a fallback for the
     * other: a URL that is right in one and wrong in the other answers from
     * a different catalogue than the one intended, which reads as stale data
     * rather than as a misconfiguration.
     *
     * @return string Base URL with any trailing slash removed
     */
    private function fieldServiceUrl(): string
    {
        $fromEnvironment = getenv('CHISIMBA_FIELD_SERVICE_URL');
        if (is_string($fromEnvironment) && trim($fromEnvironment) !== '') {
            return rtrim(trim($fromEnvironment), '/');
        }

        // The config file is at <docroot>/config/restapi_inc.php, and how far
        // up that is depends on where this installation rooted the framework.
        // Two layouts are both real: the source tree, where the module is
        // app/core_modules/<module> and the docroot is three levels out, and
        // the assembled image, where app/ IS the docroot and it is two. Rather
        // than guess an installation's layout, walk up until a readable
        // config file is found. A silently unwritable-looking-for path is the
        // failure this avoids: the operator's setting would be ignored and the
        // default would answer instead, which looks like the setting works.
        for ($levels = 2; $levels <= 4; $levels++) {
            $candidate = dirname(__DIR__, $levels);

            $file = $candidate . '/config/restapi_inc.php';
            if (!is_readable($file)) {
                continue;
            }

            require_once $file;
            if (defined('KEWL_FIELD_SERVICE_URL') && is_string(KEWL_FIELD_SERVICE_URL)
                && trim(KEWL_FIELD_SERVICE_URL) !== '') {
                return rtrim(trim(KEWL_FIELD_SERVICE_URL), '/');
            }

            // The file was found and read, so a missing or blank constant is a
            // decision rather than an oversight. Keep looking no further.
            break;
        }

        // The compose default. The service listens on 8080 inside its own
        // container and shares the chisimba-dev network under this name.
        return 'http://field-service:8080';
    }

    /**
     * Ask the field guiding service for a path, as the calling user.
     *
     * Reads a path or sends one a body, depending on whether $body is given.
     * Both exist for the same reason: the reference catalogues are read-only and
     * a device needs them to fill its pickers, while sync carries a device's
     * queued work and cannot be served by a proxy that only reads.
     *
     * The caller's own Authorization header is forwarded verbatim. The field
     * service signs nothing here and trusts nothing here: its guard validates
     * the token itself, against its own cached JWKS, and refuses a token whose
     * epoch has been raised or whose scope is missing. So this proxy answers
     * for the same person who asked, with no service credential minted and no
     * broader access granted than the caller already had. What this method
     * contributes is identity: the handlers require a valid Chisimba session
     * before they get here, so no anonymous request reaches the field service.
     *
     * Every failure answers as JSON. A refused connection, a timeout, an
     * upstream error page and a body that is not the expected shape have all
     * previously surfaced as a blank page or a PHP warning, which a device
     * cannot tell apart from "there is no network" — and an empty species
     * picker is what a misread failure looks like when the catalogue is
     * reference data. So each mode is distinguished:
     *
     *   transport failure      503 UPSTREAM_UNAVAILABLE  nothing answered
     *   upstream's own error   its status, its own code  it refused
     *   body that is not JSON  502 UPSTREAM_UNREADABLE   answered wrongly
     *
     * Details of the fault (URL, errno, raw body) go to the log, never to the
     * client.
     *
     * @param string $path Path on the field service, '/api/v1/...' with any
     *                     query string already attached
     * @param string|null $body The request body to send, or null to send none.
     *                     A null body is a GET; a string body is a POST. The
     *                     field service's sync endpoints take their batch and
     *                     their cursor in the body, so a proxy that could only
     *                     read would leave exactly the write path unreachable.
     * @return array The decoded upstream body
     */
    private function fetchFromFieldService(string $path, ?string $body = null): array
    {
        if (!function_exists('curl_init')) {
            error_log('REST API: field service unreachable, the cURL extension is absent');
            ApiResponse::error(
                'UPSTREAM_UNAVAILABLE',
                'The field service is unavailable.',
                503
            );
        }

        // Two headers are taken from the caller and the rest are this proxy's
        // own. Each is the first of its name, so a second Authorization sent by
        // a caller cannot quietly displace the one that was honoured.
        $authorization = null;
        $contentType = null;

        // getallheaders() keeps the casing the client sent and the $_SERVER
        // fallback lowercases, so the name is folded either way.
        foreach ($this->getRequestHeaders() as $name => $value) {
            if (!is_string($value) || $value === '') {
                continue;
            }
            $folded = strtolower((string) $name);

            if ($authorization === null && $folded === 'authorization') {
                $authorization = $value;
            } elseif ($contentType === null && $folded === 'content-type') {
                $contentType = $value;
            }
        }

        $requestHeaders = ['Accept: application/json'];
        if ($authorization !== null) {
            $requestHeaders[] = 'Authorization: ' . $authorization;
        }

        $options = [
            CURLOPT_URL => $this->fieldServiceUrl() . $path,
            CURLOPT_RETURNTRANSFER => true,
            // A redirect is not followed: a 3xx would only re-ask for
            // something already answered, and following one on a write is a
            // way to send the same batch twice. It is reported like any other
            // answer the proxy did not expect.
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_HTTPHEADER => $requestHeaders,
        ];

        if ($body !== null) {
            // The field service refuses a body whose Content-Type it does not
            // recognise as JSON, so the caller's own value goes with the
            // caller's own bytes. Reading it from the request rather than
            // assuming the common case keeps a client that names a charset or
            // a vendor suffix from being answered 415 for a well-formed body.
            $requestHeaders[] = 'Content-Type: ' . ($contentType ?? 'application/json');
            $options[CURLOPT_HTTPHEADER] = $requestHeaders;
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $options);

        $body = curl_exec($ch);

        if ($body === false) {
            $errno = curl_errno($ch);
            $message = curl_error($ch);
            curl_close($ch);

            error_log("REST API: field service unreachable ({$errno}): {$message}");
            ApiResponse::error(
                'UPSTREAM_UNAVAILABLE',
                'The field service is unavailable.',
                503
            );
        }

        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $decoded = json_decode((string) $body, true);

        if (!is_array($decoded)) {
            error_log(
                'REST API: field service answered ' . $status
                . ' with a body that is not JSON'
            );
            ApiResponse::error(
                'UPSTREAM_UNREADABLE',
                'The field service did not answer as expected.',
                502
            );
        }

        if ($status < 200 || $status >= 300) {
            // The catalogue reports a stable code with a readable message, and
            // its own body is flat — {"error":"unknown_species","message":...} —
            // while Chisimba's envelope nests under error.code/error.message.
            // Both shapes are read, because the answer we owe the caller is
            // the catalogue's own reason rather than one of ours.
            $error = $decoded['error'] ?? null;

            $code = 'UPSTREAM_ERROR';
            if (is_string($error) && $error !== '') {
                $code = $error;
            } elseif (isset($error['code']) && is_string($error['code']) && $error['code'] !== '') {
                $code = $error['code'];
            }

            $message = 'The field service refused the request.';
            if (isset($decoded['message']) && is_string($decoded['message'])) {
                $message = $decoded['message'];
            } elseif (isset($error['message']) && is_string($error['message'])) {
                $message = $error['message'];
            }

            error_log("REST API: field service refused ({$status}): {$code}");
            ApiResponse::error($code, $message, $status);
        }

        return $decoded;
    }
}