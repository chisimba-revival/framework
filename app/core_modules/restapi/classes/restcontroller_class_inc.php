<?php
/**
 * RestController - Base controller for REST API endpoints
 * 
 * Provides shared functionality: authentication, pagination, validation,
 * request parsing, and standardized responses via ApiResponse.
 */
require_once dirname(__DIR__, 3) . '/classes/core/apiresponse_class_inc.php';

abstract class RestController extends controller
{
    /**
     * Canonical permission application that owns this module's rights.
     */
    const PERMISSION_APPLICATION = 'chisimba';

    /**
     * Canonical permission area name. Module registration seeds one area per
     * MODULE_ID under the 'chisimba' application.
     */
    const PERMISSION_AREA = 'restapi';

    /**
     * The complete API-key scope vocabulary. Any scope string not in this list
     * is rejected at key-creation time, and scope checks only ever look for a
     * member of this list, so a scope outside it can never grant a route.
     *
     * Bearer access tokens are not scoped — they carry no scope claim, and
     * adding one would be a field-service contract change. Enforcement here
     * constrains long-lived API keys only.
     */
    const API_SCOPES = [
        'profile:read',
        'users:read',
        'users:write',
        'modules:read',
        'modules:write',
        'files:read',
        'files:write',
        'catalogue:read',
        'sync:write',
        'keys:manage',
    ];

    protected TokenAuthService $auth;
    protected \SigningKeyService $keys;
    protected array $currentUser = [];
    protected string $requestMethod;
    protected array $requestData = [];
    protected array $routeParams = [];

    /**
     * Credential type that authenticated the current request: 'jwt', 'api_key',
     * or null when nothing has authenticated yet. Bearer access tokens carry no
     * scope claim, so they are not scope-constrained; API keys are.
     */
    protected ?string $credentialType = null;

    /**
     * Scopes held by an API-key credential, or null for a bearer token (which
     * is not scope-constrained) or before authentication.
     */
    protected ?array $credentialScopes = null;

    /**
     * Database connection, assigned in the constructor for handlers that need
     * it. Declared here rather than in a subclass because the constructor that
     * assigns it lives here.
     */
    protected $db;

    public function __construct($engine, $moduleName)
    {
        parent::__construct($engine, $moduleName);
        $this->auth = $this->getObject('tokenauthservice', 'security');
        $this->keys = $this->getObject('signingkeyservice', 'security');
        $this->db = $this->getDb();
        $this->requestMethod = $this->getRequestMethod();
        $this->parseRequestData();
    }

