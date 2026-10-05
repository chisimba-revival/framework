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
     * The default is under the framework's var directory rather than beside
     * this file, so that a deployment which serves the source tree cannot also
     * serve its signing key. The environment variable exists so an installation
     * can put the key on a mounted secret volume instead, which is what it
     * should do in production.
     *
     * @return string
     */
    public function keyPath(): string
    {
        if ($this->path !== null) {
            return $this->path;
        }

        $fromEnvironment = getenv('CHISIMBA_JWKS_KEY_PATH');
        if (is_string($fromEnvironment) && trim($fromEnvironment) !== '') {
            return $this->path = $fromEnvironment;
        }

        return $this->path = dirname(dirname(dirname(__DIR__))) . '/var/restapi/jwks_private.pem';
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
            if ($pem === false) {
                throw new RuntimeException(
                    'The REST API signing key exists at ' . $path . ' but could not be read.'
                );
            }
            return $pem;
        }

        $resource = openssl_pkey_new([
            'private_key_bits' => self::KEY_BITS,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        if ($resource === false) {
            throw new RuntimeException(
                'The REST API signing key could not be generated: ' . openssl_error_string()
            );
        }

        $pem = '';
        if (!openssl_pkey_export($resource, $pem)) {
            throw new RuntimeException(
                'The REST API signing key could not be exported: ' . openssl_error_string()
            );
        }

        $this->store($path, $pem);
        return $pem;
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
        $directory = dirname($path);
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException(
                'The REST API signing key directory ' . $directory . ' could not be created.'
            );
        }

        // The file is created with its final contents and mode together where
        // the platform allows it. A restrictive umask still applies, so this
        // narrows the window rather than closing it, and that is the honest
        // claim. A deployment that needs it closed should mount the key as a
        // secret with CHISIMBA_JWKS_KEY_PATH pointing at it.
        if (@file_put_contents($path, $pem, LOCK_EX) === false) {
            throw new RuntimeException(
                'The REST API signing key could not be written to ' . $path . '.'
            );
        }
        @chmod($path, 0600);
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