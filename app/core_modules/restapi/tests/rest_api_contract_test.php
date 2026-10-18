<?php
/**
 * REST API Contract Test
 * 
 * Tests the REST API endpoints against the field-guiding service contract.
 * This test verifies that the endpoints match the specification in:
 * framework/docs/architecture/field-guiding-service-contract.md
 * 
 * Run from Chisimba context: php /path/to/rest_api_contract_test.php
 * 
 * This test uses HTTP requests to test the actual running API endpoints.
 */

// Must be run from Chisimba context
if (!isset($GLOBALS['kewl_entry_point_run'])) {
    $GLOBALS['kewl_entry_point_run'] = true;
}

class RestApiContractTest
{
    private $baseUrl;
    private $testResults = [];

    /**
     * Cookie jar holding the session a login is bound to.
     *
     * The abuse evidence is signed against the issuing session, so a login
     * that does not carry that session's cookie back is refused as invalid
     * credentials rather than as bad evidence — the same status a wrong
     * password gets, which is a misleading failure to debug. Null when no
     * session is being carried, which leaves requests cookie-free as before.
     *
     * @var string|null
     */
    private $sessionCookieFile = null;

    public function __construct()
    {
        // Use the internal Docker network URL
        $this->baseUrl = 'http://localhost/api/v1';
    }

    /**
     * Run all contract tests
     */
    public function runAllTests(): void
    {
        echo "=== REST API Contract Tests ===\n\n";
        
        $this->testTokenEndpoints();
        $this->testRefreshEndpoint();
        $this->testProfileEndpoint();
        $this->testBackwardCompatibility();
        $this->testJwksEndpoint();
        $this->testSpeciesCatalogue();
        $this->testCatalogueProxies();
        $this->testSyncProxies();

        $this->printSummary();
    }

    /**
     * Test token exchange endpoints (POST /api/v1/auth/token)
     */
    private function testTokenEndpoints(): void
    {
        echo "--- Token Exchange Endpoints ---\n";
        
        // Test 1: GET /api/v1/auth/token returns abuse evidence
        $this->test('GET /api/v1/auth/token returns abuse evidence', function() {
            $response = $this->httpRequest('GET', '/auth/token');
            $this->assertStatus($response, 200);
            // Response is wrapped in {data: ..., meta: ...}
            $data = $response['body']['data'] ?? $response['body'];
            $this->assertHasKeys($data, ['csrf_token', 'issued_at', 'nonce', 'signature', 'minimum_seconds', 'maximum_seconds']);
            $this->assertIsString($data['csrf_token']);
            $this->assertIsInt($data['issued_at']);
            $this->assertIsString($data['nonce']);
            $this->assertIsString($data['signature']);
        });

        // Test 2: POST /api/v1/auth/token without credentials fails
        $this->test('POST /api/v1/auth/token without credentials fails', function() {
            $response = $this->httpRequest('POST', '/auth/token', []);
            $this->assertStatus($response, 422); // Validation error (Unprocessable Entity)
        });

        // Test 3: POST /api/v1/auth/token with invalid credentials fails
        $this->test('POST /api/v1/auth/token with invalid credentials fails', function() {
            // First get abuse evidence
            $evidence = $this->httpRequest('GET', '/auth/token');
            if ($evidence['status'] !== 200) {
                throw new Exception('Could not get abuse evidence');
            }
            
            // Extract data from wrapped response
            $evidenceData = $evidence['body']['data'] ?? $evidence['body'];
            
            $response = $this->httpRequest('POST', '/auth/token', [
                'username' => 'nonexistent_user',
                'password' => 'wrong_password',
                'csrf_token' => $evidenceData['csrf_token'],
                'abuse_issued_at' => $evidenceData['issued_at'],
                'abuse_nonce' => $evidenceData['nonce'],
                'abuse_signature' => $evidenceData['signature'],
                'device_id' => 'test-device-123'
            ]);
            $this->assertStatus($response, 401);
        });
    }

