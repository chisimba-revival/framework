<?php
/**
 * RestController Routing Test
 * 
 * Tests the routing logic in RestController to ensure proper handler resolution
 * for the new endpoints specified in the field-guiding service contract.
 * 
 * Run from Chisimba context: php /path/to/restcontroller_routing_test.php
 */

// Must be run from Chisimba context
if (!isset($GLOBALS['kewl_entry_point_run'])) {
    $GLOBALS['kewl_entry_point_run'] = true;
}

class RestControllerRoutingTest
{
    private $testResults = [];

    public function runAllTests(): void
    {
        echo "=== RestController Routing Tests ===\n\n";
        
        $this->testAuthRoutes();
        $this->testUserRoutes();
        $this->testSpeciesRoutes();
        $this->testCatalogueProxyRoutes();
        $this->testSyncProxyRoutes();
        $this->testSpecialRoutes();
        
        $this->printSummary();
    }

    /**
     * Test auth resource routes
     */
    private function testAuthRoutes(): void
    {
        echo "--- Auth Resource Routes ---\n";
        
        // Test that new endpoint methods exist
        $this->test('Controller has GET_auth_token method', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/restapi/controller.php');
            $this->assertTrue(strpos($source, 'function GET_auth_token') !== false,
                'GET_auth_token method must exist for GET /api/v1/auth/token');
        });

