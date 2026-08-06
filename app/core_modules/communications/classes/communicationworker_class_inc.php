<?php
/**
 * Bounded outbox delivery worker with retry and attempt audit.
 *
 * @author  Derek Keats
 * @package communications
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class communicationworker extends dbTable
{
    public $objConfig;

    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init($tableName !== null ? $tableName : 'tbl_communications_outbox', $pearDb, $errorCallback);
        $this->objConfig = $this->getObject('dbsysconfig', 'sysconfig');
    }

    /** Process one explicitly selected ready outbox item. */
    public function runMessage($messageId)
    {
        $id = strtolower(trim((string) $messageId));
        if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
            return array('selected' => 0, 'sent' => 0, 'retried' => 0, 'failed' => 0);
        }
        $now = date('Y-m-d H:i:s');
        $rows = $this->getArray(
            "SELECT * FROM tbl_communications_outbox WHERE id = '" . $id
            . "' AND status = 'queued' AND available_at <= '" . $now . "' LIMIT 1"
        );
        $summary = array('selected' => 0, 'sent' => 0, 'retried' => 0, 'failed' => 0);
        if (!is_array($rows) || !isset($rows[0])) { return $summary; }
        $summary['selected'] = 1;
        $outcome = $this->deliverOne($rows[0]);
        $summary[$outcome] = 1;
        return $summary;
    }

    /** Process at most $limit ready items. One worker process is assumed in v0.1. */
    public function run($limit = 20)
    {
        $limit = max(1, min(100, (int) $limit));
        $now = date('Y-m-d H:i:s');
        $rows = $this->getArray("SELECT * FROM tbl_communications_outbox WHERE status = 'queued' AND available_at <= '" . $now . "' ORDER BY date_created ASC LIMIT " . $limit);
        $summary = array('selected' => 0, 'sent' => 0, 'retried' => 0, 'failed' => 0);
        if (!is_array($rows)) { return $summary; }
        foreach ($rows as $row) {
            $summary['selected']++;
            $outcome = $this->deliverOne($row);
            $summary[$outcome]++;
        }
        return $summary;
    }

    private function deliverOne(array $row)
    {
        $transportName = strtolower(trim((string) $this->objConfig->getValue('COMMUNICATION_TRANSPORT', 'communications')));
        if (!in_array($transportName, array('null', 'sendgrid'), true)) { $transportName = 'null'; }
        $transport = $this->getObject($transportName . 'transport', 'communications');
        $attempt = ((int) $row['attempts']) + 1;
        $result = $transport->deliver($row);
        $now = date('Y-m-d H:i:s');
        $this->recordAttempt($row['id'], $attempt, $transportName, $result, $now);
        if (!empty($result['ok'])) {
            $this->update('id', $row['id'], array('status' => 'sent', 'transport' => $transportName,
                'attempts' => $attempt, 'sent_at' => $now, 'last_error' => null, 'date_updated' => $now));
            return 'sent';
        }
        $maxAttempts = max(1, (int) $this->objConfig->getValue('COMMUNICATION_MAX_ATTEMPTS', 'communications'));
        $terminal = $attempt >= $maxAttempts;
        $delay = min(3600, 60 * (1 << min(6, $attempt - 1)));
        $detail = isset($result['detail']) ? (string) $result['detail'] : (isset($result['error']) ? (string) $result['error'] : 'delivery_failed');
        $this->update('id', $row['id'], array('status' => $terminal ? 'failed' : 'queued',
            'transport' => $transportName, 'attempts' => $attempt,
            'available_at' => date('Y-m-d H:i:s', time() + $delay), 'last_error' => substr($detail, 0, 65000),
            'date_updated' => $now));
        return $terminal ? 'failed' : 'retried';
    }

    private function recordAttempt($outboxId, $attempt, $transport, array $result, $now)
    {
        $previous = $this->_tableName;
        $this->_tableName = 'tbl_communications_attempts';
        $this->insert(array('id' => bin2hex(random_bytes(16)), 'outbox_id' => $outboxId,
            'attempt_number' => $attempt, 'transport' => $transport,
            'outcome' => !empty($result['ok']) ? 'sent' : 'failed',
            'provider_reference' => isset($result['providerReference']) ? $result['providerReference'] : null,
            'response_code' => isset($result['code']) ? (int) $result['code'] : null,
            'error_detail' => isset($result['detail']) ? substr((string) $result['detail'], 0, 65000) : null,
            'date_created' => $now));
        $this->_tableName = $previous;
    }
}
?>