    /**
     * Test token refresh endpoint (POST /api/v1/auth/refresh)
     */
    private function testRefreshEndpoint(): void
    {
        echo "\n--- Token Refresh Endpoint ---\n";
        
        // Test 1: POST /api/v1/auth/refresh without refresh token fails
        $this->test('POST /api/v1/auth/refresh without refresh token fails', function() {
            $response = $this->httpRequest('POST', '/auth/refresh', [
                'device_id' => 'test-device-123'
            ]);
            $this->assertStatus($response, 422); // Validation error (Unprocessable Entity)
        });

        // Test 2: POST /api/v1/auth/refresh without device_id fails
        $this->test('POST /api/v1/auth/refresh without device_id fails', function() {
            $response = $this->httpRequest('POST', '/auth/refresh', [
                'refresh_token' => 'invalid_token'
            ]);
            $this->assertStatus($response, 422); // Validation error (Unprocessable Entity)
        });

        // Test 3: POST /api/v1/auth/refresh with invalid token fails
        $this->test('POST /api/v1/auth/refresh with invalid token fails', function() {
            $response = $this->httpRequest('POST', '/auth/refresh', [
                'refresh_token' => 'invalid_token',
                'device_id' => 'test-device-123'
            ]);
            $this->assertStatus($response, 401);
        });

        // Test 4: Verify TokenAuthService has required methods for family revocation
        $this->test('TokenAuthService has family revocation methods', function() {
            // We verify this by checking the source file directly
            $source = file_get_contents('/var/www/html/core_modules/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'revokeAllUserDeviceTokens') !== false, 
                'revokeAllUserDeviceTokens method must exist for family revocation');
            $this->assertTrue(strpos($source, 'function refreshAccessToken') !== false,
                'refreshAccessToken method must exist');
        });
    }

    /**
     * Test profile endpoint (GET /api/v1/user/profile)
     */
    private function testProfileEndpoint(): void
    {
        echo "\n--- Profile Endpoint ---\n";
        
        // Test 1: GET /api/v1/user/profile without auth fails
        $this->test('GET /api/v1/user/profile without auth fails', function() {
            $response = $this->httpRequest('GET', '/user/profile');
            $this->assertStatus($response, 401);
        });

        // Test 2: Verify controller has GET_user_profile method
        $this->test('Controller has GET_user_profile method', function() {
            $source = file_get_contents('/var/www/html/core_modules/restapi/controller.php');
            $this->assertTrue(strpos($source, 'GET_user_profile') !== false,
                'GET_user_profile method must exist on controller');
        });
    }

    /**
     * Test backward compatibility endpoints
     */
    private function testBackwardCompatibility(): void
    {
        echo "\n--- Backward Compatibility ---\n";
        
        // Test 1: GET /api/v1/auth/login (deprecated but working)
        $this->test('GET /api/v1/auth/login returns abuse evidence', function() {
            $response = $this->httpRequest('GET', '/auth/login');
            $this->assertStatus($response, 200);
            // Response is wrapped in {data: ..., meta: ...}
            $data = $response['body']['data'] ?? $response['body'];
            $this->assertHasKeys($data, ['csrf_token', 'issued_at', 'nonce', 'signature']);
        });

        // Test 2: GET /api/v1/auth/me (deprecated but working)
        $this->test('GET /api/v1/auth/me without auth fails', function() {
            $response = $this->httpRequest('GET', '/auth/me');
            $this->assertStatus($response, 401);
        });
    }

    /**
     * Test JWKS endpoint
     */
    private function testJwksEndpoint(): void
    {
        echo "\n--- JWKS Endpoint ---\n";
        
        $this->test('GET /api/v1/auth/jwks returns valid JWKS', function() {
            $response = $this->httpRequest('GET', '/auth/jwks');
            $this->assertStatus($response, 200);
            $this->assertHasKeys($response['body'], ['keys']);
            $this->assertIsArray($response['body']['keys']);
            
            if (count($response['body']['keys']) > 0) {
                $key = $response['body']['keys'][0];
                $this->assertHasKeys($key, ['kty', 'e', 'n', 'kid', 'alg', 'use']);
                $this->assertEquals('RSA', $key['kty']);
                $this->assertEquals('RS256', $key['alg']);
            }
        });
    }

