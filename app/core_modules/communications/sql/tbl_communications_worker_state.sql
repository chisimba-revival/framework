<?php
/** Communications worker heartbeat used by operational dashboards. */
$tablename = 'tbl_communications_worker_state';
$options = array('comment' => 'Communications worker heartbeat', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'status' => array('type' => 'text', 'length' => 24, 'notnull' => TRUE),
    'last_run_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'selected_count' => array('type' => 'integer', 'notnull' => TRUE, 'default' => 0),
    'sent_count' => array('type' => 'integer', 'notnull' => TRUE, 'default' => 0),
    'retried_count' => array('type' => 'integer', 'notnull' => TRUE, 'default' => 0),
    'failed_count' => array('type' => 'integer', 'notnull' => TRUE, 'default' => 0),
    'date_updated' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array('communications_worker_state_primary' => array('primary' => TRUE, 'fields' => array('id' => array())));
?>