        $this->test('Controller has POST_auth_token method', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/restapi/controller.php');
            $this->assertTrue(strpos($source, 'function POST_auth_token') !== false,
                'POST_auth_token method must exist for POST /api/v1/auth/token');
        });

        $this->test('Controller has POST_auth_refresh with device_id', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/restapi/controller.php');
            $this->assertTrue(strpos($source, 'function POST_auth_refresh') !== false,
                'POST_auth_refresh method must exist');
            $this->assertTrue(strpos($source, 'device_id') !== false,
                'POST_auth_refresh should validate device_id');
        });

        // Test backward compatibility methods
        $this->test('Controller has GET_auth_login (deprecated)', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/restapi/controller.php');
            $this->assertTrue(strpos($source, 'function GET_auth_login') !== false,
                'GET_auth_login method must exist for backward compatibility');
        });

        $this->test('Controller has POST_auth_login (deprecated)', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/restapi/controller.php');
            $this->assertTrue(strpos($source, 'function POST_auth_login') !== false,
                'POST_auth_login method must exist for backward compatibility');
        });

        $this->test('Controller has GET_auth_me (deprecated)', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/restapi/controller.php');
            $this->assertTrue(strpos($source, 'function GET_auth_me') !== false,
                'GET_auth_me method must exist for backward compatibility');
        });

        $this->test('Controller has GET_auth_jwks', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/restapi/controller.php');
            $this->assertTrue(strpos($source, 'function GET_auth_jwks') !== false,
                'GET_auth_jwks method must exist');
        });

        $this->test('Controller has POST_auth_logout', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/restapi/controller.php');
            $this->assertTrue(strpos($source, 'function POST_auth_logout') !== false,
                'POST_auth_logout method must exist');
        });
    }

    /**
     * Test user resource routes
     */
    private function testUserRoutes(): void
    {
        echo "\n--- User Resource Routes ---\n";
        
        $this->test('Controller has GET_user_profile method', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/restapi/controller.php');
            $this->assertTrue(strpos($source, 'function GET_user_profile') !== false,
                'GET_user_profile method must exist for GET /api/v1/user/profile');
        });

        $this->test('GET_user_profile returns proper profile structure', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/restapi/controller.php');
            $this->assertTrue(strpos($source, 'active_context') !== false,
                'Profile should include active_context');
            $this->assertTrue(strpos($source, 'context_grants') !== false,
                'Profile should include context_grants');
            $this->assertTrue(strpos($source, 'roles') !== false,
                'Profile should include roles');
            $this->assertTrue(strpos($source, 'scopes') !== false,
                'Profile should include scopes');
            $this->assertTrue(strpos($source, 'identity_epoch') !== false,
                'Profile should include identity_epoch');
            $this->assertTrue(strpos($source, 'account_active') !== false,
                'Profile should include account_active');
        });

        // Test existing user methods still exist
        $this->test('Controller has GET_users method', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/restapi/controller.php');
            $this->assertTrue(strpos($source, 'function GET_users') !== false,
                'GET_users method must exist');
        });

        $this->test('Controller has GET_users_by_id method', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/restapi/controller.php');
            $this->assertTrue(strpos($source, 'function GET_users_by_id') !== false,
                'GET_users_by_id method must exist');
        });
    }

    /**
     * Test species resource routes
     *
     * The species catalogue is the only thing this module answers from another
     * container, so its handlers are asserted as source here rather than being
     * left to the live contract test: a deleted handler is a deleted route, and
     * that is worth catching without a field service running.
     *
     * Unlike the groups above, which read one hardcoded installed path, these
     * resolve the controller through the layouts an installation can have. A
     * read that finds nothing is reported as a failed assertion rather than
     * passed: a source check over an empty string proves nothing.
     */
    private function testSpeciesRoutes(): void
    {
        echo "\n--- Species Resource Routes ---\n";

        $source = $this->controllerSource();

        $this->test('Controller has GET_species method', function() use ($source) {
            $this->assertSourceRead($source);
            $this->assertTrue(strpos($source, 'function GET_species') !== false,
                'GET_species method must exist for GET /api/v1/species');
        });

        $this->test('Controller has GET_species_by_id method', function() use ($source) {
            $this->assertSourceRead($source);
            $this->assertTrue(strpos($source, 'function GET_species_by_id') !== false,
                'GET_species_by_id method must exist for GET /api/v1/species/{code}');
        });

        $this->test('Species routes are answered by the field service proxy', function() use ($source) {
            $this->assertSourceRead($source);
            $this->assertTrue(strpos($source, 'function fetchFromFieldService') !== false,
                'fetchFromFieldService must exist: the catalogue is the field service\'s, and a route that answers it any other way would be a second catalogue free to disagree');
        });

        $this->test('Species routes require an authenticated caller', function() use ($source) {
            $this->assertSourceRead($source);
            $this->assertTrue(
                preg_match('/function GET_species\s*\(\s*\)[^{]*\{\s*\$this->requireAuth\(\)/s', $source) === 1,
                'GET_species must call requireAuth(): the catalogue is readable for the asking person, not for nobody'
            );
        });

        $this->test('The field service address is resolved by walking up, not hardcoded', function() use ($source) {
            $this->assertSourceRead($source);

            // The config file sits at <docroot>/config/restapi_inc.php, and how
            // many levels up that is depends on where the installation rooted
            // the framework: three in the source tree, where the module is
            // app/core_modules/<module>, and two in the assembled image, where
            // app/ IS the docroot. Hardcoding three silently ignored the
            // operator's setting in the assembled layout — the override looked
            // like it worked because the default happened to be right there
            // too, so nothing looked broken. A walk cannot make that mistake.
            $this->assertTrue(strpos($source, 'function fieldServiceUrl') !== false,
                'fieldServiceUrl must exist: it is where the catalogue address is resolved');
            $this->assertTrue(strpos($source, 'dirname(__DIR__, $levels)') !== false,
                'fieldServiceUrl must walk up levels to find config/restapi_inc.php rather than assuming one layout');
        });
    }

    /**
     * The other two catalogues answer through the same proxy.
     *
     * A field card asks Chisimba for species, outings and competencies in one
     * refresh, and the refresh does not finish until all three have answered.
     * A route that is missing therefore costs the caller the catalogues it did
     * serve: the outings and competencies lists used to 404, which failed the
     * whole reference refresh and left the species picker on its stale cache —
     * the very symptom these routes were added to stop, arriving by a
     * different door. So the existence of all three is asserted together.
     */
    private function testCatalogueProxyRoutes(): void
    {
        echo "\n--- Outings and Competency Proxy Routes ---\n";

        $source = $this->controllerSource();

        $this->test('Controller has GET_outings method', function() use ($source) {
            $this->assertSourceRead($source);
            $this->assertTrue(strpos($source, 'function GET_outings') !== false,
                'GET_outings method must exist for GET /api/v1/outings');
        });

        $this->test('Controller has GET_competencies method', function() use ($source) {
            $this->assertSourceRead($source);
            $this->assertTrue(strpos($source, 'function GET_competencies') !== false,
                'GET_competencies method must exist for GET /api/v1/competencies');
        });

        // The catalogues do not agree on a shape: the field service answers
        // species and competencies bare but nests outings under "data". The
        // list helper exists to lift that payload out before wrapping it once,
        // and without it the outings list arrives one level too deep and reads
        // as empty — a wrong answer rather than a refused one.
        $this->test('Catalogue lists are lifted out of the upstream envelope before wrapping', function() use ($source) {
            $this->assertSourceRead($source);
            $this->assertTrue(strpos($source, 'function catalogueList') !== false,
                'catalogueList must exist: it normalises the differing upstream shapes into one wrapper');
            $this->assertTrue(
                strpos($source, "\$upstream['data']") !== false,
                'catalogueList must lift the upstream data envelope off, or a nested catalogue reads as empty'
            );
        });

        $this->test('Catalogue routes require an authenticated caller', function() use ($source) {
            $this->assertSourceRead($source);
            $this->assertTrue(
                preg_match('/function GET_outings\s*\(\s*\)[^{]*\{\s*\$this->requireAuth\(\)/s', $source) === 1,
                'GET_outings must call requireAuth(): the list is the caller\'s own, scoped by the token forwarded upstream'
            );
            $this->assertTrue(
                preg_match('/function GET_competencies\s*\(\s*\)[^{]*\{\s*\$this->requireAuth\(\)/s', $source) === 1,
                'GET_competencies must call requireAuth()'
            );
        });
    }

    /**
     * The write path.
     *
     * Sync is the reason a proxy that only reads is not enough: a device that
     * cannot push holds its observations forever, and the badge beside the map
     * counts them the whole time. These assert the routing and the forwarding
     * shape rather than the outcome, because the outcome belongs to the field
     * service and the contract test covers that live.
     */
    private function testSyncProxyRoutes(): void
    {
        echo "\n--- Sync Proxy Routes ---\n";

        $source = $this->controllerSource();
        $routing = $this->routingSource();

        $this->test('Controller has POST_sync_push method', function() use ($source) {
            $this->assertSourceRead($source);
            $this->assertTrue(strpos($source, 'function POST_sync_push') !== false,
                'POST_sync_push method must exist for POST /api/v1/sync/push');
        });

        $this->test('Controller has POST_sync_pull method', function() use ($source) {
            $this->assertSourceRead($source);
            $this->assertTrue(strpos($source, 'function POST_sync_pull') !== false,
                'POST_sync_pull method must exist for POST /api/v1/sync/pull');
        });

        // /sync/push is an action, not an id. The generic convention resolves it
        // to POST_sync_by_id('push'), which names a record called "push" and
        // does not exist, so the sync branch is what makes the write path
        // reachable at all.
        $this->test('Sync routes resolve as actions rather than resource ids', function() use ($routing) {
            $this->assertSourceRead($routing);
            $this->assertTrue(strpos($routing, "\$resource === 'sync'") !== false,
                'resolveHandler needs a sync branch, or /sync/push resolves to POST_sync_by_id and answers 404');
        });

        // The batch is the caller's bytes. Decoding and re-encoding them in PHP
        // is where a key gets dropped, and the field service decodes strictly
        // enough that a dropped key is a change applied incompletely rather than
        // a refused one.
        $this->test('The batch is forwarded as it arrived', function() use ($source) {
            $this->assertSourceRead($source);
            $this->assertTrue(strpos($source, 'function rawRequestBody') !== false,
                'rawRequestBody must exist: the body is forwarded as bytes, not re-encoded through json_decode');
            $this->assertTrue(strpos($source, 'php://input') !== false,
                'rawRequestBody reads the raw stream; a decoded copy is not the request');
        });

        $this->test('Sync routes require an authenticated caller', function() use ($source) {
            $this->assertSourceRead($source);
            $this->assertTrue(
                preg_match('/function POST_sync_push\s*\(\s*\)[^{]*\{\s*\$this->requireAuth\(\)/s', $source) === 1,
                'POST_sync_push must call requireAuth()'
            );
            $this->assertTrue(
                preg_match('/function POST_sync_pull\s*\(\s*\)[^{]*\{\s*\$this->requireAuth\(\)/s', $source) === 1,
                'POST_sync_pull must call requireAuth()'
            );
        });
    }

    /**
     * Read the RestController base class from wherever this installation keeps it.
     *
     * @return string The routing source, empty when no candidate exists
     */
    private function routingSource(): string
    {
        foreach ([
            dirname(__DIR__, 2) . '/restapi/classes/restcontroller_class_inc.php',
            dirname(__DIR__, 2) . '/restapi/classes/restcontroller_class_inc.php',
            dirname(__DIR__) . '/classes/restcontroller_class_inc.php',
        ] as $path) {
            if (is_file($path)) {
                $source = file_get_contents($path);
                if ($source !== false) {
                    return $source;
                }
            }
        }

        return '';
    }

    /**
     * Read the restapi controller from wherever this installation keeps it.
     *
     * @return string The controller source, empty when no candidate exists
     */
    private function controllerSource(): string
    {
        foreach ([
            dirname(__DIR__, 2) . '/restapi/controller.php',
            dirname(__DIR__, 2) . '/restapi/controller.php',
            dirname(__DIR__) . '/controller.php',
        ] as $path) {
            if (is_file($path)) {
                $source = file_get_contents($path);
                if ($source !== false) {
                    return $source;
                }
            }
        }

        return '';
    }

    /**
     * Fail when the controller could not be read at all.
     *
     * @param string $source What controllerSource() returned
     */
    private function assertSourceRead(string $source): void
    {
        if ($source === '') {
            throw new Exception(
                'the restapi controller could not be read, so the assertions that follow would prove nothing'
            );
        }
    }

    /**
     * Test special routes in RestController base class
     */
    private function testSpecialRoutes(): void
    {
        echo "\n--- RestController Routing Logic ---\n";
        
        $this->test('RestController handles user/profile route', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/restapi/classes/restcontroller_class_inc.php');
            $this->assertTrue(strpos($source, '$resource === \'user\'') !== false,
                'RestController should have special handling for user resource');
            $this->assertTrue(strpos($source, 'Special handling for user resource') !== false,
                'Should have comment for user resource handling');
        });

        $this->test('RestController has resolveHandler method', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/restapi/classes/restcontroller_class_inc.php');
            $this->assertTrue(strpos($source, 'function resolveHandler') !== false,
                'RestController must have resolveHandler method');
        });

        $this->test('RestController parseRoute method exists', function() {
            $source = file_get_contents(dirname(__DIR__, 2) . '/restapi/classes/restcontroller_class_inc.php');
            $this->assertTrue(strpos($source, 'function parseRoute') !== false,
                'RestController must have parseRoute method');
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
    $test = new RestControllerRoutingTest();
    $test->runAllTests();
}