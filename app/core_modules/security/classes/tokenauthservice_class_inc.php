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
    private $denylist;
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
        require_once dirname(__DIR__) . '/classes/signingkeyservice_class_inc.php';
        $this->signingKeys = new SigningKeyService();

        // Raises the epoch in MySQL and publishes it to the denylist the field
        // services read. Both or neither: an epoch recorded in a database
        // nothing reads is a revocation that has not happened.
        require_once dirname(__DIR__) . '/classes/denylistpublisher_class_inc.php';
        $this->denylist = new DenylistPublisher();
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
     * The scope a service token carries, and the only one this class issues.
     *
     * It is a constant rather than a parameter because the scope decides which
     * door the token opens at the far end, and a caller that could pass an
     * arbitrary scope would be a caller that could mint a token for a door this
     * class has no reason to open. Adding a second scope is a change to this
     * constant and to the service that needs it, not an argument.
     */
    const SERVICE_SCOPE = 'field:write';

    /**
     * How long a service token lives.
     *
     * Short, and deliberately not configurable from outside. A service token is
     * minted and used within a single verification and is not stored by anyone,
     * so there is nothing for a long lifetime to buy. The cost of a long-lived
     * one would be paid at the far end: it would have to be revocable, and the
     * field service's revocation is keyed on a subject and an epoch, neither of
     * which a service has.
     */
    const SERVICE_TOKEN_TTL = 300;

    /**
     * Mint a token for a service rather than for a person.
     *
     * This is NOT generateJWT with a different type. generateJWT merges
     * identityClaims(), which is the user's grant set, active context, roles and
     * identity epoch — and a service has none of those. Minting through that path
     * would produce a token naming the service while carrying somebody's
     * contexts and scopes, which is a token that looks like two things at once
     * and would be honoured as whichever the receiving door happened to check.
     *
     * So the claim set is written out here, and the absence of ctxs, ctx, roles
     * and epoch is the point rather than an omission. The field service's service
     * door requires no grants and opens no transaction precisely because a
     * service token carries none, and a token that quietly carried some would
     * break that agreement in a way that only shows up as a write landing in a
     * context the service never named.
     *
     * The subject names the service, never a person. A verification names the
     * mentor it acts for in the request body, where the field service checks it
     * against Chisimba's records; naming one here would be the service asserting
     * an identity it has no business asserting.
     *
     * @param string $serviceName Stable identifier of the calling service
     * @param int|null $ttl Override for the service token lifetime
     * @return string
     */
    public function issueServiceToken(string $serviceName, ?int $ttl = null, ?array $scopes = null): string
    {
        $serviceName = trim($serviceName);
        if ($serviceName === '') {
            // Refused rather than defaulted to a name. A token whose subject is
            // an empty string is a token whose subject is anybody, and the log
            // would record a service that cannot be named.
            throw new InvalidArgumentException(
                'A service token requires a service name.'
            );
        }

        $now = time();
        $tokenId = bin2hex(random_bytes(16));
        $lifetime = ($ttl === null || $ttl <= 0)
            ? self::SERVICE_TOKEN_TTL : $ttl;

        return $this->signingKeys->sign([
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $now,
            'exp' => $now + $lifetime,
            'sub' => $serviceName,
            'type' => 'service',
            // The scope is an ARRAY, which is what the field service requires.
            // A space-separated string is the OAuth spelling of the same thing
            // and would be read as one scope called "field:write x", which is a
            // scope nothing holds.
            //
            // A caller that names the scopes it needs gets exactly those; a
            // caller that names none gets the default. The list was previously
            // ignored when passed, so a module asking for field:write was
            // silently issued field:verify instead.
            'scope' => (!empty($scopes)
                ? array_values(array_unique($scopes))
                : [self::SERVICE_SCOPE]),
            'jti' => $tokenId,
            'ver' => $tokenId,
        ]);
    }

    /**
     * The scopes each field role carries.
     *
     * A person's roles are read from their context-role group memberships (see
     * splitContextRoles), and the scope their token carries is the union of the
     * scopes of the roles it names. This map is the one definition of both, so
     * the scopes on a token are the same set the provisioning script materialised
     * into those groups, and a token can never carry a scope its role does not
     * grant: an unknown role contributes nothing rather than being invented.
     *
     * It is deliberately not read from tbl_perms_grouprights on every token. The
     * group rights are the enforcement the modules consult; this is the smaller,
     * auditable statement of what each role is for, kept next to the claim it
     * feeds.
     */
    const ROLE_SCOPES = array(
        'Student' => array('field:read', 'field:write'),
        'Guide' => array('field:read', 'field:write'),
        'Mentor' => array('field:read', 'field:write', 'field:verify'),
        'Admin' => array('field:read', 'field:write', 'field:verify', 'field:revoke'),
    );

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
     * A field grant is a group named "<context>^<role>" — "north^Mentor" — and
     * it carries both halves of the answer: the context is where the person may
     * work, the role is what they may do there. The name is part of the security
     * boundary and is recorded rather than hidden: 'north^Mentor' and
     * 'north^mentor' are two different groups, and a typo in an operator's
     * provisioning is a grant nobody holds until it is corrected. This is a real
     * cost of reusing existing data, paid knowingly.
     *
     * ctx is the active context — the one a new record is written into. It is the
     * user's stored active context when that is one they may write to, and
     * otherwise the single context they hold; when a user holds several contexts
     * and has not chosen one it is deliberately left EMPTY, so writes are refused
     * by the field service rather than a trainee's sighting landing in whichever
     * context happened to sort first. That quiet wrongness is what this system
     * exists to prevent. Reads follow the grant set and are unaffected by ctx.
     *
     * @param string $userId Chisimba logical user identifier
     * @return array
     */
    public function identityClaims(string $userId): array
    {
        $split = $this->splitContextRoles($this->groupsFor($userId));
        $contexts = $split['contexts'];
        $roles = $split['roles'];

        $active = $this->activeContextFor($userId);
        if ($active !== '' && in_array($active, $contexts, true)) {
            $ctx = $active;
        } elseif (count($contexts) === 1) {
            $ctx = $contexts[0];
        } else {
            $ctx = '';
        }

        return array(
            // Whether the account is switched on. It is in the claims because a
            // token must not outlive the account it was issued to, and because
            // nothing else in Chisimba changes when an account is deactivated.
            'active' => $this->accountState($userId) === 'active',
            // ctx and ctxs are separate on purpose. Using the active context as
            // the read boundary would hide a guide's northern-reserve drives
            // while they happen to be working in the southern one.
            'ctx' => $ctx,
            'ctxs' => $contexts,
            // Roles are the role halves of the context-role groups. The sign-off
            // workflow reads them to decide who may act as a programme
            // administrator, which is why they are names and not a boolean.
            'roles' => $roles,
            'epoch' => $this->epochFor($userId),
            'scope' => $this->scopesForRoles($roles),
        );
    }

    /**
     * Split group names into the contexts and roles they encode.
     *
     * A field grant is a group named "<context>^<role>". The context half is
     * where a person may work; the role half is what they may do there. A group
     * without the separator is not a field grant and contributes neither half,
     * which is what keeps LiveUser's 'Site Admin', 'Lecturers' and 'Students'
     * out of the claim set instead of turning them into contexts.
     *
     * @param array $groups Raw group names from groupsFor()
     * @return array{contexts: array, roles: array}
     */
    private function splitContextRoles(array $groups): array
    {
        $contexts = array();
        $roles = array();
        foreach ($groups as $name) {
            $name = (string)$name;
            $separator = strpos($name, '^');
            if ($separator === false || $separator === 0) {
                continue;
            }
            $context = substr($name, 0, $separator);
            $role = substr($name, $separator + 1);
            if ($context === '' || $role === '') {
                continue;
            }
            $contexts[$context] = true;
            $roles[$role] = true;
        }
        $contextList = array_keys($contexts);
        $roleList = array_keys($roles);
        sort($contextList);
        sort($roleList);

        return array('contexts' => $contextList, 'roles' => $roleList);
    }

    /**
     * The scopes the named roles carry, as a sorted union.
     *
     * @param array $roles
     * @return array
     */
    private function scopesForRoles(array $roles): array
    {
        $scopes = array();
        foreach ($roles as $role) {
            if (!isset(self::ROLE_SCOPES[$role])) {
                continue;
            }
            foreach (self::ROLE_SCOPES[$role] as $scope) {
                $scopes[$scope] = true;
            }
        }
        $list = array_keys($scopes);
        sort($list);

        return $list;
    }

    /**
     * The context a new record is written into, as stored for this user.
     *
     * Read from the same active-context store the field-guiding features use, so
     * that "where am I working" has one answer. A missing store or a failed read
     * returns '' — the claim falls back to the single-context rule and writes to
     * a multi-context user are refused, which is the safe direction.
     *
     * @param string $userId Chisimba logical user identifier
     * @return string The active context code, or '' if none is set
     */
    private function activeContextFor(string $userId): string
    {
        $statement = $this->db->prepare(
            'SELECT context_code FROM tbl_field_active_context WHERE userid = ?',
            array('text')
        );
        if (MDB2::isError($statement)) {
            return '';
        }
        $result = $statement->execute(array($userId));
        if (MDB2::isError($result)) {
            $statement->free();
            return '';
        }
        $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        $statement->free();
        if (!is_array($row) || !isset($row['context_code'])) {
            return '';
        }

        return (string)$row['context_code'];
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
     * Every group a user belongs to, by name.
     *
     * The names are returned whole and interpreted by the caller: a
     * "<context>^<role>" name is a field grant, anything else (the LiveUser
     * programme groups) is not. The interpretation lives next to the claim it
     * feeds rather than hidden in the query, so there is one place that decides
     * what a group name means.
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

        // Published once the database has accepted the new epoch, so a
        // published epoch is never one the database has not recorded.
        //
        // A failure here is deliberately not caught. Raising an epoch and failing
        // to publish it leaves tokens issued under the old grants readable, and
        // returning quietly would report a revocation that has not happened.
        // Refusing the sign-in is the only outcome that matches what the caller
        // was just told.
        $this->denylist->publish($userId, $epoch + 1);

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
            // Roles and scopes are in the fingerprint alongside the contexts. A
            // change of role inside the same context — a trainee becoming a
            // mentor — leaves ctxs untouched, and a fingerprint over contexts
            // alone would call that no change and leave the old,
            // lower-privileged token valid until it expired on its own.
            'roles' => $claims['roles'],
            'scope' => $claims['scope'],
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
        sort($shape['roles']);
        sort($shape['scope']);
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
        $detailed = $this->validateFromRequestDetailed($request);
        return $detailed['user_id'] ?? null;
    }

    /**
     * Validate an access token or API key and report which kind of credential
     * it was, plus its scopes when it is an API key.
     *
     * Bearer access tokens carry no scope claim, so their 'scopes' is null —
     * meaning "not scope-constrained". API keys carry the scopes they were
     * minted with, and a key with an unparseable scope blob reports an empty
     * array so the caller fails closed rather than granting everything.
     *
     * @param array $request Request headers
     * @return array|null {user_id, type: 'jwt'|'api_key', scopes: ?array}, or null when no usable credential was presented
     */
    public function validateFromRequestDetailed(array $request): ?array
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
            if ($result) {
                return [
                    'user_id' => $result[0],
                    'type' => 'jwt',
                    'scopes' => null,
                ];
            }
        }

        $apiKey = $headers['x-api-key'] ?? '';
        if ($apiKey) {
            $detailed = $this->validateApiKeyDetailed($apiKey);
            if ($detailed) {
                $detailed['type'] = 'api_key';
                return $detailed;
            }
        }

        return null;
    }

    /**
     * Validate an API key and return user ID
     */
    public function validateApiKey(string $apiKey): ?string
    {
        $detailed = $this->validateApiKeyDetailed($apiKey);
        return $detailed ? $detailed['user_id'] : null;
    }

    /**
     * Validate an API key and return both user ID and scopes.
     *
     * @return array|null {user_id, scopes: array}, or null when the key is unknown, expired or revoked
     */
    public function validateApiKeyDetailed(string $apiKey): ?array
    {
        if (!str_starts_with($apiKey, self::API_KEY_PREFIX)) {
            return null;
        }

        // Extract prefix for quick lookup
        $prefix = substr($apiKey, 0, 12);
        $keyHash = hash('sha256', $apiKey);

        $sql = "
            SELECT user_id, expires_at, revoked_at, scopes
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

        return [
            'user_id' => (string)$row['user_id'],
            'scopes' => $this->decodeScopes($row['scopes'] ?? null),
        ];
    }

    /**
     * Decode a stored scopes blob into a scope list.
     *
     * A missing or unparseable blob is reported as an empty list, so an
     * unscoped key never accidentally grants a route.
     */
    private function decodeScopes($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $scopes = [];
        foreach ($decoded as $scope) {
            if (is_string($scope) && $scope !== '') {
                $scopes[] = $scope;
            }
        }
        return $scopes;
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

        // The device is part of the token's identity, not decoration. The same
        // refresh token presented from another device is treated as stolen, so
        // the row that proves the token belongs to this install has to record
        // which install that is. An unbound client stores NULL, so the refresh
        // check can tell "never bound" from "bound to the empty string".
        $deviceId = isset($options['device_id']) && $options['device_id'] !== ''
            ? substr((string) $options['device_id'], 0, 255)
            : null;

        $this->executeStatement("
            INSERT INTO tbl_rest_refresh_tokens
                (user_id, token_hash, expires_at, user_agent, ip_address, device_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ", [$userId, $refreshHash, $expiresAt, $userAgent, $ipAddress, $deviceId]);

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
    public function refreshAccessToken(string $refreshToken, string $deviceId = ''): ?array
    {
        // Validate refresh token structure
        $parts = explode('.', $refreshToken);
        if (count($parts) !== 3) return null;

        $refreshHash = hash('sha256', $refreshToken);

        // Look up in database
        $sql = "
            SELECT user_id, expires_at, revoked_at, device_id
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

        // A row that is present but already revoked is a replay: the token was
        // rotated (or revoked) earlier, so the copy in the caller's hand should
        // no longer exist. A well-behaved client gives up the old token the
        // moment it is used, which leaves two explanations — a captured token
        // being replayed, or a lost rotation response being retried with the
        // stale token. They are indistinguishable here, and reuse of a rotated
        // token is the documented signal of compromise, so take the safe
        // reading: revoke the whole family instead of minting a fresh pair that
        // would also keep the thief alive. This is what the refresh docblock and
        // the field-service contract promise, and it is the missing half of the
        // device-binding revocation further down: refusing only the presented
        // token left the sibling copies already taken from the family usable.
        //
        // This is checked before expiry on purpose. An expired token that was
        // never revoked is housekeeping; a revoked token is evidence, whether or
        // not it has also expired.
        if ($row['revoked_at']) {
            $owner = (string) $row['user_id'];
            $familyDevice = isset($row['device_id']) ? (string) $row['device_id'] : '';
            if ($familyDevice !== '') {
                $this->revokeAllUserDeviceTokens($owner, $familyDevice);
            } else {
                // No device was ever recorded, so there is no narrower family
                // to revoke. Undo every session this user holds.
                $this->revokeAllUserTokens($owner);
            }
            return null;
        }

        // Check expiration
        if (strtotime($row['expires_at']) < time()) {
            $this->revokeRefreshToken($refreshHash);
            return null;
        }

        // Decode to verify type
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        if (!$payload || ($payload['type'] ?? '') !== 'refresh') {
            return null;
        }

        $userId = (string)$payload['sub'];
        if ($userId !== (string) $row['user_id']) return null;

        // The refresh token is bound to the install that signed in. A token
        // presented by a different device is not a typo, it is a stolen
        // credential, so the whole family is revoked rather than this one row:
        // refusing only the presented token would leave the thief's earlier
        // copies live. A row with no device recorded predates the binding and
        // is accepted, because refusing it would sign out every device that
        // signed in before this existed.
        $bound = isset($row['device_id']) ? (string) $row['device_id'] : '';
        if ($deviceId !== '' && $bound !== '' && !hash_equals($bound, $deviceId)) {
            $this->revokeAllUserDeviceTokens($userId, $bound);
            return null;
        }

        // Issue new token pair (rotate refresh token). The fresh refresh token
        // keeps the device binding, or the rotation would quietly unbind the
        // install that just proved it owns the token.
        $this->revokeRefreshToken($refreshHash);
        $options = ($bound !== '') ? ['device_id' => $bound] : [];
        return $this->issueTokens($userId, $options);
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
     * Revoke every refresh token a user holds for one device.
     *
     * This is the counterpart to the device binding: when a token turns up on a
     * device it was not minted for, every token that device-bound family
     * carries is revoked together. Without it, revoking the one presented token
     * would leave the stolen family's other members live, and the theft would
     * only be half undone. It is also the sign-out path for a client that names
     * its device, so losing a phone ends the sessions on that phone alone
     * rather than signing the trainee out everywhere.
     */
    public function revokeAllUserDeviceTokens(string $userId, string $deviceId): void
    {
        if ($deviceId === '') {
            // An empty device is not a device. Falling back to the all-device
            // revocation here would turn a client bug into a full sign-out, so
            // it does nothing instead.
            return;
        }

        $this->executeStatement("
            UPDATE tbl_rest_refresh_tokens
            SET revoked_at = NOW()
            WHERE user_id = ? AND device_id = ? AND revoked_at IS NULL
        ", [$userId, $deviceId]);
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