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

    protected TokenAuthService $auth;
    protected array $currentUser = [];
    protected string $requestMethod;
    protected array $requestData = [];
    protected array $routeParams = [];

    public function __construct($engine, $moduleName)
    {
        parent::__construct($engine, $moduleName);
        $this->auth = $this->getObject('tokenauthservice', 'security');
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
        } catch (NotFoundException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (ConflictException $e) {
            ApiResponse::conflict($e->getMessage(), $e->getDetails());
        } catch (RateLimitException $e) {
            ApiResponse::rateLimited($e->getRetryAfter());
        } catch (Exception $e) {
            error_log("REST API Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
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

        return [
            'resource' => $resource,
            'id' => $id,
            'sub' => $sub
        ];
    }

    /**
     * Resolve handler method name from route and HTTP method
     */
    protected function resolveHandler(): ?string
    {
        $resource = $this->routeParams['resource'];
        $id = $this->routeParams['id'];
        $sub = $this->routeParams['sub'];
        $method = strtoupper($this->requestMethod);

        if (!$resource) return null;

        // Special handling for auth resource - actions like login, refresh, logout, me
        // are direct actions, not resource IDs
        if ($resource === 'auth' && $id) {
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
     * Parse request body based on Content-Type
     */
    protected function parseRequestData(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (str_starts_with($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $this->requestData = json_decode($raw, true) ?? [];
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
        $userId = $this->auth->validateFromRequest($headers);
        
        if (!$userId) {
            throw new AuthenticationException('Authentication required');
        }

        $userService = $this->getObject('userservice', 'security');
        $record = $userService->findByUserId($userId);

        if (!is_array($record) || !isset($record['userid'])) {
            throw new AuthenticationException('User not found');
        }

        $this->currentUser = $this->userToArrayFromRecord($record);
        $this->primeSessionUser($record);
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
     * Get database connection (MDB2 or PDO)
     */
    protected function getDb()
    {
        global $_globalObjDb;
        if ($_globalObjDb === null) {
            $_globalObjDb = $this->objEngine->getDbObj();
        }
        return $_globalObjDb;
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