    /**
     * Main dispatch method - routes to appropriate handler based on REST conventions
     * 
     * Expected route format: /resource/id/subresource
     * Maps to methods like: GET_users, POST_users, GET_users_by_id, GET_users_by_id_posts
     */
    public function dispatch($action)
    {
        // For REST API, the action contains the full route path
        $route = $action ?? '';
        $this->routeParams = $this->parseRoute($route);
        
        $handler = $this->resolveHandler();

        if (!$handler || !method_exists($this, $handler)) {
            // The route shape may exist under a different method. Reporting
            // that as 404 hides a real endpoint behind a wrong status and
            // withholds the Allow header a client needs to correct itself, so
            // probe the other methods before falling back to 404.
            $allowed = $this->allowedMethodsForRoute();
            if (!empty($allowed)) {
                ApiResponse::methodNotAllowed($allowed);
            }
            ApiResponse::error('NOT_FOUND', 'Endpoint not found', 404);
        }

        try {
            if (str_ends_with($handler, '_by_id') && isset($this->routeParams['id'])) {
                return $this->$handler($this->routeParams['id']);
            }
            if (!empty($this->routeParams['sub']) && isset($this->routeParams['id'])) {
                return $this->$handler($this->routeParams['id']);
            }
            return $this->$handler();
        } catch (ValidationException $e) {
            ApiResponse::validationError($e->getErrors());
        } catch (AuthenticationException $e) {
            ApiResponse::unauthorized($e->getMessage());
        } catch (AuthorizationException $e) {
            ApiResponse::forbidden($e->getMessage());
        } catch (ScopeException $e) {
            $details = [];
            if ($e->getRequiredScope() !== null) {
                $details['requiredScope'] = $e->getRequiredScope();
            }
            ApiResponse::error('SCOPE_MISSING', $e->getMessage(), 403, $details);
        } catch (NotFoundException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (ConflictException $e) {
            ApiResponse::conflict($e->getMessage(), $e->getDetails());
        } catch (RateLimitException $e) {
            ApiResponse::rateLimited($e->getRetryAfter());
        } catch (\Throwable $e) {
            error_log("REST API request failed: " . get_class($e));
            ApiResponse::serverError();
        }
    }

    /**
     * Parse route into components
     * 
     * /users -> ['resource' => 'users', 'id' => null, 'sub' => null]
     * /users/123 -> ['resource' => 'users', 'id' => '123', 'sub' => null]
     * /users/admin -> ['resource' => 'users', 'id' => 'admin', 'sub' => null]
     * /users/123/posts -> ['resource' => 'users', 'id' => '123', 'sub' => 'posts']
     * /users/admin/posts -> ['resource' => 'users', 'id' => 'admin', 'sub' => 'posts']
     */
    protected function parseRoute(string $route): array
    {
        $route = trim($route, '/');
        if (!$route) {
            return ['resource' => '', 'id' => null, 'sub' => null];
        }

        $parts = explode('/', $route);
        $resource = array_shift($parts);
        $id = null;
        $sub = null;

        if ($parts) {
            $id = array_shift($parts);
            // If there are more parts after the ID, the ID is the second part
            // and the next part is the sub-resource (action)
            if ($parts) {
                $sub = array_shift($parts);
            }
        }

        // Reject routes with more than 3 segments (resource/id/sub) — do not silently drop extras
        if (!empty($parts)) {
            ApiResponse::error('NOT_FOUND', 'Endpoint not found', 404);
        }

        return [
            'resource' => $resource,
            'id' => $id,
            'sub' => $sub
        ];
    }

    /**
     * Resolve handler method name from route and HTTP method
     *
     * @param string|null $method HTTP method to resolve, or null to use the request method
     */
    protected function resolveHandler(?string $method = null): ?string
    {
        $resource = $this->routeParams['resource'];
        $id = $this->routeParams['id'];
        $sub = $this->routeParams['sub'];
        $method = strtoupper($method ?? $this->requestMethod);

        if (!$resource) return null;

        // Public site branding is a fixed endpoint, never a supplied resource ID.
        if ($resource === 'site') {
            $handler = strtolower($method) . '_site_branding';
            return $id === 'branding' && $sub === null && method_exists($this, $handler)
                ? $handler : null;
        }

        // The text-post collection is a fixed route, not a client-selected ID.
        if ($resource === 'simpleblog') {
            $handler = strtolower($method) . '_simpleblog_' . $id;
            return in_array($id, ['posts', 'categories'], true) && $sub === null && method_exists($this, $handler)
                ? $handler : null;
        }

        if ($resource === 'messaging') {
            $handler = strtolower($method) . '_messaging_' . $id;
            return in_array($id, ['contexts','history','messages','read','announcements','sightings','sightingimage'], true) && $sub === null && method_exists($this, $handler) ? $handler : null;
        }

        // Special handling for auth resource - actions like login, refresh, logout, me
        // are direct actions, not resource IDs
        if ($resource === 'auth' && $id) {
            $handler = strtolower($method) . '_' . $resource . '_' . $id;
            if ($sub) {
                $handler .= '_' . $sub;
            }
            return method_exists($this, $handler) ? $handler : null;
        }

        // Special handling for user resource - sub-actions like profile
        if ($resource === 'user' && $id && !$sub) {
            // For routes like /user/profile, the "id" is actually a sub-action
            $handler = strtolower($method) . '_' . $resource . '_' . $id;
            return method_exists($this, $handler) ? $handler : null;
        }

        // Special handling for sync resource - /sync/push and /sync/pull are
        // actions on the sync endpoint, not identifiers of a sync record.
        // Resolving them through the generic convention below would ask for
        // POST_sync_by_id('push'), which names a record called "push" and does
        // not exist. Same reasoning and same shape as the auth branch above.
        if ($resource === 'sync' && $id) {
            $handler = strtolower($method) . '_' . $resource . '_' . $id;
            if ($sub) {
                $handler .= '_' . $sub;
            }
            return method_exists($this, $handler) ? $handler : null;
        }

        // Build handler name: METHOD_resource[_by_id][_sub]
        $handler = strtolower($method) . '_' . $resource;
        
        if ($id) {
            $handler .= '_by_id';
        }
        
        if ($sub) {
            $handler .= '_' . $sub;
        }
        
        return method_exists($this, $handler) ? $handler : null;
    }

    /**
     * Get HTTP request method
     */
    protected function getRequestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Collect the HTTP methods this route supports, for an Allow header on a
     * 405 response. Probes each common method through the same resolution the
     * live method uses, so the header can never advertise a method the router
     * would not actually dispatch.
     *
     * @return string[]
     */
    protected function allowedMethodsForRoute(): array
    {
        $allowed = [];
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $handler = $this->resolveHandler($method);
            if ($handler && method_exists($this, $handler)) {
                $allowed[] = $method;
            }
        }
        return $allowed;
    }