    /**
     * Test the species catalogue routes.
     *
     * These are the only routes that answer from another container: the
     * catalogue is the field guiding service's, and Chisimba proxies to it.
     * So what is asserted here is the proxy, not the catalogue — that the
     * envelope survives the trip, that an anonymous request never reaches the
     * field service, that the caller's own token is what is forwarded, that a
     * code the catalogue does not hold is answered as a 404 rather than as a
     * blank page, and that a refusal is JSON with the catalogue's own reason.
     *
     * The authenticated assertions need a working credential. Where the
     * installation has none available they are reported as skipped, not
     * passed: a test that cannot run is not a test that succeeded.
     */
    private function testSpeciesCatalogue(): void
    {
        echo "\n--- Species Catalogue Endpoints ---\n";

        // The anonymous case needs no credential and must hold everywhere.
        $this->test('GET /api/v1/species without auth does not reach the catalogue', function() {
            $response = $this->httpRequest('GET', '/species');
            $this->assertStatus($response, 401);
        });

        $this->test('GET /api/v1/species/{code} without auth does not reach the catalogue', function() {
            $response = $this->httpRequest('GET', '/species/LEOP');
            $this->assertStatus($response, 401);
        });

        $token = $this->loginToken();

        if ($token === null) {
            $this->skip('species catalogue contents (no credential available on this installation)');
            $this->skip('species by code (no credential available on this installation)');
            $this->skip('species search is passed through to the catalogue (no credential available on this installation)');
            $this->skip('unknown species code is a 404 the catalogue explained (no credential available on this installation)');
            return;
        }

        $this->test('species catalogue contents', function() use ($token) {
            $response = $this->httpRequestWithToken('GET', '/species', $token);
            $this->assertStatus($response, 200);
            $this->assertJsonEnvelope($response);

            $data = $response['body']['data'] ?? $response['body'];
            $this->assertIsArray($data);
            $this->assertTrue(isset($data['species']), 'data must carry a species key');
            $this->assertIsArray($data['species']);

            // A null here is the failure mode this route exists to stop: a
            // device that cannot tell an empty catalogue from an unexpected
            // response shows an empty picker.
            $this->assertTrue($data['species'] !== null, 'species must never be null');

            $this->assertTrue(
                count($data['species']) > 0,
                'the catalogue must not be empty, or the field service is unreachable and said so'
            );

            $first = $data['species'][0];
            $this->assertIsArray($first);
            $this->assertHasKeys($first, ['code', 'common_name']);
        });

        $this->test('species by code', function() use ($token) {
            $response = $this->httpRequestWithToken('GET', '/species/LEOP', $token);
            $this->assertStatus($response, 200);
            $this->assertJsonEnvelope($response);

            $data = $response['body']['data'] ?? $response['body'];
            $this->assertIsArray($data);
            $this->assertEquals('LEOP', $data['code'] ?? null);
        });

        $this->test('species search is passed through to the catalogue', function() use ($token) {
            $searched = $this->httpRequestWithToken('GET', '/species?q=eagle', $token);
            $this->assertStatus($searched, 200);
            $searchedData = $searched['body']['data'] ?? $searched['body'];
            $this->assertTrue(
                !empty($searchedData['species']) && count($searchedData['species']) < 124,
                'a search must narrow the catalogue rather than return all of it, or the proxy dropped the query'
            );
        });

        $this->test('unknown species code is a 404 the catalogue explained', function() use ($token) {
            $response = $this->httpRequestWithToken('GET', '/species/ZZZZ', $token);
            $this->assertStatus($response, 404);
            $this->assertJsonEnvelope($response);

            $code = $response['body']['error']['code'] ?? null;
            $this->assertTrue(
                is_string($code) && $code !== '',
                'the catalogue explains a refusal in its own code, and a blank or HTML answer hides it'
            );
            $this->assertTrue($code !== 'UPSTREAM_UNREADABLE',
                "a 404 with an unreadable body means the catalogue answered in HTML, which is a misread client-visible");
        });
    }

