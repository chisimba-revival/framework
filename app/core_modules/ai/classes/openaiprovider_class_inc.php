<?php
/**
 * OpenAI Responses API provider for the shared AI service.
 *
 * @category  Chisimba
 * @package   ai
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
require_once dirname(__FILE__) . '/aiproviderinterface.php';

class openaiprovider extends ChisimbaObject implements AiProviderInterface
{
    public $objConfig;

    public function init()
    {
        $this->objConfig = $this->getObject('dbsysconfig', 'sysconfig');
    }

    public function status()
    {
        $key = trim((string) $this->objConfig->getValue('AI_OPENAI_API_KEY', 'ai'));
        $model = trim((string) $this->objConfig->getValue('AI_OPENAI_MODEL', 'ai'));
        return array(
            'provider' => 'openai',
            'model' => $model,
            'configured' => $key !== '' && $model !== ''
        );
    }

    public function execute(array $request)
    {
        $apiKey = trim((string) $this->objConfig->getValue('AI_OPENAI_API_KEY', 'ai'));
        $model = trim((string) $this->objConfig->getValue('AI_OPENAI_MODEL', 'ai'));
        if ($apiKey === '') { return $this->failure('openai_secret_unavailable'); }
        if ($model === '') { return $this->failure('openai_model_unavailable'); }
        if (!function_exists('curl_init')) { return $this->failure('curl_unavailable'); }

        $format = array(
            'type' => 'json_schema',
            'name' => $request['schemaName'],
            'schema' => $request['schema'],
            'strict' => true
        );
        $payload = json_encode(array(
            'model' => $model,
            'store' => false,
            'instructions' => $request['instructions'],
            'input' => $request['input'],
            'text' => array('format' => $format)
        ));
        if ($payload === false) { return $this->failure('json_encoding_failed'); }

        $timeout = (int) $this->objConfig->getValue('AI_REQUEST_TIMEOUT', 'ai');
        if ($timeout < 1) { $timeout = 60; }
        $handle = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($handle, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
            CURLOPT_TIMEOUT => $timeout
        ));
        $raw = curl_exec($handle);
        $curlError = curl_error($handle);
        $httpCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        if ($raw === false) { return $this->failure('openai_transport_error', $curlError, $httpCode); }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) { return $this->failure('openai_invalid_json', null, $httpCode); }
        if ($httpCode < 200 || $httpCode >= 300) {
            $detail = isset($decoded['error']['message']) ? (string) $decoded['error']['message'] : 'openai_rejected';
            return $this->failure('openai_rejected', $detail, $httpCode);
        }

        $text = $this->extractOutputText($decoded);
        if ($text === null) { return $this->failure('openai_missing_output', null, $httpCode); }
        $data = json_decode($text, true);
        if (!is_array($data)) { return $this->failure('openai_structured_output_invalid', null, $httpCode); }

        $usage = isset($decoded['usage']) && is_array($decoded['usage']) ? $decoded['usage'] : array();
        return array(
            'ok' => true,
            'provider' => 'openai',
            'model' => isset($decoded['model']) ? (string) $decoded['model'] : $model,
            'providerReference' => isset($decoded['id']) ? (string) $decoded['id'] : null,
            'data' => $data,
            'inputTokens' => isset($usage['input_tokens']) ? (int) $usage['input_tokens'] : 0,
            'outputTokens' => isset($usage['output_tokens']) ? (int) $usage['output_tokens'] : 0,
            'httpCode' => $httpCode
        );
    }

    private function extractOutputText(array $response)
    {
        if (empty($response['output']) || !is_array($response['output'])) { return null; }
        foreach ($response['output'] as $item) {
            if (empty($item['content']) || !is_array($item['content'])) { continue; }
            foreach ($item['content'] as $content) {
                if (isset($content['type']) && $content['type'] === 'output_text' && isset($content['text'])) {
                    return (string) $content['text'];
                }
            }
        }
        return null;
    }

    private function failure($error, $detail = null, $httpCode = 0)
    {
        return array(
            'ok' => false,
            'provider' => 'openai',
            'error' => (string) $error,
            'detail' => $detail === null ? (string) $error : (string) $detail,
            'httpCode' => (int) $httpCode,
            'inputTokens' => 0,
            'outputTokens' => 0
        );
    }
}
?>