    /**
     * Parse request body based on Content-Type
     */
    protected function parseRequestData(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (str_starts_with($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            $this->requestData = is_array($decoded) ? $decoded : [];
        } elseif (str_starts_with($contentType, 'application/x-www-form-urlencoded')) {
            $this->requestData = $_POST;
        } elseif (str_starts_with($contentType, 'multipart/form-data')) {
            $this->requestData = $_POST;
            // Files are in $_FILES
        } else {
            $this->requestData = $_POST;
        }

        // Merge query params for GET/DELETE
        if (in_array($this->requestMethod, ['GET', 'DELETE', 'HEAD', 'OPTIONS'])) {
            $this->requestData = array_merge($this->requestData, $_GET);
        }
    }

    /**
     * Get parsed request data
     */
    protected function getRequestData(): array
    {
        return $this->requestData;
    }

    /**
     * Get a specific request field with default
     */
    protected function input(string $key, $default = null)
    {
        return $this->requestData[$key] ?? $default;
    }

    /**
     * Require authentication - populates $this->currentUser
     */
    protected function requireAuth(): void
    {
        $headers = $this->getRequestHeaders();
        $credential = $this->auth->validateFromRequestDetailed($headers);

        if (!$credential) {
            throw new AuthenticationException('Authentication required');
        }

        $userId = $credential['user_id'];
        $this->credentialType = $credential['type'];
        $this->credentialScopes = $credential['scopes'];

        $userService = $this->getObject('userservice', 'security');
        $record = $userService->findByUserId($userId);

        if (!is_array($record) || !isset($record['userid'])) {
            throw new AuthenticationException('User not found');
        }

        // A signed token is not permission to keep using a closed account.
        // validateJWT() checks the signature, the expiry, the issuer, the
        // audience and the type, but nothing in the token says whether the
        // account behind it is still open, and the refresh path already
        // refuses a deactivated account (POST_auth_refresh) — so without this
        // the access token outlived the decision to deactivate, for up to the
        // whole access-token lifetime, and a 30-day remember-me token for a
        // month.
        //
        // The rule is the canonical one, the same as
        // TokenAuthService::accountState(): only '1' is active. A record with
        // no isactive column at all is reported inactive by this API
        // everywhere else (userToArrayFromRecord() defaults it to 0), so a
        // missing value must not read as active here either — an absent
        // column is an unreadable state, and an unreadable state fails closed.
        if (!isset($record['isactive']) || (int) $record['isactive'] !== 1) {
            throw new AuthenticationException(
                'This account is not active, so its token cannot be used.'
            );
        }

        $this->currentUser = $this->userToArrayFromRecord($record);
        $this->primeSessionUser($record);

        // An API key is only allowed onto routes its scopes name. This is
        // enforced here, at the single point where the credential and the
        // route are both known, rather than scattered across handlers: every
        // authenticated route calls requireAuth(), so this is the choke point
        // that cannot be forgotten. A bearer access token carries no scope
        // claim and is not constrained here.
        if ($this->credentialType === 'api_key') {
            $this->enforceApiKeyScope();
        }
    }

    /**
     * Check the presented API key's scopes against the scope the current route
     * requires. Fails closed for anything not explicitly allowed.
     */
    protected function enforceApiKeyScope(): void
    {
        $required = $this->scopeForRoute(
            $this->routeParams['resource'] ?? '',
            $this->routeParams['id'] ?? null,
            $this->routeParams['sub'] ?? null,
            strtoupper($this->requestMethod)
        );

        // Unmapped route: no scope was defined for it, so no API key is
        // allowed onto it. Adding a route without deciding its scope must not
        // silently inherit every existing key.
        if ($required === '') {
            throw new ScopeException(
                'This route cannot be reached with an API key.',
                null
            );
        }

        // Public route: no scope required.
        if ($required === null) {
            return;
        }

        $scopes = $this->credentialScopes ?? [];
        if (!in_array($required, $scopes, true)) {
            throw new ScopeException(
                'This API key does not hold the scope this route requires.',
                $required
            );
        }
    }

    /**
     * Map a route to the API-key scope it requires.
     *
     * @return string|null Scope name; null for a public route (no scope
     *                     needed); '' (empty string) for an authenticated route
     *                     with no declared scope, which denies every API key.
     */
    protected function scopeForRoute(string $resource, $id, $sub, string $method): ?string
    {
        // Public routes need no scope. These never reach requireAuth() in
        // practice, but naming them keeps the map total.
        if ($resource === 'auth' && ($id === 'token' || $id === 'jwks')) {
            return null;
        }

        // Profile reads.
        if ($resource === 'user' && $id === 'profile') {
            return 'profile:read';
        }
        if ($resource === 'auth' && $id === 'me') {
            return 'profile:read';
        }

        // API-key management.
        if ($resource === 'auth' && $id === 'api-keys') {
            return 'keys:manage';
        }

        // User records.
        if ($resource === 'users') {
            if ($method === 'GET') {
                return 'users:read';
            }
            if (in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
                return 'users:write';
            }
        }

        // Module catalogue.
        if ($resource === 'modules') {
            if ($method === 'GET') {
                return 'modules:read';
            }
            if (in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
                return 'modules:write';
            }
        }

        // Files.
        if ($resource === 'files') {
            if ($method === 'GET') {
                return 'files:read';
            }
            if (in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
                return 'files:write';
            }
        }

        // Field-guide catalogue reads.
        if (in_array($resource, ['species', 'outings', 'competencies'], true)) {
            return 'catalogue:read';
        }

        // Offline sync.
        if ($resource === 'sync') {
            return 'sync:write';
        }

        // Authenticated route with no declared scope (e.g. auth/logout,
        // auth/refresh — bearer-oriented). Deny every API key rather than
        // guess.
        return '';
    }

    /**
     * Make the session-scoped canonical services able to identify the caller.
     *
     * The canonical file services (FileApi -> pickerLocations() ->
     * resolvePickerFolder()) resolve ownership from the PHP session rather than
     * from the bearer token, so without this they see no signed-in user and
     * refuse to locate the caller's own storage. Prime the session from the
     * identity that has *already* been validated above, so the caller never
     * has to supply anything: no user id is read from the request, the query
     * string or a form field, which would make ownership client-controlled.
     *
     * The session is not an independent credential. It is derived from the
     * token on every request, and AuthController ties its lifetime to the
     * token's: logout destroys it and refresh rotates its identifier.
     */
    protected function primeSessionUser(array $record): void
    {
        $userId = isset($record['userid']) ? trim((string) $record['userid']) : '';

        if ($userId === '') {
            return;
        }

        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();

        // Only establish when the session has not already been populated for
        // this same identity. Re-establishing on every request would rotate the
        // session identifier continuously, defeating the rotation contract.
        if ($stack['sessions']->isAuthenticated()
            && (string) $stack['sessions']->getUserId() === $userId) {
            return;
        }

        $established = $stack['sessions']->establish($userId, array(
            'username' => isset($record['username']) ? $record['username'] : '',
            'provider' => 'restapi',
        ));

        if (!$established) {
            throw new RuntimeException(
                'Authenticated session could not be established for the '
                . 'canonical file services.'
            );
        }
    }

    /**
     * Convert a canonical user row to the API's internal user shape.
     *
     * `role` is intentionally absent: role data belongs to the permission
     * identity and group services, not to tbl_users.accesslevel.
     */
    protected function userToArrayFromRecord(array $record): array
    {
        return [
            'id' => $record['userid'] ?? '',
            'username' => $record['username'] ?? '',
            'email' => $record['emailaddress'] ?? '',
            'fullname' => trim(
                ($record['firstname'] ?? '') . ' ' . ($record['surname'] ?? '')
            ),
            'is_active' => isset($record['isactive']) ? (int) $record['isactive'] : 0
        ];
    }

    

    /**
     * Check if current user has a specific permission.
     *
     * Authorisation is delegated to the canonical permission services: the right
     * is resolved within this module's own permission area and evaluated by
     * PermissionService::isGranted(), which honours direct grants and group
     * inheritance. There is deliberately no `accesslevel` shortcut and no local
     * SQL: an unknown area, an undefined right, an unknown user or a query
     * failure all deny.
     */
    protected function can(string $permission): bool
    {
        if (!$this->currentUser || !isset($this->currentUser['id'])) {
            return false;
        }

        $userId = (string) $this->currentUser['id'];
        if ($userId === '') {
            return false;
        }

        try {
            $permService = $this->getObject('permissionservice', 'security');

            $areaId = $permService->areaIdForName(
                self::PERMISSION_APPLICATION,
                self::PERMISSION_AREA
            );

            if ($areaId === null) {
                return false;
            }

            $rightId = $permService->rightIdForArea($areaId, $permission);

            if ($rightId === null) {
                return false;
            }

            return (bool) $permService->isGranted($userId, $rightId);
        } catch (Exception $e) {
            error_log('REST API permission check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Require specific permission
     */
    protected function requirePermission(string $permission): void
    {
        if (!$this->can($permission)) {
            throw new AuthorizationException("Permission denied: $permission");
        }
    }

    /**
     * Get pagination parameters from request
     */
    protected function getPagination(): array
    {
        $page = max(1, (int)($this->input('page', 1)));
        $pageSize = min(100, max(1, (int)($this->input('pageSize', 20))));
        
        return [
            'page' => $page,
            'pageSize' => $pageSize,
            'offset' => ($page - 1) * $pageSize
        ];
    }

    /**
     * Validate request data against rules
     * 
     * Rules format: [
     *   'field' => 'required|type:constraint|...',
     *   'email' => 'required|email',
     *   'age' => 'sometimes|integer|min:13|max:150'
     * ]
     *
     * Uniqueness is NOT checked here. Each canonical service owns its own
     * availability rule (for example UserService::usernameAvailable()), so
     * handlers must ask the owning service rather than issuing table queries
     * from the validator.
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $data[$field] ?? null;
            $isRequired = in_array('required', $fieldRules);
            $isSometimes = in_array('sometimes', $fieldRules);

            // Skip if not present and not required
            if ($value === null && !$isRequired && !$isSometimes) {
                continue;
            }

            // Required but missing
            if ($isRequired && ($value === null || $value === '')) {
                $errors[$field][] = "$field is required";
                continue;
            }

            // Skip validation for optional empty fields
            if (!$isRequired && $isSometimes && ($value === null || $value === '')) {
                continue;
            }

            foreach ($fieldRules as $rule) {
                if (in_array($rule, ['required', 'sometimes'])) continue;

                if (str_starts_with($rule, 'type:')) {
                    $type = substr($rule, 5);
                    if (!$this->validateType($value, $type)) {
                        $errors[$field][] = "$field must be of type $type";
                    }
                } elseif (str_starts_with($rule, 'min:')) {
                    $min = (int)substr($rule, 4);
                    if (is_numeric($value) && $value < $min) {
                        $errors[$field][] = "$field must be at least $min";
                    } elseif (is_string($value) && strlen($value) < $min) {
                        $errors[$field][] = "$field must be at least $min characters";
                    }
                } elseif (str_starts_with($rule, 'max:')) {
                    $max = (int)substr($rule, 4);
                    if (is_numeric($value) && $value > $max) {
                        $errors[$field][] = "$field must not exceed $max";
                    } elseif (is_string($value) && strlen($value) > $max) {
                        $errors[$field][] = "$field must not exceed $max characters";
                    }
                } elseif ($rule === 'email') {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = "$field must be a valid email";
                    }
                } elseif ($rule === 'integer' || $rule === 'int') {
                    if (!filter_var($value, FILTER_VALIDATE_INT)) {
                        $errors[$field][] = "$field must be an integer";
                    }
                } elseif ($rule === 'numeric') {
                    if (!is_numeric($value)) {
                        $errors[$field][] = "$field must be numeric";
                    }
                } elseif ($rule === 'string') {
                    if (!is_string($value)) {
                        $errors[$field][] = "$field must be a string";
                    }
                } elseif ($rule === 'boolean' || $rule === 'bool') {
                    if (!is_bool($value) && !in_array($value, ['true', 'false', '1', '0', 1, 0], true)) {
                        $errors[$field][] = "$field must be a boolean";
                    }
                } elseif ($rule === 'array') {
                    if (!is_array($value)) {
                        $errors[$field][] = "$field must be an array";
                    }
                } elseif (str_starts_with($rule, 'in:')) {
                    $allowed = explode(',', substr($rule, 3));
                    if (!in_array($value, $allowed, true)) {
                        $errors[$field][] = "$field must be one of: " . implode(', ', $allowed);
                    }
                }
            }

            if (!isset($errors[$field])) {
                $validated[$field] = $value;
            }
        }

        if ($errors) {
            throw new ValidationException($errors);
        }

        return $validated;
    }

    /**
     * Validate a value against a type
     */
    private function validateType($value, string $type): bool
    {
        switch ($type) {
            case 'string': return is_string($value);
            case 'int': case 'integer': return filter_var($value, FILTER_VALIDATE_INT) !== false;
            case 'float': case 'double': return is_numeric($value);
            case 'bool': case 'boolean': return is_bool($value) || in_array($value, ['true', 'false', '1', '0', 1, 0], true);
            case 'array': return is_array($value);
            case 'email': return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'url': return filter_var($value, FILTER_VALIDATE_URL) !== false;
            case 'uuid': return $this->isUuid($value);
            default: return true;
        }
    }

    /**
     * Get request headers as array
     */
    protected function getRequestHeaders(): array
    {
        // Use getallheaders() which works with Apache mod_php
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            return $headers;
        }
        
        // Fallback to $_SERVER
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(substr($key, 5))] = $value;
            }
        }
        // Also check for Authorization header specifically
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
        }
        return $headers;
    }

    /**
     * Check if string is UUID
     */
    protected function isUuid(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }

    /**
     * Send success response (convenience method)
     */
    protected function respond($data, int $status = 200, array $meta = []): void
    {
        ApiResponse::success($data, $status, $meta);
    }

    /**
     * Send paginated response (convenience method)
     */
    protected function respondPaginated(array $data, int $page, int $pageSize, int $total, array $meta = []): void
    {
        ApiResponse::paginated($data, $page, $pageSize, $total, $meta);
    }

    /**
     * Send created response (201)
     */
    protected function respondCreated($data, array $meta = []): void
    {
        ApiResponse::success($data, 201, $meta);
    }

    /**
     * Send no content response (204)
     */
    protected function respondNoContent(): void
    {
        ApiResponse::success(null, 204);
    }

    /**
     * Get the catalogue repository through the shared dbTable boundary
     */
    protected function getDb()
    {
        return $this->getObject('modules', 'modulecatalogue');
    }
}

