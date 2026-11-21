<?php
/** Bounded credential-free HTTPS JSON GETs. Consumers supply a fixed host allowlist. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class publicjsontransport extends ChisimbaObject
{
    public function init() {}
    public function get(string $url, array $allowedHosts, string $agent): array
    {
        $p=parse_url($url); $host=strtolower($p['host'] ?? '');
        if (($p['scheme'] ?? '')!=='https' || !in_array($host,$allowedHosts,true) || isset($p['user']) || isset($p['pass']) || isset($p['fragment']) || (isset($p['port']) && $p['port']!==443)) throw new InvalidArgumentException('Invalid public API URL');
        // Pin a validated address so DNS cannot change between validation and connection.
        $addresses=gethostbynamel($host) ?: [];
        if (!$addresses) return ['ok'=>false,'status'=>0,'retry_after'=>60];
        foreach ($addresses as $ip) if (!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) throw new InvalidArgumentException('Non-public API address');
        $body=''; $retry=0; $h=curl_init($url);
        curl_setopt_array($h,[CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,
            CURLOPT_RESOLVE=>[$host.':443:'.$addresses[0]],CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>40,
            CURLOPT_USERAGENT=>$agent,CURLOPT_HTTPHEADER=>['Accept: application/json'],
            CURLOPT_HEADERFUNCTION=>static function($h,$line) use (&$retry) {
                if (stripos($line,'retry-after:')===0) { $v=trim(substr($line,12)); $retry=ctype_digit($v)?(int)$v:max(0,(int)strtotime($v)-time()); }
                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION=>static function($h,$chunk) use (&$body) { if(strlen($body)+strlen($chunk)>4000000)return 0; $body.=$chunk;return strlen($chunk); }
        ]);
        $ok=curl_exec($h); $code=(int)curl_getinfo($h,CURLINFO_HTTP_CODE); unset($h);
        $data=json_decode($body,true,64);
        return ['ok'=>$ok!==false && $code===200 && is_array($data),'status'=>$code,'retry_after'=>$retry,'data'=>is_array($data)?$data:[]];
    }
}
