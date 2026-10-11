<?php
require_once dirname(__FILE__) . '/abuseeventrepositoryinterface.php';

/** dbTable persistence owned by the abuseprotection module. @author Derek Keats */
final class DbTableAbuseEventRepository implements AbuseEventRepositoryInterface
{
    private $db;
    public function __construct(dbTable $connection) { $this->db = $connection; }
    public function countFailures($action, $subjectHash, $since)
    {
        $result = $this->db->selectPrepared(
            "SELECT COUNT(*) AS total FROM tbl_abuse_events WHERE action_key=? "
            . "AND subject_hash=? AND outcome='failure' AND occurred_at>=?",
            array((string) $action, (string) $subjectHash, $this->date($since))
        );
        $row = ($result[0] ?? []);
        return (int) ($row['total'] ?? 0);
    }
    public function record(array $event)
    {
        return $this->execute(
            'INSERT INTO tbl_abuse_events (id,action_key,subject_hash,outcome,'
            . 'occurred_at,expires_at) VALUES (?,?,?,?,?,?)',
            array($event['id'], $event['action_key'], $event['subject_hash'],
                $event['outcome'], $this->date($event['occurred_at']),
                $this->date($event['expires_at']))
        ) === 1;
    }
    /** MariaDB advisory lock covers admission across all PHP workers on this database.
     * Partial records after an I/O failure are conservative: they consume budget.
     * No nested transaction is opened in the caller's business transaction.
     */
    public function reserveBudgets($lock, array $budgets)
    {
        // Include the database name to avoid contention between installations.
        $result = $this->db->selectPrepared('SELECT GET_LOCK(CONCAT(LEFT(DATABASE(),12), ?), 2) AS acquired',
            array($lock));
        $row = ($result[0] ?? []);
        if ((int) ($row['acquired'] ?? 0) !== 1) { return false; }
        try {
            foreach ($budgets as $budget) {
                $result = $this->db->selectPrepared('SELECT COUNT(*) AS total FROM tbl_abuse_events '
                    . 'WHERE action_key=? AND subject_hash=? AND occurred_at>?',
                    array($budget['action_key'], $budget['subject_hash'], $this->date($budget['since'])));
                $row = ($result[0] ?? []);
                if ((int) ($row['total'] ?? 0) >= $budget['limit']) { return false; }
            }
            foreach ($budgets as $budget) {
                if (!$this->record($budget)) { throw new RuntimeException('Budget reservation failed.'); }
            }
            return true;
        } finally {
            $result = $this->db->selectPrepared('SELECT RELEASE_LOCK(CONCAT(LEFT(DATABASE(),12), ?)) AS released',
                array($lock));
            if ((int) ($result[0]['released'] ?? 0) !== 1) {
                throw new RuntimeException('Budget lock release failed.');
            }
        }
    }
    public function purgeExpired($now)
    {
        return $this->execute(
            'DELETE FROM tbl_abuse_events WHERE expires_at<=?',
            array($this->date($now))
        );
    }
    private function execute($sql, array $args)
    {
        return $this->db->executePrepared($sql, $args);
    }
    private function date($timestamp)
    {
        return gmdate('Y-m-d H:i:s', (int) $timestamp);
    }
}
