<?php
/** Shared UTF-8 MIME boundary for the retained synchronous mail API. */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('No direct access');
}
class mimemailservice extends ChisimbaObject
{
    public function init() {}

    /** Accept legacy comma-separated bare addresses or arrays, without header injection. */
    private function addresses($value)
    {
        if ($value === null || $value === '') {
            return [];
        }
        $values = is_array($value) ? $value : [$value];
        $result = [];
        foreach ($values as $entry) {
            if (!is_string($entry) || preg_match('/[\r\n\x00]/', $entry)) {
                throw new InvalidArgumentException('Invalid mail recipient');
            }
            foreach (explode(',', $entry) as $address) {
                $address = trim($address);
                if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Invalid mail recipient');
                }
                $result[strtolower($address)] = $address;
            }
        }
        if (count($result) > 1000) {
            throw new InvalidArgumentException('Too many mail recipients');
        }
        return array_values($result);
    }

    /** Prepare only: no network, filesystem attachment reads or provider requests. */
    public function prepare(array $message)
    {
        $vendor = dirname(__DIR__) . '/vendor/phpmailer/src/';
        foreach (['Exception', 'SMTP', 'PHPMailer'] as $class) {
            require_once $vendor . $class . '.php';
        }
        $from = $message['from'] ?? '';
        $subject = $message['subject'] ?? '';
        $this->getObject('plainmailservice', 'mail')->headers($from, $from, $from, $subject);
        $name = $message['fromName'] ?? '';
        if (!is_string($name) || preg_match('/[\r\n\x00]/', $name)) {
            throw new InvalidArgumentException('Invalid sender name');
        }
        $body = $message['body'] ?? '';
        $html = $message['htmlbody'] ?? '';
        foreach ([$body, $html] as $part) {
            if (!is_string($part) || strlen($part) > 1000000) {
                throw new InvalidArgumentException('Invalid mail body');
            }
        }
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'quoted-printable';
        $mail->SMTPDebug = 0;
        $mail->Timeout = 10;
        $mail->setFrom($from, $name);
        $mail->Subject = $subject;
        $count = 0;
        foreach (['to'=>'addAddress', 'cc'=>'addCC', 'bcc'=>'addBCC'] as $field=>$method) {
            foreach ($this->addresses($message[$field] ?? null) as $address) {
                $mail->$method($address);
                $count++;
            }
        }
        if (!$count || $count > 1000) {
            throw new InvalidArgumentException('Invalid mail recipient count');
        }
        $mail->isHTML($html !== '');
        $mail->Body = $html !== '' ? $html : $body;
        $mail->AltBody = $html !== '' ? ($body !== '' ? $body : strip_tags($html)) : '';
        return $mail;
    }

    public function send(array $message)
    {
        $mail = $this->prepare($message);
        $config = $this->getObject('dbsysconfig', 'sysconfig');
        if ($config->getValue('MAIL_SEND_METHOD', 'mail', 'smtp') === 'sendgrid') {
            return $this->getObject('sendgridtransport', 'mail')->sendPrepared($mail);
        }
        return $this->getObject('plainmailservice', 'mail')->configureTransport($mail)->send();
    }
}
