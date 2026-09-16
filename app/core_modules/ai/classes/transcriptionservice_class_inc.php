<?php
/**
 * Reusable speech-to-text adapter. Accepts authorised private files, never URLs.
 * Provider bodies and recordings are not written to diagnostics.
 * @author Derek Keats
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class transcriptionservice extends ChisimbaObject
{
    private $config;
    public function init() { $this->config=$this->getObject('dbsysconfig','sysconfig'); }
    public function isAvailable()
    {
        return strtolower((string)$this->config->getValue('AI_STATE','ai'))==='enabled'
            && $this->config->getValue('AI_TRANSCRIPTION_STATE','ai')==='enabled'
            && trim((string)$this->config->getValue('AI_OPENAI_API_KEY','ai'))!=='';
    }
    public function transcribe($path)
    {
        if (!$this->isAvailable()) return ['ok'=>false,'error'=>'transcription_unavailable'];
        $root=realpath((string)$this->config->getValue('SECUREFODLER','filemanager'));
        $real=is_string($path)?realpath($path):false;
        if (!$root || !$real || !str_starts_with($real,$root.DIRECTORY_SEPARATOR)) {
            return ['ok'=>false,'error'=>'private_audio_required'];
        }
        $audio=$this->getObject('audioinspection','ai')->inspect($real);
        if (empty($audio['ok'])) return $audio;
        $model=trim((string)$this->config->getValue('AI_TRANSCRIPTION_MODEL','ai'));
        if (!in_array($model,['gpt-4o-mini-transcribe','gpt-4o-transcribe','whisper-1'],true)) {
            return ['ok'=>false,'error'=>'transcription_model'];
        }
        $body='';
        $handle=curl_init('https://api.openai.com/v1/audio/transcriptions');
        curl_setopt_array($handle,[
            CURLOPT_POST=>true, CURLOPT_CONNECTTIMEOUT=>10, CURLOPT_TIMEOUT=>120,
            CURLOPT_HTTPHEADER=>['Authorization: Bearer '.trim((string)$this->config->getValue('AI_OPENAI_API_KEY','ai'))],
            CURLOPT_POSTFIELDS=>[
                'model'=>$model,'response_format'=>'json',
                'file'=>new CURLFile($real,$audio['mime'],'speech.'.$audio['extension'])
            ],
            CURLOPT_WRITEFUNCTION=>static function ($handle,$chunk) use (&$body) {
                if (strlen($body)+strlen($chunk)>1048576) return 0;
                $body.=$chunk; return strlen($chunk);
            }
        ]);
        $ok=curl_exec($handle);
        $status=(int)curl_getinfo($handle,CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        if ($ok===false || $status<200 || $status>=300) {
            return ['ok'=>false,'error'=>'transcription_failed','model'=>$model];
        }
        $data=json_decode($body,true); $text=$data['text']??null;
        if (!is_string($text) || trim($text)==='' || !mb_check_encoding($text,'UTF-8') || mb_strlen($text)>30000) {
            return ['ok'=>false,'error'=>'transcription_empty','model'=>$model];
        }
        return ['ok'=>true,'text'=>trim($text),'model'=>$model,
            'inputTokens'=>(int)($data['usage']['input_tokens']??0),
            'outputTokens'=>(int)($data['usage']['output_tokens']??0)];
    }
}
