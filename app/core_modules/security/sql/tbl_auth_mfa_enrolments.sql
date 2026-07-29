<?php
$tablename = 'tbl_auth_mfa_enrolments';
$options = array(
    'comment' => 'Security-owned multifactor enrolments',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'user_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'factor_type' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'encrypted_secret' => array('type' => 'clob', 'notnull' => TRUE),
    'secret_nonce' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'enrolled_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'verified_at' => array('type' => 'timestamp'),
    'last_accepted_step' => array('type' => 'integer'),
    'disabled_at' => array('type' => 'timestamp')
);
$tableIndexes = array(
    'auth_mfa_enrolments_primary' => array(
        'primary' => TRUE,
        'fields' => array('id' => array())
    ),
    'auth_mfa_user_factor' => array(
        'unique' => TRUE,
        'fields' => array('user_id' => array(), 'factor_type' => array())
    )
);
?>
