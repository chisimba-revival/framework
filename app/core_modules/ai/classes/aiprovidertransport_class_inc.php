<?php
/** Credentialed provider engagement only; consumers own payloads and decoding. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('No direct access'); }
class aiprovidertransport extends ChisimbaObject
{
    public function init() {}
    public function post($provider, $operation, array $payload)
    {
        // Never accept an arbitrary destination, header, credential or redirect.
        $routes = array('openai' => array('image_edit' => 'https://api.openai.com/v1/images/edits'));
        if (!isset($routes[$provider][$operation])) { throw new InvalidArgumentException('Unsupported provider operation'); }
        $config = $this->getObject('dbsysconfig', 'sysconfig');
        if ($config->getValue('AI_STATE', 'ai') !== 'enabled') { return array('ok'=>false, 'error'=>'ai_disabled'); }
        $key = trim((string)$config->getValue('AI_OPENAI_API_KEY', 'ai'));
        if ($key === '' || !function_exists('curl_init')) { return array('ok'=>false, 'error'=>'provider_unavailable'); }
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $body = ''; $requestId = null;
        $handle = curl_init($routes[$provider][$operation]);
        curl_setopt_array($handle, array(
            CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$json,
            CURLOPT_HTTPHEADER=>array('Content-Type: application/json', 'Authorization: Bearer '.$key),
            CURLOPT_FOLLOWLOCATION=>false, CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT=>10, CURLOPT_TIMEOUT=>300,
            CURLOPT_HEADERFUNCTION=>static function($h, $line) use (&$requestId) {
                if (stripos($line, 'x-request-id:') === 0) { $requestId = substr(trim(substr($line, 13)), 0, 200); }
                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION=>static function($h, $chunk) use (&$body) {
                if (strlen($body) + strlen($chunk) > 20000000) { return 0; }
                $body .= $chunk; return strlen($chunk);
            }
        ));
        $started = microtime(true);
        $ok = curl_exec($handle);
        $code = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
        unset($handle);
        $meta = array('request_id'=>$requestId, 'http_status'=>$code,
            'duration_ms'=>(int)round((microtime(true)-$started)*1000));
        if ($ok === false) { return $meta + array('ok'=>false, 'error'=>'unknown_outcome'); }
        if ($code < 200 || $code >= 300) { return $meta + array('ok'=>false, 'error'=>'provider_http_error'); }
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) { return $meta + array('ok'=>false, 'error'=>'invalid_provider_response'); }
        return $meta + array('ok'=>true, 'body'=>$decoded);
    }
}
