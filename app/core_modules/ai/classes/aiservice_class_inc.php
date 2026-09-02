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
    public $objTimeAndDate;

    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init($tableName !== null ? $tableName : 'tbl_ai_requests', $pearDb, $errorCallback);
        $this->objConfig = $this->getObject('dbsysconfig', 'sysconfig');
        $this->objTimeAndDate = $this->getObject('timeanddateservice', 'timeanddate-service');
    }

    /**
     * Return the single application-facing availability decision for AI.
     *
     * Consumers must not inspect provider configuration directly. A missing AI
     * module is handled by the consumer before this service can be requested.
     */
    public function isAvailable()
    {
        $state = strtolower(trim((string) $this->objConfig->getValue('AI_STATE', 'ai')));
        if ($state !== 'enabled') {
            return false;
        }

        $status = $this->providerStatus();
        return is_array($status) && !empty($status['configured']);
    }

    /**
     * Execute one provider-neutral structured AI request.
     *
     * Required keys: consumer, task, instructions, input, schemaName, schema.
     * The service deliberately does not know the domain meaning of the task.
     */
    public function execute(array $request)
    {
        if (!$this->isAvailable()) {
            return array(
                'ok' => false,
                'provider' => $this->providerName(),
                'error' => 'ai_unavailable',
                'detail' => 'ai_unavailable',
                'inputTokens' => 0,
                'outputTokens' => 0
            );
        }

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
        return $this->usageAnalytics(array('days'=>30));
    }

    /**
     * Return privacy-preserving operational analytics for administrators.
     * Prompt and response content are neither read nor returned.
     *
     * @param array $filters days, consumer and provider are supported.
     * @return array Aggregates, trends and breakdowns for dashboard rendering.
     * @author Derek Keats
     */
    public function usageAnalytics(array $filters = array())
    {
        $days = isset($filters['days']) && in_array((int) $filters['days'], array(7, 30, 90), true)
            ? (int) $filters['days'] : 30;
        $consumer = $this->safeDimension($filters['consumer'] ?? '');
        $providerFilter = $this->safeDimension($filters['provider'] ?? '');
        $since = $this->objTimeAndDate->toStorage(
            $this->objTimeAndDate->nowUtc()->modify('-' . $days . ' days')
        );
        $where = "WHERE date_created >= '" . addslashes($since) . "'";
        if ($consumer !== '') { $where .= " AND consumer_module='" . addslashes($consumer) . "'"; }
        if ($providerFilter !== '') { $where .= " AND provider='" . addslashes($providerFilter) . "'"; }
        $rows = $this->getAll($where . ' ORDER BY date_created ASC');
        if (!is_array($rows)) { $rows = array(); }

        $summary = array(
            'days'=>$days, 'requests'=>0, 'successful'=>0, 'failed'=>0,
            'inputTokens'=>0, 'outputTokens'=>0, 'durationAverageMs'=>0,
            'durationP95Ms'=>0, 'successRate'=>0.0, 'estimatedCost'=>0.0,
            'trend'=>array(), 'consumers'=>array(), 'providers'=>array(),
            'models'=>array(), 'errors'=>array(), 'availableConsumers'=>array(),
            'availableProviders'=>array(),
        );
        $durations = array();
        for ($offset = min(13, $days - 1); $offset >= 0; $offset--) {
            $key = $this->objTimeAndDate->nowUtc()->modify('-' . $offset . ' days')->format('Y-m-d');
            $summary['trend'][$key] = array('requests'=>0, 'failed'=>0, 'tokens'=>0);
        }
        foreach ($rows as $row) {
            $summary['requests']++;
            $success = !empty($row['success']);
            $summary[$success ? 'successful' : 'failed']++;
            $input = max(0, (int) ($row['input_tokens'] ?? 0));
            $output = max(0, (int) ($row['output_tokens'] ?? 0));
            $summary['inputTokens'] += $input;
            $summary['outputTokens'] += $output;
            $duration = max(0, (int) ($row['duration_ms'] ?? 0));
            $durations[] = $duration;
            $provider = $this->safeDimension($row['provider'] ?? '') ?: 'unknown';
            $module = $this->safeDimension($row['consumer_module'] ?? '') ?: 'unknown';
            $model = trim((string) ($row['model'] ?? '')) ?: 'unknown';
            $summary['consumers'][$module] = ($summary['consumers'][$module] ?? 0) + 1;
            $summary['providers'][$provider] = ($summary['providers'][$provider] ?? 0) + 1;
            $summary['models'][$model] = ($summary['models'][$model] ?? 0) + 1;
            $summary['availableConsumers'][$module] = $module;
            $summary['availableProviders'][$provider] = $provider;
            if (!$success) {
                $error = $this->safeDimension($row['error_code'] ?? '') ?: 'unknown_error';
                $summary['errors'][$error] = ($summary['errors'][$error] ?? 0) + 1;
            }
            $day = substr((string) ($row['date_created'] ?? ''), 0, 10);
            if (isset($summary['trend'][$day])) {
                $summary['trend'][$day]['requests']++;
                $summary['trend'][$day]['tokens'] += $input + $output;
                if (!$success) { $summary['trend'][$day]['failed']++; }
            }
            $summary['estimatedCost'] += $this->estimateCost($provider, $input, $output);
        }
        if ($summary['requests'] > 0) {
            $summary['successRate'] = ($summary['successful'] / $summary['requests']) * 100;
            $summary['durationAverageMs'] = (int) round(array_sum($durations) / count($durations));
            sort($durations, SORT_NUMERIC);
            $summary['durationP95Ms'] = $durations[(int) floor((count($durations) - 1) * .95)];
        }
        arsort($summary['consumers']); arsort($summary['providers']);
        arsort($summary['models']); arsort($summary['errors']);
        ksort($summary['availableConsumers']); ksort($summary['availableProviders']);
        return $summary;
    }

    /** Estimate request cost from administrator-maintained per-million rates. */
    private function estimateCost($provider, $inputTokens, $outputTokens)
    {
        $prefix = 'AI_' . strtoupper($provider);
        $inputRate = max(0.0, (float) $this->objConfig->getValue($prefix . '_INPUT_COST_PER_MILLION', 'ai', 0));
        $outputRate = max(0.0, (float) $this->objConfig->getValue($prefix . '_OUTPUT_COST_PER_MILLION', 'ai', 0));
        return (($inputTokens / 1000000) * $inputRate) + (($outputTokens / 1000000) * $outputRate);
    }

    /** Normalise a dimension before including it in a query or aggregate. */
    private function safeDimension($value)
    {
        $value = strtolower(trim((string) $value));
        return preg_match('/^[a-z][a-z0-9_-]{0,95}$/', $value) ? $value : '';
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
            'date_created' => $this->objTimeAndDate->nowStorage()
        );
        $this->insert($row);
    }
}
?>
