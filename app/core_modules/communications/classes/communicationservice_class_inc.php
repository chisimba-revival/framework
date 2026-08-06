<?php
/**
 * Canonical application-facing communication service.
 *
 * @author  Derek Keats
 * @package communications
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class communicationservice extends dbTable
{
    public $objConfig;

    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init($tableName !== null ? $tableName : 'tbl_communications_outbox', $pearDb, $errorCallback);
        $this->objConfig = $this->getObject('dbsysconfig', 'sysconfig');
    }

    /** Queue one validated email. Returns a stable result array. */
    public function queueEmail(array $input)
    {
        $normalised = $this->normaliseEmail($input);
        if (!$normalised['ok']) { return $normalised; }
        $message = $normalised['message'];
        $key = isset($input['idempotencyKey']) ? trim((string) $input['idempotencyKey']) : '';
        if ($key !== '') {
            if (strlen($key) > 191) { return $this->result(false, 'invalid_idempotency_key'); }
            $existing = $this->getRow('idempotency_key', $key);
            if (is_array($existing) && !empty($existing['id'])) {
                return $this->result(true, 'already_queued', $existing['id']);
            }
        }
        $now = date('Y-m-d H:i:s');
        $message['id'] = bin2hex(random_bytes(16));
        $message['idempotency_key'] = $key === '' ? null : $key;
        $message['channel'] = 'email';
        $message['transport'] = null;
        $message['metadata_json'] = isset($input['metadata']) ? json_encode($input['metadata']) : null;
        $message['status'] = 'queued';
        $message['attempts'] = 0;
        $message['available_at'] = $now;
        $message['claimed_at'] = null;
        $message['sent_at'] = null;
        $message['last_error'] = null;
        $message['date_created'] = $now;
        $message['date_updated'] = $now;
        if ($this->insert($message) === false) { return $this->result(false, 'queue_failed'); }
        return $this->result(true, 'queued', $message['id']);
    }

    public function status($messageId)
    {
        $id = strtolower(trim((string) $messageId));
        if (!preg_match('/^[a-f0-9]{32}$/', $id)) { return null; }
        return $this->getRow('id', $id);
    }

    private function normaliseEmail(array $input)
    {
        $recipient = isset($input['to']) ? trim((string) $input['to']) : '';
        $subject = isset($input['subject']) ? trim((string) $input['subject']) : '';
        $text = isset($input['text']) ? (string) $input['text'] : '';
        $html = isset($input['html']) ? (string) $input['html'] : '';
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) { return $this->result(false, 'invalid_recipient'); }
        if ($subject === '' || strlen($subject) > 998) { return $this->result(false, 'invalid_subject'); }
        if ($text === '' && $html === '') { return $this->result(false, 'missing_body'); }
        if ($text === '') { $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')); }
        $sender = trim((string) $this->objConfig->getValue('COMMUNICATION_FROM_EMAIL', 'communications'));
        if (!filter_var($sender, FILTER_VALIDATE_EMAIL)) { return $this->result(false, 'invalid_sender_configuration'); }
        return array('ok' => true, 'message' => array(
            'recipient' => $recipient,
            'recipient_name' => isset($input['toName']) ? trim((string) $input['toName']) : null,
            'sender' => $sender,
            'sender_name' => trim((string) $this->objConfig->getValue('COMMUNICATION_FROM_NAME', 'communications')),
            'subject' => $subject, 'body_text' => $text, 'body_html' => $html === '' ? null : $html,
        ));
    }

    private function result($ok, $code, $messageId = null)
    {
        return array('ok' => (bool) $ok, 'code' => (string) $code, 'messageId' => $messageId);
    }
}
?>
