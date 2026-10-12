<?php
/** Compose actual services in an opted-in disposable installation; never send. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_INSTALLER_FIXTURE') !== '1') exit(64);
error_reporting(E_ALL);
set_error_handler(static function ($n, $s, $f, $l) { throw new ErrorException($s, 0, $n, $f, $l); });
require dirname(__DIR__) . '/installer/installed_runtime_smoke.php';
$engine->getObject('mailer', 'mail');
$mail = $engine->getObject('mimemailservice', 'mail')->prepare([
    'to'=>'recipient@example.invalid', 'from'=>'fixture@example.invalid', 'bcc'=>'private@example.invalid',
    'subject'=>'Fixture café', 'body'=>'Plain', 'htmlbody'=>'<p>HTML</p>',
]);
$mail->isSMTP();
if (!$mail->preSend() || str_contains($mail->getSentMIMEMessage(), 'private@example.invalid')) {
    throw new RuntimeException('Installed MIME/privacy boundary failed');
}
if (class_exists('PEAR', false) || class_exists('Mail', false) || class_exists('Mail_mime', false)) {
    throw new RuntimeException('Installed mail loaded PEAR');
}
echo "PASS installed mail: actual services, UTF-8 MIME, private BCC, no PEAR, no transport invoked\n";
