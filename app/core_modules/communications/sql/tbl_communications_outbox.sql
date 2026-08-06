<?php
/**
 * Communications outbox table definition.
 *
 * @author  Derek Keats
 * @package communications
 */
$tablename = 'tbl_communications_outbox';
$options = array(
    'comment' => 'Canonical communications outbox',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'idempotency_key' => array('type' => 'text', 'length' => 191),
    'channel' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE, 'default' => 'email'),
    'transport' => array('type' => 'text', 'length' => 32),
    'recipient' => array('type' => 'text', 'length' => 320, 'notnull' => TRUE),
    'recipient_name' => array('type' => 'text', 'length' => 255),
    'sender' => array('type' => 'text', 'length' => 320, 'notnull' => TRUE),
    'sender_name' => array('type' => 'text', 'length' => 255),
    'subject' => array('type' => 'text', 'length' => 998, 'notnull' => TRUE),
    'body_text' => array('type' => 'clob', 'notnull' => TRUE),
    'body_html' => array('type' => 'clob'),
    'metadata_json' => array('type' => 'clob'),
    'status' => array('type' => 'text', 'length' => 24, 'notnull' => TRUE, 'default' => 'queued'),
    'attempts' => array('type' => 'integer', 'notnull' => TRUE, 'default' => 0),
    'available_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'claimed_at' => array('type' => 'timestamp'),
    'sent_at' => array('type' => 'timestamp'),
    'last_error' => array('type' => 'clob'),
    'date_created' => array('type' => 'timestamp', 'notnull' => TRUE),
    'date_updated' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'communications_outbox_primary' => array(
        'primary' => TRUE,
        'fields' => array('id' => array())
    ),
    'communications_idempotency' => array(
        'unique' => TRUE,
        'fields' => array('idempotency_key' => array())
    ),
    'communications_ready' => array(
        'fields' => array('status' => array(), 'available_at' => array())
    )
);
?>
