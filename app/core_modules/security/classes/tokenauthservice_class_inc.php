<?php
/**
 * TokenAuthService - JWT access/refresh tokens + API keys for REST API
 * 
 * Provides stateless authentication compatible with existing session-based auth.
 * Supports:
 * - JWT access tokens (short-lived, for API requests)
 * - JWT refresh tokens (long-lived, for token renewal)
 * - API keys (long-lived, for server-to-server)
 */
class TokenAuthService
{
    const ACCESS_TOKEN_TTL = 3600;          // 1 hour
    const REFRESH_TOKEN_TTL = 86400 * 14;   // 14 days
    const API_KEY_PREFIX = 'chk_';          // Chisimba Key prefix
    const API_KEY_LENGTH = 32;              // 32 bytes = 64 hex chars

    private $db;
    private $secret;
    private $issuer = 'chisimba';
    private $audience = 'chisimba-api';

    public function __construct()
    {
        global $_globalObjDb;
        $this->db = $_globalObjDb;
        
        // Use a config-based secret or generate from site key
        $this->secret = $this->getSecret();
    }

    /**
     * Get or generate the JWT signing secret
     */
    private function getSecret(): string
    {
        return $this->resolveSecret('jwt_secret', 'CHISIMBA_JWT_SECRET');
    }

    /**
     * Resolve the site key from the environment or the module configuration.
     *
     * @return string The configured site key
     * @throws RuntimeException When no site key is configured
     */
    private function getSiteKey(): string
    {
        return $this->resolveSecret('site_key', 'CHISIMBA_SITE_KEY');
    }

    /**
     * Resolve a signing secret without ever falling back to a value in source.
     *
     * A signing secret committed to the repository is not a secret, and a
     * shared development default means every installation silently signs
     * tokens with a publicly known key. This class is a plain collaborator
     * rather than a ChisimbaObject, so it has no engine handle with which to
     * reach the module configuration, and inventing one would be worse than
     * being explicit. The secret is therefore taken from the environment and
     * nothing else: when it is absent the caller gets an exception rather than
     * a guess.
     *
     * @param string $configKey   The register.conf CONFIG entry name, used for
     *                            the error message only
     * @param string $environment The environment variable that supplies it
     * @return string The resolved secret
     * @throws RuntimeException When no secret is configured
     */
    private function resolveSecret(string $configKey, string $environment): string
    {
        $fromEnvironment = getenv($environment);

        if (is_string($fromEnvironment) && trim($fromEnvironment) !== '') {
            return $fromEnvironment;
        }

        throw new RuntimeException(
            'The REST API ' . $configKey . ' is not configured. Set the '
            . $environment . ' environment variable before starting the '
            . 'application; it is deliberately not defaulted in source.'
        );
    }

    /**
     * Execute a statement that uses ? placeholders.
     *
     * MDB2::query() does not bind values: it takes a complete SQL string only.
     * Passing a bindings array as the second argument is silently ignored, the
     * literal ? tokens reach the server, and the statement fails without the
     * caller noticing. Placeholders must go through prepare() and then
     * execute($bindings), and both steps have to be checked because prepare()
     * returns an MDB2_Error rather than throwing when a table is missing.
     *
     * @throws RuntimeException when the statement cannot be prepared or run
     */
    /**
     * Declare the bind types so MDB2 does not fall back to a null type.
     *
     * MDB2 records the types given to prepare() and looks each one up per
     * placeholder when binding. A placeholder with no recorded type resolves to
     * null, which reaches MDB2\Driver\Datatype::mapPrepareDatatype(null) and
     * indexes $options['datatype_map'][null], raising a PHP 8.1 deprecation on
     * every statement. Quoting already infers the type from the value, so
     * declaring it changes nothing except supplying the bind type character
     * MDB2 was otherwise leaving out.
     *
     * Only the names MDB2 recognises may be declared: declaring an unknown one
     * makes execute() fail with "not supported". Those are 'boolean',
     * 'integer', 'float' and 'blob'; every other value is bound as text, which
     * is what MDB2 would have used anyway once quoting inferred it.
     *
     * @param array $bindings The bound values, in placeholder order
     * @return array MDB2 type names, positionally matching $bindings
     */
    private function parameterTypes(array $bindings): array
    {
        $types = array();

        foreach ($bindings as $value) {
            if (is_int($value)) {
                $types[] = 'integer';
            } elseif (is_bool($value)) {
                $types[] = 'boolean';
            } elseif (is_float($value)) {
                $types[] = 'float';
            } else {
                $types[] = 'text';
            }
        }

        return $types;
    }

