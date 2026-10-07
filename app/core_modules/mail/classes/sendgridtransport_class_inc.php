<?php
/** SendGrid HTTPS transport; fixed endpoint, no redirects, logging or automatic retries. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class sendgridtransport extends ChisimbaObject
{
    public function init(){}
    public function send($to,$from,$reply,$subject,$body)
    {
        $this->getObject('plainmailservice','mail')->headers($to,$from,$reply,$subject);
        if(!is_string($body)||strlen($body)>100000)throw new InvalidArgumentException('Invalid mail body');
        $key=(string)$this->getObject('dbsysconfig','sysconfig')->getValue('MAIL_SENDGRID_KEY','mail','');
        if($key===''||preg_match('/[\r\n]/',$key))throw new RuntimeException('SendGrid credential unavailable');
        $payload=['personalizations'=>[['to'=>[['email'=>$to]]]],'from'=>['email'=>$from],'reply_to'=>['email'=>$reply],'subject'=>$subject,'content'=>[['type'=>'text/plain','value'=>$body]],'tracking_settings'=>['click_tracking'=>['enable'=>false,'enable_text'=>false],'open_tracking'=>['enable'=>false]]];
        return $this->request($key,json_encode($payload,JSON_THROW_ON_ERROR))===202;
    }
    protected function request($key,$payload)
    {
        $ch=curl_init('https://api.sendgrid.com/v3/mail/send');$received=0;
        curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$key,'Content-Type: application/json'],CURLOPT_WRITEFUNCTION=>static function($ch,$part)use(&$received){$received+=strlen($part);return $received>65536?0:strlen($part);}]);
        $ok=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
        if($ok===false)throw new RuntimeException('Mail delivery result uncertain');
        if($status!==202)throw new RuntimeException('Mail provider rejected request (HTTP '.$status.')');
        return $status;
    }
}
