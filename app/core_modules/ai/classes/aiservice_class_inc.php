<?php
/**
 * Canonical application-facing AI service.
 *
 * The service owns provider execution and cross-domain audit metadata. Domain
 * modules remain responsible for task instructions, schemas and domain data.
 *
 * @category  Chisimba
 * @package   ai
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class aiservice extends dbTable
{
    public $objConfig;

    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init($tableName !== null ? $tableName : 'tbl_ai_requests', $pearDb, $errorCallback);
        $this->objConfig = $this->getObject('dbsysconfig', 'sysconfig');
    }

    /**
     * Execute one provider-neutral structured AI request.
     *
     * Required keys: consumer, task, instructions, input, schemaName, schema.
     * The service deliberately does not know the domain meaning of the task.
     */
    public function execute(array $request)
    {
        $normalised = $this->normalise($request);
        if (!$normalised['ok']) { return $normalised; }
        $request = $normalised['request'];
        $providerName = $this->providerName();
        $started = microtime(true);

        try {
            $provider = $this->getObject($providerName . 'provider', 'ai');
            $result = $provider->execute($request);
        } catch (Throwable $exception) {
            $result = array(
                'ok' => false,
                'provider' => $providerName,
                'error' => 'provider_exception',
                'detail' => $exception->getMessage(),
                'inputTokens' => 0,
                'outputTokens' => 0
            );
        }

        $duration = (int) round((microtime(true) - $started) * 1000);
        $this->recordAudit($request, $providerName, $result, $duration);
        $result['durationMs'] = $duration;
        return $result;
    }

    public function providerStatus()
    {
        $providerName = $this->providerName();
        try {
            $provider = $this->getObject($providerName . 'provider', 'ai');
            return $provider->status();
        } catch (Throwable $exception) {
            return array('provider' => $providerName, 'model' => '', 'configured' => false);
        }
    }

    public function usageSummary()
    {
        $rows = $this->getAll();
        $summary = array('requests' => 0, 'inputTokens' => 0, 'outputTokens' => 0);
        if (!is_array($rows)) { return $summary; }
        foreach ($rows as $row) {
            $summary['requests']++;
            $summary['inputTokens'] += isset($row['input_tokens']) ? (int) $row['input_tokens'] : 0;
            $summary['outputTokens'] += isset($row['output_tokens']) ? (int) $row['output_tokens'] : 0;
        }
        return $summary;
    }

    private function providerName()
    {
        $provider = strtolower(trim((string) $this->objConfig->getValue('AI_PROVIDER', 'ai')));
        return preg_match('/^[a-z][a-z0-9_]*$/', $provider) ? $provider : 'openai';
    }

    private function normalise(array $request)
    {
        $required = array('consumer', 'task', 'instructions', 'input', 'schemaName', 'schema');
        foreach ($required as $key) {
            if (!array_key_exists($key, $request)) {
                return array('ok' => false, 'error' => 'invalid_request', 'detail' => 'missing_' . $key);
            }
        }
        $consumer = strtolower(trim((string) $request['consumer']));
        $task = strtolower(trim((string) $request['task']));
        $schemaName = trim((string) $request['schemaName']);
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $consumer)
            || !preg_match('/^[a-z][a-z0-9_]*$/', $task)
            || !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $schemaName)
            || trim((string) $request['instructions']) === ''
            || !is_array($request['schema'])) {
            return array('ok' => false, 'error' => 'invalid_request', 'detail' => 'request_validation_failed');
        }
        $request['consumer'] = $consumer;
        $request['task'] = $task;
        $request['schemaName'] = $schemaName;
        $request['instructions'] = (string) $request['instructions'];
        $request['input'] = is_string($request['input']) ? $request['input'] : json_encode($request['input']);
        if (!is_string($request['input']) || $request['input'] === '') {
            return array('ok' => false, 'error' => 'invalid_request', 'detail' => 'invalid_input');
        }
        return array('ok' => true, 'request' => $request);
    }

    private function recordAudit(array $request, $providerName, array $result, $duration)
    {
        $row = array(
            'id' => bin2hex(random_bytes(16)),
            'consumer_module' => $request['consumer'],
            'task_name' => $request['task'],
            'provider' => (string) $providerName,
            'model' => isset($result['model']) ? (string) $result['model'] : null,
            'success' => !empty($result['ok']) ? 1 : 0,
            'input_tokens' => isset($result['inputTokens']) ? (int) $result['inputTokens'] : 0,
            'output_tokens' => isset($result['outputTokens']) ? (int) $result['outputTokens'] : 0,
            'duration_ms' => (int) $duration,
            'error_code' => !empty($result['ok']) ? null : (string) ($result['error'] ?? 'unknown_error'),
            'date_created' => date('Y-m-d H:i:s')
        );
        $this->insert($row);
    }
}
?>