    /**
     * The outings and competencies lists go through the same proxy.
     *
     * Both are reference data a field card needs alongside the species
     * catalogue, and the device asks Chisimba for all three in one refresh.
     * Outings are the interesting case: the field service nests its list one
     * level deeper under "data" than the other catalogues do, and a proxy that
     * forwarded that shape unchanged would answer 200 with a list the device
     * cannot find. So the assertions here are deliberately about the shape as
     * well as the status — a list that arrives empty because it was wrapped
     * wrongly is indistinguishable, to the device, from a reserve with no
     * outings.
     *
     * Both endpoints are scoped to the caller, so the empty-list case is
     * legitimate and is asserted as such rather than requiring content.
     */
    private function testCatalogueProxies(): void
    {
        echo "\n--- Outings and Competency Proxies ---\n";

        // Anonymous: the proxy must not reach the field service at all, and
        // must answer as JSON rather than letting the request through.
        foreach (['/outings', '/competencies'] as $path) {
            $this->test("GET /api/v1{$path} without auth does not reach the field service", function() use ($path) {
                $response = $this->httpRequest('GET', $path);
                $this->assertStatus($response, 401);
            });
        }

        $token = $this->loginToken();

        if ($token === null) {
            $this->skip('outings list (no credential available on this installation)');
            $this->skip('competencies list (no credential available on this installation)');
            return;
        }

        $this->test('outings list answers with the key a device reads', function() use ($token) {
            $response = $this->httpRequestWithToken('GET', '/outings', $token);
            $this->assertStatus($response, 200);
            $this->assertJsonEnvelope($response);

            $data = $response['body']['data'] ?? $response['body'];
            $this->assertIsArray($data);
            $this->assertTrue(isset($data['outings']), 'data must carry an outings key');
            $this->assertTrue($data['outings'] !== null, 'outings must never be null');
            $this->assertIsArray($data['outings']);

            // The list may legitimately be empty, but a row that does exist
            // must survive the wrap intact. An installation with no outings
            // passes on the key assertions above; one with rows is caught here.
            foreach ($data['outings'] as $outing) {
                $this->assertIsArray($outing);
                $this->assertHasKeys($outing, ['id', 'kind', 'status']);
            }
        });

        $this->test('competencies list answers with the key a device reads', function() use ($token) {
            $response = $this->httpRequestWithToken('GET', '/competencies', $token);
            $this->assertStatus($response, 200);
            $this->assertJsonEnvelope($response);

            $data = $response['body']['data'] ?? $response['body'];
            $this->assertIsArray($data);
            $this->assertTrue(isset($data['competencies']), 'data must carry a competencies key');
            $this->assertTrue($data['competencies'] !== null, 'competencies must never be null');
            $this->assertIsArray($data['competencies']);

            foreach ($data['competencies'] as $competency) {
                $this->assertIsArray($competency);
                $this->assertHasKeys($competency, ['code', 'name']);
            }
        });
    }

    /**
     * The write path, through the proxy.
     *
     * These assert the proxy's own job, not the field service's. What the proxy
     * owes a device is that the batch arrives and that the answer comes back in
     * a form the device can read — so the assertions are on routing, on the
     * envelope, and on the upstream's own reason reaching the caller rather
     * than one of the proxy's.
     *
     * The push assertion deliberately sends an empty batch. That makes the
     * field service answer with its own 400 and a code of its own, which proves
     * the whole POST path — method, body, headers, response — without writing a
     * row that would then sit in the database forever with no delete API to
     * take it back out.
     */
    private function testSyncProxies(): void
    {
        echo "\n--- Sync Proxies ---\n";

        foreach (['push', 'pull'] as $action) {
            $this->test("POST /api/v1/sync/{$action} without auth does not reach the field service", function() use ($action) {
                $response = $this->httpRequest('POST', "/sync/{$action}", []);
                $this->assertStatus($response, 401);
            });
        }

        $token = $this->loginToken();

        if ($token === null) {
            $this->skip('sync push proxy (no credential available on this installation)');
            $this->skip('sync pull proxy (no credential available on this installation)');
            return;
        }

        // An empty batch is refused by the field service itself, with its own
        // code. The proxy must pass that reason through rather than replacing
        // it with one of its own: a device branches on the code, and
        // UPSTREAM_UNAVAILABLE would send it looking for a network problem that
        // does not exist.
        $this->test('a refused push carries the upstream reason, not the proxy\'s', function() use ($token) {
            $response = $this->httpRequestWithToken('POST', '/sync/push', $token, '{"operations":[]}');
            $this->assertJsonEnvelope($response);

            $error = $response['body']['error'] ?? null;
            $code = is_string($error) ? $error : ($error['code'] ?? null);

            $this->assertTrue(
                $code !== null && $code !== '',
                'a refused push must answer with a code, so a device can tell why'
            );
            $this->assertTrue(
                $code !== 'UPSTREAM_UNAVAILABLE',
                'the field service answered, so this is not a transport failure'
            );
            $this->assertTrue(
                $code !== 'UPSTREAM_UNREADABLE',
                'the answer was read, so this is not an unreadable body'
            );
        });

        $this->test('a pull answers in the envelope a device reads', function() use ($token) {
            $response = $this->httpRequestWithToken('POST', '/sync/pull', $token, '{}');
            $this->assertStatus($response, 200);
            $this->assertJsonEnvelope($response);

            $data = $response['body']['data'] ?? $response['body'];
            $this->assertIsArray($data);
            // The page's own fields, whatever else the service adds. Status is
            // the one that says whether this is a page of changes or an
            // instruction to start over.
            $this->assertHasKeys($data, ['status']);
        });
    }

