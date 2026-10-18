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
    /**
     * Declare the bind types so MDB2 does not fall back to a null type.
     *
     * MDB2 resolves a type per placeholder when binding; a placeholder with no
     * recorded type resolves to null and reaches
     * MDB2\Driver\Datatype::mapPrepareDatatype(null), which indexes
     * $options['datatype_map'][null] and raises a PHP 8.1 deprecation. Quoting
     * already infers the type, so declaring it changes nothing else.
     *
     * Only names MDB2 recognises may be declared ('boolean', 'integer',
     * 'float', 'blob'); an unknown name makes execute() fail with
     * "not supported". Everything else is bound as text.
     */
    private function parameterTypes(array $args)
    {
        $types = array();

        foreach ($args as $value) {
            if (is_int($value)) {
                $types[] = 'integer';
            } elseif (is_bool($value)) {
                $types[] = 'boolean';
            } elseif (is_float($value)) {
                $types[] = 'float';
            } else {
                $types[] = 'text';
            }
        }

        return $types;
    }

    private function prepared($sql, array $args, $mode)
    {
        $statement = $this->db->prepare($sql, $this->parameterTypes($args), $mode);
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
