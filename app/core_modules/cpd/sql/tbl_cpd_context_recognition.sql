<?php
/** Context CPD-recognition table definition. @author Derek Keats @package cpd */
$tablename = 'tbl_cpd_context_recognition';
$options = array('comment' => 'Versioned context CPD recognition', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'context_code' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'scheme_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'category_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'version_number' => array('type' => 'integer', 'notnull' => TRUE),
    'points' => array('type' => 'decimal', 'length' => 12, 'scale' => 3, 'notnull' => TRUE),
    'valid_from' => array('type' => 'date'),
    'valid_until' => array('type' => 'date'),
    'status' => array('type' => 'text', 'length' => 24, 'notnull' => TRUE, 'default' => 'active'),
    'reason' => array('type' => 'clob', 'notnull' => TRUE),
    'created_by' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'date_created' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'cpd_recognition_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'cpd_recognition_version' => array('unique' => TRUE, 'fields' => array('context_code' => array(), 'scheme_id' => array(), 'version_number' => array())),
    'cpd_recognition_context' => array('fields' => array('context_code' => array(), 'status' => array()))
);
?>
