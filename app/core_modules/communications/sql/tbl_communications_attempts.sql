<?php
/**
 * Communications delivery-attempt table definition.
 *
 * @author  Derek Keats
 * @package communications
 */
$tablename = 'tbl_communications_attempts';
$options = array(
    'comment' => 'Communications delivery attempts',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'outbox_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'attempt_number' => array('type' => 'integer', 'notnull' => TRUE),
    'transport' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'outcome' => array('type' => 'text', 'length' => 24, 'notnull' => TRUE),
    'provider_reference' => array('type' => 'text', 'length' => 255),
    'response_code' => array('type' => 'integer'),
    'error_detail' => array('type' => 'clob'),
    'date_created' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'communications_attempt_primary' => array(
        'primary' => TRUE,
        'fields' => array('id' => array())
    ),
    'communications_attempt_outbox' => array(
        'fields' => array('outbox_id' => array(), 'attempt_number' => array())
    )
);
?>