    private function executeStatement(string $sql, array $bindings = []): void
    {
        $statement = $this->db->prepare($sql, $this->parameterTypes($bindings));

        if (MDB2::isError($statement)) {
            throw new RuntimeException(
                'Token statement could not be prepared: ' . $statement->getMessage()
            );
        }

        $result = $statement->execute($bindings);

        if (MDB2::isError($result)) {
            $message = $result->getMessage();
            $statement->free();
            throw new RuntimeException('Token statement failed: ' . $message);
        }

        $statement->free();
    }

    /**
     * Ensure required database tables exist
     */
    /**
     * Generate a JWT token
     */
    private function generateJWT(string $userId, int $ttl, string $type = 'access'): string
    {
        $now = time();
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $now,
            'exp' => $now + $ttl,
            'sub' => (string)$userId,
            'type' => $type,
            'jti' => bin2hex(random_bytes(16))
        ];

        $encodedHeader = $this->base64UrlEncode(json_encode($header));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "$encodedHeader.$encodedPayload", $this->secret, true)
        );

        return "$encodedHeader.$encodedPayload.$signature";
    }

    /**
     * Validate and decode a JWT token
     * 
     * @return array|null [userId, payload] or null if invalid
     */
    public function validateJWT(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$encodedHeader, $encodedPayload, $signature] = $parts;
        
        // Verify signature
        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', "$encodedHeader.$encodedPayload", $this->secret, true)
        );
        
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        // Decode payload
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);
        if (!$payload) return null;

        // Check expiration. A token with no exp is rejected rather than
        // accepted: absence of the claim must not mean "never expires", or a
        // token minted without one would stay valid indefinitely.
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        // Check issuer and audience
        if (($payload['iss'] ?? '') !== $this->issuer) return null;
        if (($payload['aud'] ?? '') !== $this->audience) return null;

        // Check token type
        if (($payload['type'] ?? '') !== 'access') return null;

        return [(string)$payload['sub'], $payload];
    }

    /**
     * Validate an access token or API key presented in a request's headers.
     *
     * Only the standard Authorization: Bearer header and the X-API-Key header
     * are honoured, matched case-insensitively as HTTP requires. $request is
     * always the headers array as returned by getallheaders()/getRequestHeaders(),
     * never $_GET, so credentials are never read from the query string.
     * Accepting a token under some other header name is not supported: it is
     * credential smuggling, it sidesteps the header handling that proxies and
     * WAFs normalise, and a non-standard header carrying a bearer token is
     * still a secret that some access logs record.
     *
     * @param array $request Request headers
     * @return string|null The validated user id, or null when no usable credential was presented
     */
    public function validateFromRequest(array $request): ?string
    {
        // HTTP header names are case-insensitive (RFC 7230), but getallheaders()
        // preserves whatever casing the client sent, so looking up 'Authorization'
        // alone rejects a perfectly legal "AUTHORIZATION: Bearer ...". Fold the
        // keys once instead of guessing at variants. The CGI-style HTTP_ prefix
        // that getRequestHeaders()'s $_SERVER fallback produces is stripped too.
        $headers = array();
        foreach ($request as $name => $value) {
            $name = strtolower((string) $name);
            if (str_starts_with($name, 'http_')) {
                $name = substr($name, 5);
            }
            $headers[$name] = $value;
        }

        $auth = $headers['authorization'] ?? '';
        if (str_starts_with($auth, 'Bearer ')) {
            $token = substr($auth, 7);
            $result = $this->validateJWT($token);
            if ($result) return $result[0];
        }

        $apiKey = $headers['x-api-key'] ?? '';
        if ($apiKey) {
            return $this->validateApiKey($apiKey);
        }

        return null;
    }

    /**
     * Validate an API key and return user ID
     */
    public function validateApiKey(string $apiKey): ?string
    {
        if (!str_starts_with($apiKey, self::API_KEY_PREFIX)) {
            return null;
        }

        // Extract prefix for quick lookup
        $prefix = substr($apiKey, 0, 12);
        $keyHash = hash('sha256', $apiKey);

        $sql = "
            SELECT user_id, expires_at, revoked_at 
            FROM tbl_rest_api_keys 
            WHERE key_prefix = ? AND key_hash = ?
        ";
        $stmt = $this->db->prepare($sql, $this->parameterTypes(array($prefix, $keyHash)));
        if (MDB2::isError($stmt)) {
            // Without this, a failed prepare returns an MDB2_Error and the
            // call below fatals on it, turning a database problem into a 500
            // that is indistinguishable from a bad credential.
            throw new RuntimeException(
                'API key lookup could not be prepared: ' . $stmt->getMessage()
            );
        }
        $result = $stmt->execute([$prefix, $keyHash]);
        if (MDB2::isError($result)) {
            $stmt->free();
            throw new RuntimeException(
                'API key lookup failed: ' . $result->getMessage()
            );
        }
        $row = $result->fetchRow();
        $result->free();
        $stmt->free();

        if (!$row) return null;

        // Check expiration
        if ($row['expires_at'] && strtotime($row['expires_at']) < time()) {
            return null;
        }

        // Check revocation
        if ($row['revoked_at']) {
            return null;
        }

        // Update last_used_at
        $this->executeStatement("
            UPDATE tbl_rest_api_keys 
            SET last_used_at = NOW() 
            WHERE key_prefix = ? AND key_hash = ?
        ", [$prefix, $keyHash]);

        return (string)$row['user_id'];
    }

    /**
     * Issue access + refresh token pair for a user
     */
    public function issueTokens(string $userId, array $options = []): array
    {
        $accessToken = $this->generateJWT($userId, self::ACCESS_TOKEN_TTL, 'access');
        $refreshToken = $this->generateJWT($userId, self::REFRESH_TOKEN_TTL, 'refresh');
        
        // Store refresh token hash
        $refreshHash = hash('sha256', $refreshToken);
        $expiresAt = date('Y-m-d H:i:s', time() + self::REFRESH_TOKEN_TTL);
        $userAgent = $options['user_agent']
            ?? (isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 512) : '');
        $ipAddress = $options['ip_address']
            ?? (isset($_SERVER['REMOTE_ADDR']) ? substr((string) $_SERVER['REMOTE_ADDR'], 0, 45) : '');

        $this->executeStatement("
            INSERT INTO tbl_rest_refresh_tokens (user_id, token_hash, expires_at, user_agent, ip_address)
            VALUES (?, ?, ?, ?, ?)
        ", [$userId, $refreshHash, $expiresAt, $userAgent, $ipAddress]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_TTL
        ];
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        // Validate refresh token structure
        $parts = explode('.', $refreshToken);
        if (count($parts) !== 3) return null;

        $refreshHash = hash('sha256', $refreshToken);

        // Look up in database
        $sql = "
            SELECT user_id, expires_at, revoked_at 
            FROM tbl_rest_refresh_tokens 
            WHERE token_hash = ?
        ";
        $stmt = $this->db->prepare($sql, $this->parameterTypes(array($refreshHash)));
        if (MDB2::isError($stmt)) {
            throw new RuntimeException(
                'Refresh token lookup could not be prepared: ' . $stmt->getMessage()
            );
        }
        $result = $stmt->execute([$refreshHash]);
        if (MDB2::isError($result)) {
            $stmt->free();
            throw new RuntimeException(
                'Refresh token lookup failed: ' . $result->getMessage()
            );
        }
        $row = $result->fetchRow();
        $result->free();
        $stmt->free();

        if (!$row) return null;

        // Check expiration
        if (strtotime($row['expires_at']) < time()) {
            $this->revokeRefreshToken($refreshHash);
            return null;
        }

        // Check revocation
        if ($row['revoked_at']) return null;

        // Decode to verify type
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        if (!$payload || ($payload['type'] ?? '') !== 'refresh') {
            return null;
        }

        $userId = (string)$payload['sub'];
        if ($userId !== (string) $row['user_id']) return null;

        // Issue new token pair (rotate refresh token)
        $this->revokeRefreshToken($refreshHash);
        return $this->issueTokens($userId);
    }

    /**
     * Revoke a refresh token
     */
    public function revokeRefreshToken(string $refreshHash): void
    {
        $this->executeStatement("
            UPDATE tbl_rest_refresh_tokens 
            SET revoked_at = NOW() 
            WHERE token_hash = ?
        ", [$refreshHash]);
    }

    /**
     * Revoke all refresh tokens for a user
     */
    public function revokeAllUserTokens(string $userId): void
    {
        $this->executeStatement("
            UPDATE tbl_rest_refresh_tokens 
            SET revoked_at = NOW() 
            WHERE user_id = ? AND revoked_at IS NULL
        ", [$userId]);
    }

    /**
     * Create an API key for a user
     * 
     * @return array ['key' => full key (only shown once), 'id' => db id, 'prefix' => key prefix]
     */
    public function createApiKey(string $userId, string $name, array $scopes = [], ?string $expiresAt = null): array
    {
        $randomBytes = random_bytes(self::API_KEY_LENGTH);
        $key = self::API_KEY_PREFIX . bin2hex($randomBytes);
        $keyHash = hash('sha256', $key);
        $prefix = substr($key, 0, 12);

        $this->executeStatement("
            INSERT INTO tbl_rest_api_keys (user_id, name, key_hash, key_prefix, scopes, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [$userId, $name, $keyHash, $prefix, json_encode($scopes), $expiresAt]);

        $id = $this->db->lastInsertId();
        
        return [
            'key' => $key,  // Only returned once!
            'id' => $id,
            'prefix' => $prefix,
            'name' => $name,
            'scopes' => $scopes,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * List API keys for a user (without the actual key)
     */
    public function listApiKeys(string $userId): array
    {
        $sql = "
            SELECT id, name, key_prefix, scopes, expires_at, last_used_at, created_at, revoked_at
            FROM tbl_rest_api_keys
            WHERE user_id = ? AND revoked_at IS NULL
            ORDER BY created_at DESC
        ";
        $stmt = $this->db->prepare($sql, $this->parameterTypes(array($userId)));
        $result = $stmt->execute([$userId]);
        
        $keys = [];
        while ($row = $result->fetchRow()) {
            $keys[] = $row;
        }
        return $keys;
    }

    /**
     * Revoke an API key
     */
    public function revokeApiKey(string $userId, int $keyId): bool
    {
        $this->executeStatement("
            UPDATE tbl_rest_api_keys 
            SET revoked_at = NOW() 
            WHERE id = ? AND user_id = ?
        ", [$keyId, $userId]);
        
        return $this->db->affectedRows() > 0;
    }

    /**
     * Base64 URL-safe encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL-safe decode
     */
    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Get user object for authenticated user ID
     */
    public function getUser(string $userId)
    {
        global $default;
        $user = new user($default);
        $user->init();
        $user->getRow('id', $userId);
        return $user;
    }
}