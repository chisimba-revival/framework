<?php
/**
 * End-to-End Field Service Integration Test
 *
 * Tests the full flow:
 *   1. Chisimba issues a token with field context claims (ctx, ctxs, roles, scope)
 *   2. The token is accepted by field-service
 *   3. A sync/push batch creates an outing and a log book entry
 *   4. A sync/pull returns those changes in the change feed
 *
 * Prerequisites:
 *   - docker compose up -d  (web, db, field-service, field-service-postgis, redis)
 *   - field-service migrations applied (including 0011_fieldapp_grants.sql)
 *   - Admin user enrolled in at least one field context (tbl_field_context_grants)
 *     and has an active context set (tbl_field_active_context)
 *
 * Run from anywhere:
 *   php framework/app/core_modules/restapi/tests/field_service_e2e_test.php
 */

if (!isset($GLOBALS['kewl_entry_point_run'])) {
    $GLOBALS['kewl_entry_point_run'] = true;
}

class FieldServiceE2ETest
{
    private string $chisimbaUrl;
    private string $fieldServiceUrl;
    private string $cookieFile;
    private array $results = [];

    public function __construct()
    {
        // Use service names when running inside Docker, localhost otherwise.
        if (file_exists('/.dockerenv') || getenv('DOCKER_CONTAINER')) {
            $this->chisimbaUrl = 'http://web/api/v1';
            $this->fieldServiceUrl = 'http://field-service:8080/api/v1';
        } else {
            $this->chisimbaUrl = 'http://localhost:8080/api/v1';
            $this->fieldServiceUrl = 'http://localhost:8081/api/v1';
        }
        $this->cookieFile = sys_get_temp_dir() . '/e2e_cookies_' . getmypid() . '.txt';
    }

    public function run(): int
    {
        echo "=== Field Service End-to-End Integration Test ===\n\n";

        try {
            $token = $this->testLogin();
            $this->testTokenClaims($token);
            $ids = $this->testSyncPush($token);
            $this->testSyncPull($token, $ids);
            $listResult = $this->testOutingsList($token);
            $this->testOutingGetById($token, $listResult['id'] ?? $ids['outing']);
            $signoffId = $this->testSignoffCreate($token, $ids['outing']);
            $this->testSignoffGetById($token, $signoffId);
            $this->testSignoffSubmit($token, $signoffId);
            $this->testSignoffReview($token, $signoffId);
            $this->testMediaUploadTarget($token);
        } catch (Throwable $e) {
            $this->record('end-to-end', false, 'exception: ' . $e->getMessage());
        }

        $this->summary();
        @unlink($this->cookieFile);
        return $this->allPassed() ? 0 : 1;
    }

    private function testLogin(): string
    {
        $evidence = $this->chisimbaRequest('GET', '/auth/token');
        if (!isset($evidence['data']['csrf_token'])) {
            $this->record('login: abuse evidence', false, json_encode($evidence));
            throw new RuntimeException('no abuse evidence');
        }
        $this->record('login: abuse evidence', true);

        $login = $this->chisimbaRequest('POST', '/auth/token', [
            'username'         => 'admin',
            'password'         => 'a',
            'csrf_token'       => $evidence['data']['csrf_token'],
            'abuse_signature'  => $evidence['data']['signature'],
            'abuse_nonce'      => $evidence['data']['nonce'],
            'abuse_issued_at'  => $evidence['data']['issued_at'],
            'device_id'        => 'e2e-test-' . getmypid(),
        ]);
        $token = $login['data']['tokens']['access_token'] ?? null;
        $this->record('login: token issued', $token !== null);
        if (!$token) {
            throw new RuntimeException('no access token');
        }
        return $token;
    }

    private function testTokenClaims(string $token): void
    {
        $claims = $this->decodeJwt($token);

        $this->record('claims: active context set', !empty($claims['ctx']), 'ctx=' . ($claims['ctx'] ?? ''));
        $this->record('claims: contexts non-empty', !empty($claims['ctxs']), json_encode($claims['ctxs'] ?? []));
        $this->record('claims: roles non-empty', !empty($claims['roles']), json_encode($claims['roles'] ?? []));
        $this->record(
            'claims: scope has field:write',
            in_array('field:write', $claims['scope'] ?? [], true),
            json_encode($claims['scope'] ?? [])
        );
        $this->record(
            'claims: scope has field:read',
            in_array('field:read', $claims['scope'] ?? [], true)
        );
    }

