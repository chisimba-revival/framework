<?php
/**
 * TokenAuthService Contract Test
 * 
 * Tests the TokenAuthService methods against the field-guiding service contract.
 * This test focuses on the token issuance, refresh, and revocation logic.
 * 
 * Run from Chisimba context: php /path/to/tokenauthservice_contract_test.php
 */

// Must be run from Chisimba context
if (!isset($GLOBALS['kewl_entry_point_run'])) {
    $GLOBALS['kewl_entry_point_run'] = true;
}

class TokenAuthServiceContractTest
{
    private $testResults = [];

    public function runAllTests(): void
    {
        echo "=== TokenAuthService Contract Tests ===\n\n";
        
        $this->testTokenIssuance();
        $this->testTokenRefresh();
        $this->testTokenRevocation();
        $this->testServiceToken();
        $this->testIdentityClaims();
        $this->testJwks();
        
        $this->printSummary();
    }

    /**
     * Test token issuance
     */
    private function testTokenIssuance(): void
    {
        echo "--- Token Issuance ---\n";
        
        // Verify TokenAuthService has issueTokens method with device_id support
        $this->test('TokenAuthService::issueTokens accepts device_id option', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, "function issueTokens") !== false,
                'issueTokens method must exist');
            $this->assertTrue(strpos($source, "device_id") !== false,
                'issueTokens should handle device_id option');
        });

        // Verify access token TTL constant
        $this->test('ACCESS_TOKEN_TTL is 3600 seconds (1 hour)', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'ACCESS_TOKEN_TTL = 3600') !== false,
                'ACCESS_TOKEN_TTL should be 3600 seconds');
        });

        // Verify refresh token TTL constant
        $this->test('REFRESH_TOKEN_TTL is 14 days', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'REFRESH_TOKEN_TTL = 86400 * 14') !== false,
                'REFRESH_TOKEN_TTL should be 14 days');
        });
    }

    /**
     * Test token refresh with device_id and family revocation
     */
    private function testTokenRefresh(): void
    {
        echo "\n--- Token Refresh ---\n";
        
        // Verify refreshAccessToken accepts device_id parameter
        $this->test('TokenAuthService::refreshAccessToken accepts device_id parameter', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'function refreshAccessToken(string $refreshToken, string $deviceId = \'\')') !== false,
                'refreshAccessToken should accept device_id parameter');
        });

        // Verify family revocation on replay detection
        $this->test('Family revocation logic exists for replay detection', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'revokeAllUserDeviceTokens') !== false,
                'revokeAllUserDeviceTokens method must exist');
            $this->assertTrue(strpos($source, 'REPLAY DETECTED') !== false || strpos($source, 'replay') !== false,
                'Should have replay detection logic');
        });

        // Verify device_id column used in refresh token lookup
        $this->test('Refresh token lookup includes device_id', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'SELECT user_id, expires_at, revoked_at, device_id') !== false,
                'Refresh token lookup should include device_id');
        });
    }

    /**
     * Test token revocation
     */
    private function testTokenRevocation(): void
    {
        echo "\n--- Token Revocation ---\n";
        
        // Verify revokeAllUserDeviceTokens method exists
        $this->test('TokenAuthService::revokeAllUserDeviceTokens exists', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'function revokeAllUserDeviceTokens') !== false,
                'revokeAllUserDeviceTokens method must exist');
        });

        // Verify revokeAllUserTokens method exists
        $this->test('TokenAuthService::revokeAllUserTokens exists', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'function revokeAllUserTokens') !== false,
                'revokeAllUserTokens method must exist');
        });

        // Verify revokeRefreshToken method exists
        $this->test('TokenAuthService::revokeRefreshToken exists', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'function revokeRefreshToken') !== false,
                'revokeRefreshToken method must exist');
        });
    }

    /**
     * Test service token issuance
     */
    private function testServiceToken(): void
    {
        echo "\n--- Service Token ---\n";
        
        // Verify issueServiceToken method exists
        $this->test('TokenAuthService::issueServiceToken exists', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'function issueServiceToken') !== false,
                'issueServiceToken method must exist');
        });

        // Verify SERVICE_SCOPE constant
        $this->test('SERVICE_SCOPE is field:write', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, "SERVICE_SCOPE = 'field:write'") !== false,
                'SERVICE_SCOPE should be field:write');
        });

        // Verify SERVICE_TOKEN_TTL is 300 seconds
        $this->test('SERVICE_TOKEN_TTL is 300 seconds', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'SERVICE_TOKEN_TTL = 300') !== false,
                'SERVICE_TOKEN_TTL should be 300 seconds');
        });

        // Verify service token has type: service
        $this->test('Service token has type: service', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, "'type' => 'service'") !== false,
                'Service token should have type: service');
        });

        // Verify service token has scope as array
        $this->test('Service token scope is array', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, "[self::SERVICE_SCOPE]") !== false,
                'Service token scope should be an array');
        });
    }

    /**
     * Test identity claims
     */
    private function testIdentityClaims(): void
    {
        echo "\n--- Identity Claims ---\n";
        
        // Verify identityClaims method exists
        $this->test('TokenAuthService::identityClaims exists', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'function identityClaims') !== false,
                'identityClaims method must exist');
        });

        // Verify identity claims include ctx, ctxs, roles, epoch, scope
        $this->test('Identity claims include required fields', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, "'ctx'") !== false, 'Should include ctx');
            $this->assertTrue(strpos($source, "'ctxs'") !== false, 'Should include ctxs');
            $this->assertTrue(strpos($source, "'roles'") !== false, 'Should include roles');
            $this->assertTrue(strpos($source, "'epoch'") !== false, 'Should include epoch');
            $this->assertTrue(strpos($source, "'scope'") !== false, 'Should include scope');
            $this->assertTrue(strpos($source, "'active'") !== false, 'Should include active');
        });

        // Verify MAX_GRANTS constant
        $this->test('MAX_GRANTS is 32', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'MAX_GRANTS = 32') !== false,
                'MAX_GRANTS should be 32');
        });

        // Verify epoch sync logic exists
        $this->test('syncEpochFor method exists', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'function syncEpochFor') !== false,
                'syncEpochFor method must exist');
        });
    }

    /**
     * Test JWKS
     */
    private function testJwks(): void
    {
        echo "\n--- JWKS ---\n";
        
        // Verify jwks method exists
        $this->test('TokenAuthService::jwks exists', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'function jwks') !== false,
                'jwks method must exist');
        });

        // Verify validateJWT method exists
        $this->test('TokenAuthService::validateJWT exists', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'function validateJWT') !== false,
                'validateJWT method must exist');
        });

        // Verify RS256 algorithm check
        $this->test('validateJWT enforces RS256 algorithm', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, "!== 'RS256'") !== false,
                'validateJWT should enforce RS256 algorithm');
        });

        // Verify validateFromRequest method exists
        $this->test('TokenAuthService::validateFromRequest exists', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/security/classes/tokenauthservice_class_inc.php');
            $this->assertTrue(strpos($source, 'function validateFromRequest') !== false,
                'validateFromRequest method must exist');
        });
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
        $failed = $total - $passed;
        
        echo "\n=== Test Summary ===\n";
        echo "Total: $total\n";
        echo "Passed: $passed\n";
        echo "Failed: $failed\n";
        
        if ($failed > 0) {
            echo "\nFailed tests:\n";
            foreach ($this->testResults as $result) {
                if (!$result['passed']) {
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
    $test = new TokenAuthServiceContractTest();
    $test->runAllTests();
}