<?php
require_once dirname(__FILE__) . '/abuseeventrepositoryinterface.php';

/** MDB2 persistence owned by the abuseprotection module. @author Derek Keats */
final class Mdb2AbuseEventRepository implements AbuseEventRepositoryInterface
{
    private $db;
    public function __construct($connection) { $this->db = $connection; }
    public function countFailures($action, $subjectHash, $since)
    {
        $result = $this->prepared(
            "SELECT COUNT(*) AS total FROM tbl_abuse_events WHERE action_key=? "
            . "AND subject_hash=? AND outcome='failure' AND occurred_at>=?",
            array((string) $action, (string) $subjectHash, $this->date($since)),
            MDB2_PREPARE_RESULT
        );
        $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        $result->free();
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
    public function purgeExpired($now)
    {
        return $this->execute(
            'DELETE FROM tbl_abuse_events WHERE expires_at<=?',
            array($this->date($now))
        );
    }
    private function execute($sql, array $args)
    {
        return (int) $this->prepared($sql, $args, MDB2_PREPARE_MANIP);
    }
    private function prepared($sql, array $args, $mode)
    {
        $statement = $this->db->prepare($sql, null, $mode);
        if (PEAR::isError($statement)) {
            throw new RuntimeException($statement->getMessage());
        }
        $result = $statement->execute($args);
        $statement->free();
        if (PEAR::isError($result)) {
            throw new RuntimeException($result->getMessage());
        }
        return $result;
    }
    private function date($timestamp)
    {
        return gmdate('Y-m-d H:i:s', (int) $timestamp);
    }
}