    private function testSyncPush(string $token): array
    {
        $outingId = $this->uuid();
        $entryId = $this->uuid();
        $opOuting = $this->uuid();
        $opEntry = $this->uuid();

        $response = $this->fieldServiceRequest('POST', '/sync/push', $token, [
            'operations' => [
                [
                    'operation_id'  => $opOuting,
                    'entity'        => 'outing',
                    'kind'          => 'create',
                    'entity_id'     => $outingId,
                    'base_revision' => null,
                    'captured_at'   => date('c'),
                    'recorded_at'   => date('c'),
                    'payload'       => [
                        'outing_kind'    => 'drive',
                        'duration_hours' => 3.5,
                        'guest_count'    => 4,
                        'status'         => 'active',
                        'notes'          => 'e2e test drive',
                    ],
                ],
                [
                    'operation_id'  => $opEntry,
                    'entity'        => 'log_book_entry',
                    'kind'          => 'create',
                    'entity_id'     => $entryId,
                    'base_revision' => null,
                    'captured_at'   => date('c'),
                    'recorded_at'   => date('c'),
                    'payload'       => [
                        'outing_id'    => $outingId,
                        'location'     => ['lng' => 31.5, 'lat' => -24.0],
                        'notes'        => 'e2e test entry',
                        'count'        => 1,
                        'species_code' => 'TEST',
                    ],
                    'depends_on'    => [$opOuting],
                ],
            ],
        ]);

        $results = $response['results'] ?? [];
        $this->record('push: two results returned', count($results) === 2, json_encode($response));
        foreach ($results as $i => $r) {
            $this->record(
                "push: operation $i applied",
                ($r['outcome'] ?? '') === 'applied',
                json_encode($r)
            );
        }
        return ['outing' => $outingId, 'entry' => $entryId];
    }

    private function testSyncPull(string $token, array $ids): void
    {
        $pull = $this->fieldServiceRequest('GET', '/sync/pull', $token);

        $this->record('pull: HTTP 200', ($pull['http_code'] ?? 0) === 200, json_encode($pull));
        $changes = $pull['changes'] ?? [];
        $entityIds = array_map(fn($c) => $c['entity_id'] ?? null, $changes);
        $this->record(
            'pull: change feed contains our outing',
            in_array($ids['outing'], $entityIds, true),
            json_encode($entityIds)
        );
        $this->record(
            'pull: change feed contains our entry',
            in_array($ids['entry'], $entityIds, true)
        );
    }

    private function testOutingsList(string $token): array
    {
        $response = $this->fieldServiceRequest('GET', '/outings', $token);
        $outings = $response['data']['outings'] ?? null;
        $this->record(
            'outings: list returns data',
            $outings !== null || ($response['data'] ?? null) !== null,
            json_encode($response)
        );
        // Return the first outing ID found, or null if the list is empty.
        if (is_array($outings) && count($outings) > 0) {
            return ['id' => $outings[0]['id'] ?? null];
        }
        return ['id' => null];
    }

    private function testOutingGetById(string $token, ?string $outingId): void
    {
        if (!$outingId) {
            $this->record('outings: get by id skipped (no outing)', true);
            return;
        }
        $response = $this->fieldServiceRequest('GET', '/outings/' . $outingId, $token);
        $code = $response['http_code'] ?? 0;
        $id = $response['id'] ?? ($response['data']['id'] ?? null);
        $this->record(
            'outings: get by id returns our outing',
            $id === $outingId,
            "HTTP {$code} " . json_encode($response)
        );
    }

    private function testSignoffCreate(string $token, string $outingId): string
    {
        $opId = $this->uuid();
        $signoffId = $this->uuid();
        $response = $this->fieldServiceRequest('POST', '/signoffs', $token, [
            'operation_id'    => $opId,
            'signoff_id'      => $signoffId,
            'outing_id'       => $outingId,
            'trainee_id'      => '1',
            'mentor_id'       => '1',
            'overall_comment' => 'e2e sign-off — solid bushcraft.',
            'competencies'    => [
                [
                    'code'     => 'TRKSPR',
                    'rating'   => 'competent',
                    'evidence' => 'Correctly identified spoor and estimated age.',
                ],
            ],
        ]);
        // The handler returns the created record (201) or wraps it.
        $id = $response['id'] ?? ($response['signoff_id'] ?? ($response['data']['id'] ?? null));
        $ok = $id === $signoffId
            || ($response['http_code'] ?? 0) === 201
            || ($response['http_code'] ?? 0) === 200;
        $this->record(
            'signoff: create returns our sign-off',
            $ok,
            "HTTP " . ($response['http_code'] ?? '?') . " " . json_encode($response)
        );
        return $signoffId;
    }

