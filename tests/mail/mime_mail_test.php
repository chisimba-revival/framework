<?php
/** No provider calls: exercise real MIME construction and a captured SendGrid request. */
error_reporting(E_ALL);
set_error_handler(static function ($n, $s, $f, $l) { throw new ErrorException($s, 0, $n, $f, $l); });
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject {
    public function getObject($name, $module) { return $GLOBALS['services'][$name]; }
}
class Config {
    public $values = ['MAIL_SEND_METHOD'=>'smtp', 'MAIL_SMTP_SERVER'=>'127.0.0.1', 'MAIL_SMTP_PORT'=>2525,
        'MAIL_SMTP_REQUIRESAUTH'=>'false', 'MAIL_SMTP_SECURITY'=>'none', 'MAIL_SENDGRID_KEY'=>'fixture-only',
        'MAIL_USE_HTML_AS_DEFAULT'=>'true'];
    public function getValue($key, $module, $default=null) { return $this->values[$key] ?? $default; }
}
$root = dirname(__DIR__, 2) . '/app/core_modules/mail/classes/';
foreach (['plainmailservice', 'mimemailservice', 'sendgridtransport', 'mailer'] as $class) require $root . $class . '_class_inc.php';
class CaptureSendgrid extends sendgridtransport {
    public $payload;
    protected function request($key, $payload) { $this->payload = json_decode($payload, true); return 202; }
}
$GLOBALS['services'] = ['dbsysconfig'=>new Config(), 'plainmailservice'=>new plainmailservice(),
    'mimemailservice'=>new mimemailservice(), 'sendgridtransport'=>new CaptureSendgrid()];
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
function rejects($callback) { try { $callback(); } catch (InvalidArgumentException | RuntimeException $e) { return; } throw new RuntimeException('Expected rejection'); }
$service = $GLOBALS['services']['mimemailservice'];
$message = ['from'=>'sender@example.invalid', 'fromName'=>'Café birds', 'subject'=>'Birds — reminder',
    'to'=>['one@example.invalid', 'one@example.invalid'], 'cc'=>'copy@example.invalid',
    'bcc'=>'private@example.invalid', 'body'=>'Plain café', 'htmlbody'=>'<p>HTML café</p>'];
$mail = $service->prepare($message);
$GLOBALS['services']['plainmailservice']->configureTransport($mail);
check($mail->preSend(), 'MIME prepared');
$mime = $mail->getSentMIMEMessage();
check(str_contains($mime, 'multipart/alternative'), 'plain/HTML alternatives');
check(str_contains($mime, 'Cc: copy@example.invalid'), 'CC remains visible');
check(!str_contains($mime, 'private@example.invalid') && !str_contains($mime, 'Bcc:'), 'BCC absent from SMTP message');
check(count($mail->getAllRecipientAddresses()) === 3, 'deduplicated envelope includes BCC');
check(str_contains($mime, 'caf=C3=A9'), 'UTF-8 retained');
check($mail->SMTPAuth === false && $mail->SMTPSecure === '', 'local relay configuration');
foreach (['to', 'cc', 'bcc', 'from', 'fromName', 'subject'] as $field) {
    $bad = $message; $bad[$field] = "injected@example.invalid\r\nBcc: attacker@example.invalid";
    rejects(fn()=>$service->prepare($bad));
}
foreach ([['to'=>[], 'cc'=>null, 'bcc'=>null], ['to'=>['good@example.invalid','bad']], ['body'=>str_repeat('x',1000001)]] as $fields) {
    rejects(fn()=>$service->prepare(array_replace($message, $fields)));
}
$config = $GLOBALS['services']['dbsysconfig'];
$config->values['MAIL_SMTP_REQUIRESAUTH'] = 'true';
rejects(fn()=>$GLOBALS['services']['plainmailservice']->configureTransport($service->prepare($message)));
$config->values['MAIL_SMTP_SECURITY'] = 'tls';
check($GLOBALS['services']['plainmailservice']->configureTransport($service->prepare($message))->SMTPSecure === 'tls', 'TLS selected');
$config->values['MAIL_SEND_METHOD'] = 'sendgrid';
check($service->send($message), 'captured SendGrid acceptance');
$payload = $GLOBALS['services']['sendgridtransport']->payload;
check($payload['personalizations'][0]['bcc'][0]['email'] === 'private@example.invalid', 'SendGrid private BCC');
check(count($payload['content']) === 2, 'SendGrid alternatives');
rejects(fn()=>$service->send(array_replace($message, ['to'=>[]])));
$facade = new mailer(); $facade->init();
foreach ($message as $key=>$value) $facade->setValue($key, $value);
check($facade->send(), 'legacy facade delegates');
$config->values['MAIL_USE_HTML_AS_DEFAULT'] = 'false';
check($facade->send(), 'plain default sends');
check($GLOBALS['services']['sendgridtransport']->payload['content'] === [['type'=>'text/plain','value'=>'Plain café']], 'false string disables HTML');
$facade->setValue('useHTMLMail', true);
check($facade->send(), 'explicit HTML override');
check(count($GLOBALS['services']['sendgridtransport']->payload['content']) === 2, 'HTML override honoured');
$blind = $service->prepare(array_replace($message, ['to'=>[], 'cc'=>[], 'bcc'=>['private@example.invalid', 'second@example.invalid']]));
$blind->isSMTP();
check($blind->preSend() && count($blind->getAllRecipientAddresses()) === 2, 'BCC-only SMTP supported');
check(!str_contains($blind->getSentMIMEMessage(), 'private@example.invalid'), 'BCC-only privacy');
$facade->setValue('bcc', 'invalid');
ob_start(); $result = $facade->send(); $output = ob_get_clean();
check($result === false && $output === '', 'failure is false without output');
check(!class_exists('PEAR', false) && !class_exists('Mail_mime', false), 'no PEAR loaded');
echo "MIME mail preparation, privacy, validation, facade and SendGrid capture passed\n";
