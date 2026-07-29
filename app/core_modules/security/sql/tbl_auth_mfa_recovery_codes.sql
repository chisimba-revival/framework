<?php
$tablename = 'tbl_auth_mfa_recovery_codes';
$options = array(
    'comment' => 'Security-owned multifactor recovery codes',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'user_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'code_hash' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'created_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'used_at' => array('type' => 'timestamp')
);
$tableIndexes = array(
    'auth_mfa_recovery_primary' => array(
        'primary' => TRUE,
        'fields' => array('id' => array())
    ),
    'auth_mfa_recovery_user' => array(
        'fields' => array('user_id' => array())
    )
);
?>
