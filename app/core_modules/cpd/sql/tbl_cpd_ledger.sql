<?php
/** Append-only CPD points-ledger table definition. @author Derek Keats @package cpd */
$tablename = 'tbl_cpd_ledger';
$options = array('comment' => 'Immutable CPD points ledger', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'identity_user_id' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'context_code' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'scheme_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'category_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'recognition_id' => array('type' => 'text', 'length' => 32),
    'transaction_type' => array('type' => 'text', 'length' => 24, 'notnull' => TRUE),
    'points_delta' => array('type' => 'decimal', 'length' => 12, 'scale' => 3, 'notnull' => TRUE),
    'related_transaction_id' => array('type' => 'text', 'length' => 32),
    'idempotency_key' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'completion_basis' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'reason' => array('type' => 'clob', 'notnull' => TRUE),
    'effective_date' => array('type' => 'date', 'notnull' => TRUE),
    'allocated_by' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'date_created' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'cpd_ledger_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'cpd_ledger_idempotency' => array('unique' => TRUE, 'fields' => array('idempotency_key' => array())),
    'cpd_ledger_identity' => array('fields' => array('identity_user_id' => array(), 'scheme_id' => array(), 'effective_date' => array())),
    'cpd_ledger_related' => array('fields' => array('related_transaction_id' => array()))
);
?>