    /**
     * Sign in with the test credential, if this installation has one.
     *
     * The credential is the one the e2e harness uses. Where the installation
     * has no such account, or the password has been changed, this returns
     * null and the catalogue assertions are skipped — see testSpeciesCatalogue
     * and testCatalogueProxies.
     *
     * @return string|null The access token, or null when none could be obtained
     */
    private function loginToken(): ?string
    {
        // One session for the whole exchange: evidence in, login out.
        $this->sessionCookieFile = tempnam(sys_get_temp_dir(), 'restapi_session_');
        if ($this->sessionCookieFile === false) {
            return null;
        }

        $evidence = $this->httpRequest('GET', '/auth/token');
        if ($evidence['status'] !== 200) {
            $this->closeSession();
            return null;
        }

        $data = $evidence['body']['data'] ?? $evidence['body'];
        foreach (['csrf_token', 'issued_at', 'nonce', 'signature'] as $key) {
            if (!isset($data[$key])) {
                $this->closeSession();
                return null;
            }
        }

        // The evidence carries a minimum dwell time, and a login posted inside
        // it is refused as replay rather than as invalid credentials. Waiting
        // the advertised second is what the contract asks, so the credential
        // is judged on its own.
        sleep((int) ($data['minimum_seconds'] ?? 1) + 1);

        $login = $this->httpRequest('POST', '/auth/token', [
            'username' => 'admin',
            'password' => 'a',
            'csrf_token' => $data['csrf_token'],
            'abuse_issued_at' => $data['issued_at'],
            'abuse_nonce' => $data['nonce'],
            'abuse_signature' => $data['signature'],
        ]);

        $this->closeSession();

        // Minting a token is a creation, so the answer is 201. A deployment
        // that answers the same body with a plain 200 is not a failure either,
        // so both are accepted and neither is a silent skip because of a
        // status this check guessed at.
        if ($login['status'] !== 200 && $login['status'] !== 201) {
            return null;
        }

        $body = $login['body']['data'] ?? $login['body'];

        // The pair arrives nested under tokens, beside the user it belongs to.
        $tokens = $body['tokens'] ?? $body;
        foreach (['access_token', 'token'] as $key) {
            if (isset($tokens[$key]) && is_string($tokens[$key]) && $tokens[$key] !== '') {
                return $tokens[$key];
            }
        }

        return null;
    }

    /**
     * Close and remove the session cookie jar.
     *
     * Called on every path out of loginToken(), including the successful one:
     * the authenticated species requests carry the bearer token, not the
     * session, and a cookie jar left on disk is a credential left in /tmp.
     */
    private function closeSession(): void
    {
        if ($this->sessionCookieFile !== null && file_exists($this->sessionCookieFile)) {
            unlink($this->sessionCookieFile);
        }
        $this->sessionCookieFile = null;
    }

    /**
     * Make an authenticated HTTP request, optionally with a body.
     *
     * Carries the caller's own bearer token, which is what the species proxy
     * forwards to the catalogue. The body goes out as the raw JSON string it is
     * given — the same bytes a device would send — because the proxy under test
     * forwards what it receives rather than re-encoding it.
     *
     * @param string $method HTTP method
     * @param string $path Path under the base URL
     * @param string $token Bearer token
     * @param string|null $body Raw JSON body, or null to send none
     * @return array Response with status, decoded body and raw body
     */
    private function httpRequestWithToken(string $method, string $path, string $token, ?string $body = null): array
    {
        $url = $this->baseUrl . $path;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL error: $error");
        }

