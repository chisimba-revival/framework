<?php
/** Validated UTF-8 plain-text delivery using pinned PHPMailer and the shared site mail configuration. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class plainmailservice extends ChisimbaObject
{
    public function init(){}
    public static function headers($to,$from,$reply,$subject)
    {
        foreach([$to,$from,$reply] as $address)if(!is_string($address)||preg_match('/[\r\n\x00]/',$address)||!filter_var($address,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Invalid mail address');
        if(!is_string($subject)||preg_match('/[\r\n\x00]/',$subject)||mb_strlen($subject)>250)throw new InvalidArgumentException('Invalid mail subject');
        return ['From'=>$from,'To'=>$to,'Reply-To'=>$reply,'Subject'=>'=?UTF-8?B?'.base64_encode($subject).'?=','MIME-Version'=>'1.0','Content-Type'=>'text/plain; charset=UTF-8','Content-Transfer-Encoding'=>'8bit'];
    }
    public function prepare($to,$from,$reply,$subject,$body)
    {
        self::headers($to,$from,$reply,$subject);
        if(!is_string($body)||strlen($body)>100000)throw new InvalidArgumentException('Invalid mail body');
        $vendor=dirname(__DIR__).'/vendor/phpmailer/src/';
        foreach(['Exception','SMTP','PHPMailer'] as $class)require_once $vendor.$class.'.php';
        $mail=new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet='UTF-8';$mail->Encoding='quoted-printable';$mail->SMTPDebug=0;$mail->Timeout=10;
        $mail->setFrom($from);$mail->addAddress($to);$mail->addReplyTo($reply);$mail->Subject=$subject;$mail->Body=$body;$mail->isHTML(false);
        $c=$this->getObject('dbsysconfig','sysconfig');$method=$c->getValue('MAIL_SEND_METHOD','mail','smtp');
        if($method==='smtp'){
            $mail->isSMTP();$mail->Host=(string)$c->getValue('MAIL_SMTP_SERVER','mail');$mail->Port=(int)$c->getValue('MAIL_SMTP_PORT','mail',587);
            $mail->SMTPAuth=strtolower((string)$c->getValue('MAIL_SMTP_REQUIRESAUTH','mail','true'))==='true';
            $mail->Username=(string)$c->getValue('MAIL_SMTP_USER','mail');$mail->Password=(string)$c->getValue('MAIL_SMTP_PASSWORD','mail');
            $security=(string)$c->getValue('MAIL_SMTP_SECURITY','mail','tls');
            if(!in_array($security,['tls','ssl','none'],true)||($security==='none'&&$mail->SMTPAuth))throw new RuntimeException('Invalid SMTP security configuration');
            $mail->SMTPSecure=$security==='none'?'':$security;$mail->SMTPAutoTLS=$security!=='none';
            if(!preg_match('/^[A-Za-z0-9.-]+$/D',$mail->Host)||$mail->Port<1||$mail->Port>65535)throw new RuntimeException('Invalid SMTP host or port');
        }elseif($method==='mail'){$mail->isMail();}
        elseif($method==='sendmail'){$mail->isSendmail();}
        else throw new RuntimeException('Mail transport not configured');
        return $mail;
    }
    public function send($to,$from,$reply,$subject,$body)
    {
        if($this->getObject('dbsysconfig','sysconfig')->getValue('MAIL_SEND_METHOD','mail','smtp')==='sendgrid')return $this->getObject('sendgridtransport','mail')->send($to,$from,$reply,$subject,$body);
        return $this->prepare($to,$from,$reply,$subject,$body)->send();
    }
}
