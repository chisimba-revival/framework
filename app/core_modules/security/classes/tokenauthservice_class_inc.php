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

    /**
     * The most grants one token may carry.
     *
     * A cap rather than a convenience: the grant set is the read boundary, so an
     * unbounded one makes the cost of every request unbounded too. The number is
     * generous for a reserve, and the response to exceeding it is a refusal at
     * token issue rather than a shorter list — a silently truncated grant set has
     * quietly stopped describing the user.
     */
    const MAX_GRANTS = 32;

    private $db;
    private $secret;
    private $signingKeys;
    private $issuer = 'chisimba';
    private $audience = 'chisimba-api';

    public function __construct()
    {
        global $_globalObjDb;
        $this->db = $_globalObjDb;

        // Use a config-based secret or generate from site key
        $this->secret = $this->getSecret();

        // Tokens are signed with RSA rather than with the shared secret above.
        // The secret is still needed for API keys, which are verified here
        // against a stored hash rather than by a remote service, so it has not
        // gone away — it simply stopped being something every verifier needs.
        require_once dirname(__DIR__) . '/classes/signinkeyservice_class_inc.php';
        $this->signingKeys = new SigningKeyService();
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
        $tokenId = bin2hex(random_bytes(16));

        return $this->signingKeys->sign(array_merge([
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $now,
            'exp' => $now + $ttl,
            'sub' => (string)$userId,
            'type' => $type,
            'jti' => $tokenId,
            // The field service expects a token's own identity under 'ver', and
            // jti is that value under the name RFC 7519 gives it. Emitting both
            // costs one claim and saves every verifier from guessing which of
            // the two names it should be reading.
            'ver' => $tokenId,
        ], $this->identityClaims($userId)));
    }

    /**
     * The claims describing what a user may reach, and in which context.
     *
     * Group membership is the grant set. Chisimba already holds membership, it is
     * already populated, and inventing a second table to say the same thing would
     * be a second answer free to disagree with the first: a user who is in a
     * group and a user with a row in a new grants table would not be the same
     * person, and which one the field service honoured would depend on which
     * query ran.
     *
     * The consequence is that a group name IS a context code, so it becomes part
     * of the security boundary. That is a real cost of reusing existing data and
     * is recorded rather than hidden: 'Big 5 Reserve' and 'big5' are two
     * contexts, and an operator who renames a group silently removes an access
     * nobody notices until somebody cannot do their job.
     *
     * ctx is the active context — the one a new record is written into — and it
     * is deliberately left EMPTY when a user holds more than one grant. Reads
     * follow the grant set and are unaffected; writes are refused by the field
     * service, which is the correct outcome. Picking one of several silently
     * would put a trainee's sighting into whichever context happened to sort
     * first, which is exactly the quiet wrongness this system exists to prevent.
     * An operator who wants a user writing somewhere gives them one group.
     *
     * @param string $userId Chisimba logical user identifier
     * @return array
     */
    public function identityClaims(string $userId): array
    {
        $groups = $this->groupsFor($userId);

        return array(
            // Whether the account is switched on. It is in the claims because a
            // token must not outlive the account it was issued to, and because
            // nothing else in Chisimba changes when an account is deactivated.
            'active' => $this->accountState($userId) === 'active',
            // ctx and ctxs are separate on purpose. Using the active context as
            // the read boundary would hide a guide's northern-reserve drives
            // while they happen to be working in the southern one.
            'ctx' => count($groups) === 1 ? $groups[0] : '',
            'ctxs' => $groups,
            // Roles are group names. Nothing downstream interprets them yet, and
            // emitting names rather than inventing a guide/trainee/mentor mapping
            // means that mapping can be added when something needs it instead of
            // being guessed at now.
            'roles' => $groups,
            'epoch' => $this->epochFor($userId),
            'scope' => $groups,
        );
    }

    /**
     * Whether a user's account is currently switched on, as a state that can also
     * be 'unknown'.
     *
     * Three values rather than a bool, and that is the correction. This was a bool
     * returning true whenever the row could not be read, which is the right
     * default when the question is whether to *refuse a token* and the wrong one
     * when the question is whether the user's grants have *changed*: treating an
     * unreadable row as active made a deactivation invisible, because the
     * fingerprint came back identical and the epoch never moved.
     *
     * It stayed invisible rather than failing loudly because the column was wrong.
     * This reads isactive; there is no status column on tbl_users, so the query
     * errored, the error was swallowed by the fail-open, and the fingerprint said
     * 'active' for a deactivated user. A read that fails must be a state of its
     * own, so that it changes the fingerprint and the epoch moves.
     *
     * @param string $userId Chisimba logical user identifier
     * @return string One of 'active', 'inactive' or 'unknown'
     */
    private function accountState(string $userId): string
    {
        $statement = $this->db->prepare(
            'SELECT isactive FROM tbl_users WHERE userid = ?'
        );
        if (MDB2::isError($statement)) {
            return 'unknown';
        }
        $result = $statement->execute(array($userId));
        if (MDB2::isError($result)) {
            $statement->free();
            return 'unknown';
        }
        $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        $statement->free();

        if (!is_array($row) || !array_key_exists('isactive', $row)) {
            return 'unknown';
        }
        return (int) $row['isactive'] === 1 ? 'active' : 'inactive';
    }

    /**
     * Record a user's epoch and the grants it was recorded against.
     *
     * @param string $userId The Chisimba logical user identifier
     * @param int $epoch The epoch in force
     * @param string $fingerprint The grants this epoch was recorded against
     * @return void
     */
    private function storeEpoch(string $userId, int $epoch, string $fingerprint): void
    {
        $this->executeStatement(
            'INSERT INTO tbl_rest_identity_epoch'
            . ' (user_id, epoch, grants_fingerprint, updated_at)'
            . ' VALUES (?, ?, ?, NOW())'
            . ' ON DUPLICATE KEY UPDATE'
            . ' epoch = GREATEST(epoch, VALUES(epoch)),'
            . ' grants_fingerprint = VALUES(grants_fingerprint),'
            . ' updated_at = NOW()',
            array($userId, $epoch, $fingerprint)
        );
    }

    /**
     * The groups a user belongs to, as context codes.
     *
     * @param string $userId Chisimba logical user identifier
     * @return array
     */
    private function groupsFor(string $userId): array
    {
        $sql = 'SELECT DISTINCT g.group_define_name AS name'
             . ' FROM tbl_perms_groupusers gu'
             . ' JOIN tbl_perms_groups g ON g.puid = gu.group_id'
             . ' WHERE gu.perm_user_id = ?'
             . ' AND g.group_define_name IS NOT NULL'
             . ' AND g.group_define_name <> ""'
             . ' ORDER BY g.group_define_name ASC';

        $statement = $this->db->prepare($sql, array('text'));
        if (MDB2::isError($statement)) {
            throw new RuntimeException(
                'Grant lookup could not be prepared: ' . $statement->getMessage()
            );
        }

        // The join column holds the numeric primary key; the logical identifier
        // is what it should be matched on. Mixing the two is how a grant set ends
        // up describing the wrong person.
        $result = $statement->execute(array($this->numericIdOf($userId)));
        if (MDB2::isError($result)) {
            $message = $result->getMessage();
            $statement->free();
            throw new RuntimeException('Grant lookup failed: ' . $message);
        }

        $names = array();
        while ($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            $names[] = (string)$row['name'];
        }
        $result->free();
        $statement->free();

        if (count($names) > self::MAX_GRANTS) {
            throw new RuntimeException(
                'This user holds ' . count($names) . ' grants, more than the '
                . self::MAX_GRANTS . ' a token may carry. Reduce the number of '
                . 'groups, because a grant set that is silently truncated has '
                . 'quietly stopped describing the user.'
            );
        }

        return $names;
    }

    /**
     * Resolve a logical user identifier to the numeric key the join holds.
     *
     * A numeric identifier is used as it stands, which is the case in every
     * installation so far. Anything else is looked up rather than coerced:
     * casting 'abc' to int yields 0, and user 0 would inherit whoever's grants
     * hang off it.
     *
     * @param string $userId
     * @return int
     */
    private function numericIdOf(string $userId): int
    {
        if (ctype_digit($userId)) {
            return (int)$userId;
        }

        $statement = $this->db->prepare(
            'SELECT puid FROM tbl_users WHERE userid = ?',
            array('text')
        );
        if (MDB2::isError($statement)) {
            throw new RuntimeException(
                'User lookup could not be prepared: ' . $statement->getMessage()
            );
        }
        $result = $statement->execute(array($userId));
        if (MDB2::isError($result)) {
            $message = $result->getMessage();
            $statement->free();
            throw new RuntimeException('User lookup failed: ' . $message);
        }
        $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        $result->free();
        $statement->free();

        if (!$row || !isset($row['puid'])) {
            throw new RuntimeException(
                'No user matches the identifier this token was issued for.'
            );
        }
        return (int)$row['puid'];
    }

    /**
     * The epoch in force for a user, which is zero if none was ever recorded.
     *
     * Zero rather than absent is deliberate: a token minted before this table
     * existed still has to be readable, and it legitimately carries epoch 0.
     *
     * @param string $userId
     * @return int
     */
    public function epochFor(string $userId): int
    {
        $statement = $this->db->prepare(
            'SELECT epoch FROM tbl_rest_identity_epoch WHERE user_id = ?',
            array('text')
        );
        if (MDB2::isError($statement)) {
            throw new RuntimeException(
                'Epoch lookup could not be prepared: ' . $statement->getMessage()
            );
        }
        $result = $statement->execute(array($userId));
        if (MDB2::isError($result)) {
            $message = $result->getMessage();
            $statement->free();
            throw new RuntimeException('Epoch lookup failed: ' . $message);
        }
        $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        $result->free();
        $statement->free();

        return $row && isset($row['epoch']) ? (int)$row['epoch'] : 0;
    }

    /**
     * Raise a user's identity epoch.
     *
     * Called when a user's access changes: deactivated, reactivated, or moved
     * between groups with different reach. The epoch can only rise. An epoch that
     * fell would let every token already issued at the higher value become valid
     * again, which is the precise opposite of what an increment is for.
     *
     * @param string $userId
     * @return int The new epoch
     */
    public function raiseEpoch(string $userId): int
    {
        $this->executeStatement(
            'INSERT INTO tbl_rest_identity_epoch (user_id, epoch, updated_at)'
            . ' VALUES (?, 1, NOW())'
            . ' ON DUPLICATE KEY UPDATE epoch = GREATEST(epoch + 1, VALUES(epoch)),'
            . ' updated_at = NOW()',
            array($userId)
        );

        return $this->epochFor($userId);
    }

    /**
     * Raise a user's epoch if their grants no longer match what was recorded.
     *
     * This is what makes a role reduction visible without anything having to
     * remember to report one. The alternative — raising the epoch from the group
     * admin screens, from user deactivation, and from whatever else can change
     * a person's permissions — puts the REST API's revocation model inside every
     * module that can affect it, and any of those paths that somebody forgets is
     * a reduction that stays valid until the token expires on its own.
     *
     * Comparing a fingerprint catches all of them at once, because it does not
     * care how the change arrived.
     *
     * The first sighting of a user records their fingerprint without raising the
     * epoch. Recording is not a change: a token minted before this table existed
     * carries epoch 0 and must keep working, so a user seen for the first time
     * here is not treated as having had their permissions reduced.
     *
     * @param string $userId The Chisimba logical user identifier
     * @return int The epoch now in force for this user
     */
    public function syncEpochFor(string $userId): int
    {
        $fingerprint = $this->grantsFingerprintOf($userId);

        $statement = $this->db->prepare('SELECT epoch, grants_fingerprint
            FROM tbl_rest_identity_epoch WHERE user_id = ?');
        if (MDB2::isError($statement)) {
            return 0;
        }
        // execute() is checked before it is used. Chaining fetchRow() onto it
        // means calling a method on whatever came back, which for a failed
        // statement is an MDB2_Error and the result is "Call to undefined
        // method MDB2_Error::fetchRow()" instead of the query that actually
        // failed.
        $result = $statement->execute(array($userId));
        if (MDB2::isError($result)) {
            $statement->free();
            return 0;
        }
        $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        $statement->free();

        if (empty($row)) {
            $this->storeEpoch($userId, 0, $fingerprint);
            return 0;
        }

        $epoch = (int) $row['epoch'];
        if ($row['grants_fingerprint'] === $fingerprint) {
            return $epoch;
        }

        // Greatest, so a concurrent issue cannot lower the value another one
        // just raised. An epoch that fell would make tokens already issued at
        // the higher value readable again.
        //
        // Bindings go through execute(), which is the form this class uses
        // everywhere else and the only one that works: MDB2's bindValue() wants
        // a named placeholder, and passing a position instead produces
        // "Unable to bind to missing placeholder: 0" at execution time — a
        // failure php -l cannot see and one this file's own working statements
        // never hit because they do not bind by position.
        $this->executeStatement(
            'UPDATE tbl_rest_identity_epoch'
            . ' SET epoch = GREATEST(epoch + 1, ?), grants_fingerprint = ?, updated_at = NOW()'
            . ' WHERE user_id = ?',
            array($epoch + 1, $fingerprint, $userId)
        );

        return $epoch + 1;
    }

    /**
     * The grants fingerprint for a user: their groups and whether they are active.
     *
     * Status is in it because deactivation has to move the epoch too, and
     * nothing else in Chisimba changes when an account is switched off. A
     * deactivation is the clearest case of a token that must stop being readable
     * even though its timestamps are all perfectly valid.
     *
     * @param string $userId The Chisimba logical user identifier
     * @return string A hex digest, or a placeholder if it cannot be computed
     */
    private function grantsFingerprintOf(string $userId): string
    {
        $claims = $this->identityClaims($userId);
        $shape = array(
            'groups' => $claims['ctxs'],
            // The three-valued state rather than the boolean claim. A fingerprint
            // that cannot tell 'unknown' from 'active' reports no change when the
            // account could not be read at all, and a deactivated user whose row
            // briefly failed to read would keep their grants until the token
            // expired on its own.
            'active' => $this->accountState($userId),
        );

        // Sorted, because the order rows come back in is not a fact about the
        // user: adding somebody to a group should not change their fingerprint.
        sort($shape['groups']);
        $json = json_encode($shape);
        if ($json === false) {
            // A fingerprint that cannot be computed must not read as "nothing
            // changed", or a broken user would keep their old epoch for ever.
            return 'uncomputable:' . sha1($userId);
        }
        return hash('sha256', $json);
    }

    /**
     * The public JWKS document, for services that verify tokens locally.
     *
     * Public material only, by construction: the document is derived from the
     * keypair's public half, so there is no code path by which the private
     * exponent could reach a response.
     *
     * @return array
     */
    public function jwks(): array
    {
        return $this->signingKeys->jwks();
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

        $header = json_decode($this->base64UrlDecode($encodedHeader), true);
        if (!is_array($header)) return null;

        // The algorithm is checked before the signature, and against one
        // expected value rather than a configured list. A verifier that reads
        // the algorithm out of the token it is verifying will accept a token
        // that asks to be verified as HS256, using the public key as a shared
        // HMAC secret — which is the classic JWT confusion attack and is
        // exactly what signing with RSA makes possible. 'none' is refused for
        // the same reason: an unsigned payload must never count as verified.
        if (($header['alg'] ?? '') !== 'RS256') return null;

        $key = openssl_pkey_get_public($this->signingKeys->publicKeyPem());
        if ($key === false) return null;

        $verified = openssl_verify(
            "$encodedHeader.$encodedPayload",
            $this->base64UrlDecode($signature),
            $key,
            OPENSSL_ALGO_SHA256
        );
        // 1 is a valid signature, 0 is invalid and -1 is an error. Only 1 passes:
        // an error must not be read as a pass.
        if ($verified !== 1) return null;

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
        // Before minting anything, notice whether this user's grants have moved
        // since the last token. If they have, the epoch rises, and every token
        // already issued to them becomes unreadable — which is the point of an
        // epoch, and the reason revocation needs a second check beside the
        // timestamp.
        $this->syncEpochFor($userId);

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