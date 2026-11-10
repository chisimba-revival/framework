<?php
/**
 * SigningKeyService - the RSA keypair Chisimba signs REST API tokens with.
 *
 * Why this exists: the field service validates tokens locally against a
 * published JWKS document and never calls back to check them. That rules out
 * HS256, because a shared secret would have to be handed to every service that
 * verifies a token, and any one of them could then mint tokens for all of them.
 * With an asymmetric keypair the public half is published and the private half
 * never leaves this installation.
 *
 * The key is generated on first use and written outside the source tree. It is
 * never stored in the repository, never logged and never returned by the
 * public document.
 *
 * This is a plain collaborator rather than a ChisimbaObject, for the same
 * reason TokenAuthService is: it has no engine handle it needs, and inventing
 * one to reach a module configuration would be worse than being explicit about
 * where the key comes from.
 */
class SigningKeyService
{
    /** Key size in bits. 2048 is the current default for RSA signing keys. */
    const KEY_BITS = 2048;

    /** @var string|null Overridable for tests; the resolved path in production. */
    private $path = null;

    /**
     * Where the private key lives.
     *
     * Require an explicit private path, preserving the installation's existing
     * key when upgrading. Never silently create a replacement under the web root.
     *
     * @return string
     */
    public function keyPath(): string
    {
        if ($this->path !== null) return $this->path;
        $environment = getenv('CHISIMBA_JWKS_KEY_PATH');
        $configured = is_string($environment) && trim($environment) !== ''
            ? trim($environment) : self::configuredKeyPath();
        if ($configured === null || !str_starts_with($configured, '/')) {
            throw new RuntimeException('Configure an absolute REST API signing-key path outside the web root.');
        }
        $directory = realpath(dirname($configured));
        if ($directory === false) throw new RuntimeException('Create the private REST API key directory before enabling the API.');
        $path = $directory . '/' . basename($configured);
        $resolved = realpath($path) ?: $path;
        $roots = [realpath(dirname(__DIR__, 3)), realpath($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 3))];
        foreach ($roots as $root) {
            if ($root !== false && ($resolved === $root || str_starts_with($resolved, rtrim($root, '/') . '/'))) {
                throw new RuntimeException('The REST API signing key must be outside the web root.');
            }
        }
        return $this->path = $path;
    }

    /**
     * Read KEWL_JWKS_KEY_PATH from the installation's restapi configuration.
     *
     * Returns null when the constant is absent or blank, so that absence falls
     * through to the next source rather than producing an empty path.
     *
     * @return string|null
     */
    private static function configuredKeyPath(): ?string
    {
        // Three levels up, not two: this file is in core_modules/<module>/classes,
        // so reaching the docroot means climbing out of classes, out of the
        // module, and out of core_modules. The first version of this climbed two
        // and looked in core_modules/security/config/, which does not exist — so
        // the constant was silently never read and the service fell through to
        // the default path with nothing to say why.
        $file = dirname(dirname(dirname(__DIR__))) . '/config/restapi_inc.php';
        if (!is_readable($file)) {
            return null;
        }
        require_once $file;
        if (!defined('KEWL_JWKS_KEY_PATH')) {
            return null;
        }
        $value = KEWL_JWKS_KEY_PATH;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * The private key, generated and stored on first use.
     *
     * Idempotent by design: a key that already exists is loaded, never
     * replaced. Replacing it on every start would invalidate every token in
     * circulation, and doing that during a deploy is the kind of failure that
     * looks like an outage caused by something else entirely.
     *
     * @throws RuntimeException when the key cannot be read or written
     */
    public function privateKey(): string
    {
        $path = $this->keyPath();
        if (is_file($path)) {
            $pem = file_get_contents($path);
            if ($pem === false) throw new RuntimeException('Cannot read the configured REST API signing key.');
            return $pem;
        }
        // A first-use race must not generate two keys and invalidate the first
        // caller's token. Serialise provisioning, then publish the complete PEM.
        $mask = umask(0077);
        try { $lock = fopen($path . '.lock', 'c'); } finally { umask($mask); }
        if ($lock === false) throw new RuntimeException('Cannot open the signing-key provisioning lock.');
        try {
            if (!flock($lock, LOCK_EX)) throw new RuntimeException('Cannot lock signing-key provisioning.');
            if (!is_file($path)) {
                $resource = openssl_pkey_new(['private_key_bits'=>self::KEY_BITS, 'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
                if ($resource === false || !openssl_pkey_export($resource, $pem)) throw new RuntimeException('Cannot generate the REST API signing key.');
                $this->store($path, $pem);
            }
            $pem = file_get_contents($path);
            if ($pem === false) throw new RuntimeException('Cannot read the provisioned signing key.');
            return $pem;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * Write the private key, creating its directory and restricting the mode.
     *
     * The permissions are set before the key is written, not after. A key
     * created under a permissive umask exists briefly readable by anything
     * running as another user on the host, and tightening it afterwards is a
     * window rather than a guarantee.
     *
     * @throws RuntimeException when the key cannot be stored
     */
    private function store(string $path, string $pem): void
    {
        $temporary = tempnam(dirname($path), '.rest-key-');
        if ($temporary === false) throw new RuntimeException('Cannot prepare the signing-key file.');
        try {
            if (!chmod($temporary, 0600) || file_put_contents($temporary, $pem) !== strlen($pem)
                || !rename($temporary, $path)) throw new RuntimeException('Cannot publish the signing key.');
        } finally {
            if (is_file($temporary)) unlink($temporary);
        }
    }

    /**
     * The key id, derived from the key itself.
     *
     * Derived rather than stored so there is no second record that can disagree
     * with the key. A random identifier would have to be written somewhere, and
     * a key file holding an identifier that did not match its key would produce
     * tokens nobody could verify — which looks exactly like a signature failure
     * and costs hours to find.
     *
     * @return string
     */
    public function keyId(): string
    {
        $key = openssl_pkey_get_private($this->privateKey());
        if ($key === false) {
            throw new RuntimeException(
                'The REST API signing key could not be loaded: ' . openssl_error_string()
            );
        }

        $details = openssl_pkey_get_details($key);
        if ($details === false || !isset($details['rsa']['n'])) {
            throw new RuntimeException('The REST API signing key is not an RSA key.');
        }

        // A fingerprint of the public half. Only the public modulus, so the
        // identifier cannot be used to learn anything about the private key.
        return substr(hash('sha256', $details['rsa']['n']), 0, 16);
    }

    /**
     * The public half as PEM, for verifying a token this installation signed.
     *
     * Offered because reassembling an RSA key from a bare modulus needs DER
     * encoding, and doing that by hand to verify a signature is exactly the
     * kind of cleverness that produces a subtly wrong key. OpenSSL already has
     * the assembled key; this hands it over.
     *
     * @return string
     */
    public function publicKeyPem(): string
    {
        $key = openssl_pkey_get_private($this->privateKey());
        if ($key === false) {
            throw new RuntimeException(
                'The REST API signing key could not be loaded: ' . openssl_error_string()
            );
        }

        $details = openssl_pkey_get_details($key);
        if ($details === false || !isset($details['key'])) {
            throw new RuntimeException('The REST API signing key is not an RSA key.');
        }

        return (string)$details['key'];
    }

    /**
     * The public JWKS document.
     *
     * Only public values appear here, which is the entire point of publishing a
     * key: a verifier needs the modulus and the exponent and nothing else. If
     * the private exponent ever appears in this document then every token this
     * installation signs can be forged by anybody who can read it.
     *
     * @return array The JWKS document, ready to be json_encoded
     */
    public function jwks(): array
    {
        $key = openssl_pkey_get_private($this->privateKey());
        if ($key === false) {
            throw new RuntimeException(
                'The REST API signing key could not be loaded: ' . openssl_error_string()
            );
        }

        $details = openssl_pkey_get_details($key);
        if ($details === false || !isset($details['rsa']['n'], $details['rsa']['e'])) {
            throw new RuntimeException('The REST API signing key is not an RSA key.');
        }

        return [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'alg' => 'RS256',
                    'use' => 'sig',
                    'kid' => $this->keyId(),
                    'n' => self::base64Url($details['rsa']['n']),
                    'e' => self::base64Url($details['rsa']['e']),
                ],
            ],
        ];
    }

    /**
     * Sign a claim set, returning the compact serialisation.
     *
     * The algorithm is named rather than left to the key, so that a key file
     * cannot change how tokens are signed by accident. Only RS256 is ever
     * produced here: the contract requires the field service to accept no other
     * algorithm, and a gateway that could emit several would be a gateway whose
     * verifier has to be lenient to be useful.
     *
     * @param array $claims The claim set, already assembled
     * @return string The compact JWS
     */
    public function sign(array $claims): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'RS256', 'kid' => $this->keyId()];

        $signingInput = self::base64Url(json_encode($header))
            . '.' . self::base64Url(json_encode($claims));

        $key = openssl_pkey_get_private($this->privateKey());
        if ($key === false) {
            throw new RuntimeException(
                'The REST API signing key could not be loaded: ' . openssl_error_string()
            );
        }

        $signature = '';
        if (!openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException(
                'The REST API token could not be signed: ' . openssl_error_string()
            );
        }

        return $signingInput . '.' . self::base64Url($signature);
    }

    /**
     * Base64url without padding, as every JWT requires.
     *
     * @param string $bytes
     * @return string
     */
    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}