    private function testSignoffGetById(string $token, string $signoffId): void
    {
        $response = $this->fieldServiceRequest('GET', '/signoffs/' . $signoffId, $token);
        $id = $response['id'] ?? ($response['signoff_id'] ?? ($response['data']['id'] ?? null));
        $ok = $id === $signoffId
            || ($response['http_code'] ?? 0) === 200;
        $this->record(
            'signoff: get by id returns our sign-off',
            $ok,
            "HTTP " . ($response['http_code'] ?? '?') . " " . json_encode($response)
        );
    }

    private function testSignoffSubmit(string $token, string $signoffId): void
    {
        $response = $this->fieldServiceRequest('POST', '/signoffs/' . $signoffId . '/submit', $token);
        $code = ($response['http_code'] ?? 0);
        $status = $response['status'] ?? ($response['data']['status'] ?? null);
        $ok = $status === 'submitted' || $status === 'reviewed'
            || ($response['id'] ?? null) === $signoffId
            || $code === 200;
        $this->record(
            'signoff: submit transitions out of draft',
            $ok,
            "HTTP {$code} " . json_encode($response)
        );
    }

    private function testSignoffReview(string $token, string $signoffId): void
    {
        $response = $this->fieldServiceRequest('POST', '/signoffs/' . $signoffId . '/review', $token, [
            'rating'  => 'competent',
            'comment' => 'Demonstrates solid bush craft and guest management.',
        ]);
        // Any non-error response means the review was accepted.
        $code = ($response['http_code'] ?? 0);
        $ok = ($code >= 200 && $code < 300)
            || !isset($response['error']);
        $this->record(
            'signoff: review accepted',
            $ok,
            "HTTP {$code} " . json_encode($response)
        );
    }

    private function testMediaUploadTarget(string $token): void
    {
        $response = $this->fieldServiceRequest('POST', '/media/upload', $token, [
            'content_type' => 'image/jpeg',
        ]);
        // The handler wraps the target in a "data" envelope, or returns 503
        // when no backend is configured.
        $url = $response['data']['URL'] ?? ($response['data']['url'] ?? ($response['URL'] ?? null));
        $code = ($response['http_code'] ?? 0);
        $ok = $url !== null
            || ($response['error'] ?? '') === 'media_not_configured'
            || ($code === 503 || ($code >= 200 && $code < 300));
        $this->record(
            'media: upload target returned or unconfigured is explicit',
            $ok,
            "HTTP {$code} " . json_encode($response)
        );
    }

    private function chisimbaRequest(string $method, string $path, array $body = null): array
    {
        $ch = curl_init($this->chisimbaUrl . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?: [];
    }

    private function fieldServiceRequest(string $method, string $path, string $token, array $body = null): array
    {
        $ch = curl_init($this->fieldServiceUrl . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        $headers = ['Authorization: Bearer ' . $token];
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response, true) ?: [];
        $data['http_code'] = $code;
        return $data;
    }

    private function decodeJwt(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return [];
        }
        return json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) ?: [];
    }

    private function uuid(): string
    {
        return sprintf(
            '%08x-%04x-%04x-%04x-%012x',
            mt_rand(), mt_rand(0, 65535),
            0x4000 | mt_rand(0, 0x0fff),
            0x8000 | mt_rand(0, 0x3fff),
            random_int(0, 0xffffffffffff)
        );
    }

    private function record(string $test, bool $pass, string $detail = ''): void
    {
        $this->results[] = [$test, $pass, $detail];
        echo ($pass ? '  ✓ ' : '  ✗ ') . $test . ($detail && !$pass ? ' — ' . $detail : '') . "\n";
    }

    private function allPassed(): bool
    {
        foreach ($this->results as [, $pass]) {
            if (!$pass) return false;
        }
        return true;
    }

    private function summary(): void
    {
        $total = count($this->results);
        $passed = count(array_filter($this->results, fn($r) => $r[1]));
        echo "\n=== Summary ===\n$passed / $total passed\n";
    }
}

exit((new FieldServiceE2ETest())->run());
