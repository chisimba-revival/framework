<?php
/**
 * ApiResponse - Centralized JSON response handler for REST API
 * 
 * Provides consistent response format, proper HTTP headers, and status codes
 * across all API endpoints.
 */
class ApiResponse
{
    /**
     * Send a successful response
     * 
     * @param mixed $data Response data (array, object, or null)
     * @param int $status HTTP status code (200, 201, 204)
     * @param array $meta Additional metadata
     */
    public static function success($data, int $status = 200, array $meta = []): void
    {
        // 204 No Content should not have a response body
        if ($status === 204) {
            http_response_code(204);
            header('Cache-Control: no-store, private');
            header('X-Content-Type-Options: nosniff');
            exit;
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');
        
        $response = [
            'data' => $data,
            'meta' => array_merge(['timestamp' => gmdate('c')], $meta)
        ];
        
        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /**
     * Send a paginated list response
     * 
     * @param array $data List of items
     * @param int $page Current page (1-based)
     * @param int $pageSize Items per page
     * @param int $total Total number of items
     * @param array $meta Additional metadata
     */
    public static function paginated(array $data, int $page, int $pageSize, int $total, array $meta = []): void
    {
        self::success($data, 200, array_merge([
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => (int)ceil($total / $pageSize)
            ]
        ], $meta));
    }

    /**
     * Send an error response
     * 
     * @param string $code Machine-readable error code
     * @param string $message Human-readable error message
     * @param int $status HTTP status code
     * @param array $details Additional error details (field errors, etc.)
     */
    public static function error(string $code, string $message, int $status = 400, array $details = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');
        
        $response = [
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details
            ],
            'meta' => ['timestamp' => gmdate('c')]
        ];
        
        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /** Validation error (422) */
    public static function validationError(array $fieldErrors): void
    {
        self::error('VALIDATION_ERROR', 'Invalid input', 422, $fieldErrors);
    }

    /** Not found (404) */
    public static function notFound(string $resource = 'Resource'): void
    {
        self::error('NOT_FOUND', "$resource not found", 404);
    }

    /** Unauthorized (401) */
    public static function unauthorized(string $message = 'Authentication required'): void
    {
        self::error('UNAUTHORIZED', $message, 401);
    }

    /** Forbidden (403) */
    public static function forbidden(string $message = 'Access denied'): void
    {
        self::error('FORBIDDEN', $message, 403);
    }

    /** Conflict (409) */
    public static function conflict(string $message, array $details = []): void
    {
        self::error('CONFLICT', $message, 409, $details);
    }

    /** Rate limited (429) */
    public static function rateLimited(int $retryAfter = 60): void
    {
        http_response_code(429);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');
        header("Retry-After: $retryAfter");
        
        echo json_encode([
            'error' => [
                'code' => 'RATE_LIMITED',
                'message' => 'Too many requests',
                'details' => ['retryAfter' => $retryAfter]
            ],
            'meta' => ['timestamp' => gmdate('c')]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /** Internal server error (500) */
    public static function serverError(string $message = 'Internal server error'): void
    {
        self::error('INTERNAL_ERROR', $message, 500);
    }

    /** Method not allowed (405) */
    public static function methodNotAllowed(array $allowedMethods = []): void
    {
        $message = 'Method not allowed';
        if ($allowedMethods) {
            $message .= '. Allowed: ' . implode(', ', $allowedMethods);
        }
        http_response_code(405);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');
        header('Allow: ' . implode(', ', $allowedMethods));
        
        echo json_encode([
            'error' => [
                'code' => 'METHOD_NOT_ALLOWED',
                'message' => $message,
                'details' => ['allowedMethods' => $allowedMethods]
            ],
            'meta' => ['timestamp' => gmdate('c')]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}