/**
 * Custom exceptions for API error handling
 */
class ValidationException extends Exception
{
    private array $errors;
    
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('Validation failed');
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
}

class AuthenticationException extends Exception {}
class AuthorizationException extends Exception {}
class NotFoundException extends Exception {}
class ConflictException extends Exception
{
    private array $details = [];
    
    public function __construct(string $message, array $details = [])
    {
        $this->details = $details;
        parent::__construct($message);
    }
    
    public function getDetails(): array
    {
        return $this->details;
    }
}

class RateLimitException extends Exception
{
    private int $retryAfter;

    public function __construct(string $message, int $retryAfter = 60)
    {
        $this->retryAfter = $retryAfter;
        parent::__construct($message);
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}

/**
 * The presented API key does not hold the scope the route requires.
 *
 * Distinct from AuthorizationException (a permission/ownership refusal) so
 * clients can tell "your key lacks this scope" from "you are not allowed to
 * touch this record". $requiredScope is null when the route itself is not
 * reachable by any API key, and a scope name when the route is reachable but
 * this key does not carry it.
 */
class ScopeException extends Exception
{
    private ?string $requiredScope;

    public function __construct(string $message, ?string $requiredScope)
    {
        $this->requiredScope = $requiredScope;
        parent::__construct($message);
    }

    public function getRequiredScope(): ?string
    {
        return $this->requiredScope;
    }
}
