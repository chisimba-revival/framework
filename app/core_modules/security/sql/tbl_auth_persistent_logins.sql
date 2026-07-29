<?php
$tablename = 'tbl_auth_persistent_logins';
$options = array(
    'comment' => 'Security-owned rotating persistent-login tokens',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'user_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'selector' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'verifier_hash' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'issued_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'expires_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'last_used_at' => array('type' => 'timestamp'),
    'revoked_at' => array('type' => 'timestamp'),
    'replaced_by_id' => array('type' => 'text', 'length' => 32)
);
$tableIndexes = array(
    'auth_persistent_primary' => array(
        'primary' => TRUE,
        'fields' => array('id' => array())
    ),
    'auth_persistent_selector' => array(
        'unique' => TRUE,
        'fields' => array('selector' => array())
    ),
    'auth_persistent_user' => array(
        'fields' => array('user_id' => array())
    ),
    'auth_persistent_expiry' => array(
        'fields' => array('expires_at' => array())
    )
);
?>
