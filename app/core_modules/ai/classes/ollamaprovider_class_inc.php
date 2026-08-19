<?php
/**
 * Ollama provider for the shared AI service.
 *
 * Uses Ollama's native chat API and JSON-schema structured outputs so domain
 * consumers keep the same provider-neutral request and validation contract.
 *
 * @category  Chisimba
 * @package   ai
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
require_once dirname(__FILE__) . '/aiproviderinterface.php';

class ollamaprovider extends ChisimbaObject implements AiProviderInterface
{
    public $objConfig;

    public function init()
    {
        $this->objConfig = $this->getObject('dbsysconfig', 'sysconfig');
    }

    public function status()
    {
        $baseUrl = $this->baseUrl();
        $model = trim((string) $this->objConfig->getValue('AI_OLLAMA_MODEL', 'ai'));
        return array(
            'provider' => 'ollama',
            'model' => $model,
            'configured' => $baseUrl !== '' && $model !== ''
        );
    }

    public function execute(array $request)
    {
        $baseUrl = $this->baseUrl();
        $model = trim((string) $this->objConfig->getValue('AI_OLLAMA_MODEL', 'ai'));
        if ($baseUrl === '') { return $this->failure('ollama_url_unavailable'); }
        if ($model === '') { return $this->failure('ollama_model_unavailable'); }
        if (!function_exists('curl_init')) { return $this->failure('curl_unavailable'); }

        $payload = json_encode(array(
            'model' => $model,
            'messages' => array(
                array('role' => 'system', 'content' => (string) $request['instructions']),
                array('role' => 'user', 'content' => (string) $request['input'])
            ),
            'stream' => false,
            'format' => $request['schema'],
            'options' => array('temperature' => 0)
        ));
        if ($payload === false) { return $this->failure('json_encoding_failed'); }

        $timeout = (int) $this->objConfig->getValue('AI_REQUEST_TIMEOUT', 'ai');
        if ($timeout < 1) { $timeout = 60; }
        $handle = curl_init($baseUrl . '/api/chat');
        curl_setopt_array($handle, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
            CURLOPT_TIMEOUT => $timeout
        ));
        $raw = curl_exec($handle);
        $curlError = curl_error($handle);
        $httpCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        if ($raw === false) { return $this->failure('ollama_transport_error', $curlError, $httpCode); }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) { return $this->failure('ollama_invalid_json', null, $httpCode); }
        if ($httpCode < 200 || $httpCode >= 300) {
            $detail = isset($decoded['error']) ? (string) $decoded['error'] : 'ollama_rejected';
            return $this->failure('ollama_rejected', $detail, $httpCode);
        }

        $text = isset($decoded['message']['content']) ? trim((string) $decoded['message']['content']) : '';
        if ($text === '') { return $this->failure('ollama_missing_output', null, $httpCode); }
        $data = json_decode($text, true);
        if (!is_array($data)) { return $this->failure('ollama_structured_output_invalid', null, $httpCode); }

        return array(
            'ok' => true,
            'provider' => 'ollama',
            'model' => isset($decoded['model']) ? (string) $decoded['model'] : $model,
            'providerReference' => isset($decoded['created_at']) ? (string) $decoded['created_at'] : null,
            'data' => $data,
            'inputTokens' => isset($decoded['prompt_eval_count']) ? (int) $decoded['prompt_eval_count'] : 0,
            'outputTokens' => isset($decoded['eval_count']) ? (int) $decoded['eval_count'] : 0,
            'httpCode' => $httpCode
        );
    }

    private function baseUrl()
    {
        $url = rtrim(trim((string) $this->objConfig->getValue('AI_OLLAMA_BASE_URL', 'ai')), '/');
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            return '';
        }
        return $url;
    }

    private function failure($error, $detail = null, $httpCode = 0)
    {
        return array(
            'ok' => false,
            'provider' => 'ollama',
            'error' => (string) $error,
            'detail' => $detail === null ? (string) $error : (string) $detail,
            'httpCode' => (int) $httpCode,
            'inputTokens' => 0,
            'outputTokens' => 0
        );
    }
}
?>