        return [
            'status' => $status,
            'body' => json_decode($result, true) ?? [],
            'raw' => $result
        ];
    }

    /**
     * Assert the response body is a JSON object, not a page.
     *
     * The raw body is checked, because a body that is not JSON decodes to an
     * empty array and would otherwise satisfy every "has keys" assertion by
     * having nothing to be missing. An HTML error page from an upstream fault
     * is exactly what this route must never hand a device.
     *
     * @param array $response The response to inspect
     */
    private function assertJsonEnvelope(array $response): void
    {
        $raw = trim((string) ($response['raw'] ?? ''));
        $this->assertTrue(
            $raw !== '' && $raw[0] === '{',
            'the body must be a JSON object, not a page or an empty reply'
        );
        $this->assertTrue(
            isset($response['body']['data']) || isset($response['body']['error']),
            'the body must carry a data or error envelope'
        );
    }

    /**
     * Report a test that could not run.
     *
     * Recorded as skipped rather than passed, so the summary says how much
     * was actually proven.
     *
     * @param string $name What was not run
     */
    private function skip(string $name): void
    {
        $this->testResults[] = ['name' => $name, 'passed' => false, 'skipped' => true, 'error' => null];
        echo "⊘ $name (skipped)\n";
    }

    /**
     * Make HTTP request
     */
    private function httpRequest(string $method, string $path, array $data = []): array
    {
        $url = $this->baseUrl . $path;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        // Carry the session when one is open. Without this the evidence this
        // request fetched and the login that posts it back are two different
        // conversations, and the evidence refuses the second one.
        if ($this->sessionCookieFile !== null) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->sessionCookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->sessionCookieFile);
        }

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL error: $error");
        }
        
        return [
            'status' => $status,
            'body' => json_decode($result, true) ?? [],
            'raw' => $result
        ];
    }

    /**
     * Run a single test
     */
    private function test(string $name, callable $fn): void
    {
        try {
            $fn();
            $this->testResults[] = ['name' => $name, 'passed' => true, 'error' => null];
            echo "✓ $name\n";
        } catch (Throwable $e) {
            $this->testResults[] = ['name' => $name, 'passed' => false, 'error' => $e->getMessage()];
            echo "✗ $name: {$e->getMessage()}\n";
        }
    }

    /**
     * Assert HTTP status
     */
    private function assertStatus(array $response, int $expectedStatus): void
    {
        if ($response['status'] !== $expectedStatus) {
            throw new Exception("Expected status $expectedStatus, got {$response['status']}: " . json_encode($response['body']));
        }
    }

    /**
     * Assert array has keys
     */
    private function assertHasKeys(array $array, array $keys): void
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array)) {
                throw new Exception("Missing required key: $key");
            }
        }
    }

    /**
     * Assert value is string
     */
    private function assertIsString($value): void
    {
        if (!is_string($value)) {
            throw new Exception("Expected string, got " . gettype($value));
        }
    }

    /**
     * Assert value is int
     */
    private function assertIsInt($value): void
    {
        if (!is_int($value)) {
            throw new Exception("Expected int, got " . gettype($value));
        }
    }

    /**
     * Assert value is array
     */
    private function assertIsArray($value): void
    {
        if (!is_array($value)) {
            throw new Exception("Expected array, got " . gettype($value));
        }
    }

    /**
     * Assert equality
     */
    private function assertEquals($expected, $actual): void
    {
        if ($expected !== $actual) {
            throw new Exception("Expected '$expected', got '$actual'");
        }
    }

    /**
     * Assert boolean true
     */
    private function assertTrue($value, string $message = ''): void
    {
        if ($value !== true) {
            throw new Exception($message ?: "Expected true, got " . var_export($value, true));
        }
    }

    /**
     * Print test summary
     */
    private function printSummary(): void
    {
        $total = count($this->testResults);
        $passed = array_reduce($this->testResults, fn($c, $r) => $c + ($r['passed'] ? 1 : 0), 0);
        $skipped = array_reduce($this->testResults, fn($c, $r) => $c + (!empty($r['skipped']) ? 1 : 0), 0);
        $failed = $total - $passed - $skipped;

        echo "\n=== Test Summary ===\n";
        echo "Total: $total\n";
        echo "Passed: $passed\n";
        echo "Skipped: $skipped\n";
        echo "Failed: $failed\n";

        if ($failed > 0) {
            echo "\nFailed tests:\n";
            foreach ($this->testResults as $result) {
                if (!$result['passed'] && empty($result['skipped'])) {
                    echo "  - {$result['name']}: {$result['error']}\n";
                }
            }
            exit(1);
        }

        echo "\nAll tests passed!\n";
        exit(0);
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $test = new RestApiContractTest();
    $test->runAllTests();
}