<?php
/**
 * REST API refresh token storage.
 *
 * user_id is the Chisimba logical user identifier (tbl_users.userid), which is a
 * varchar(25), NOT the numeric storage key (tbl_users.puid). Storing an int here
 * silently coerced non-numeric identifiers to 0 under STRICT_TRANS_TABLES, which
 * made every non-numeric account fail to log in with an opaque database error.
 */

$tablename = 'tbl_rest_refresh_tokens';

$options = array(
    'comment' => 'REST API refresh tokens with rotation and revocation',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);

$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => 11,
        'unsigned' => TRUE
    ),
    'user_id' => array(
        'type' => 'text',
        'length' => 25,
        'notnull' => TRUE
    ),
    'token_hash' => array(
        'type' => 'text',
        'length' => 64,
        'notnull' => TRUE
    ),
    'expires_at' => array(
        'type' => 'timestamp',
        'notnull' => TRUE
    ),
    'created_at' => array(
        'type' => 'timestamp'
    ),
    'last_used_at' => array(
        'type' => 'timestamp'
    ),
    'user_agent' => array(
        'type' => 'text',
        'length' => 512
    ),
    'ip_address' => array(
        'type' => 'text',
        'length' => 45
    ),
    'device_id' => array(
        'type' => 'text',
        'length' => 255
    ),
    'revoked_at' => array(
        'type' => 'timestamp'
    )
);

$tableIndexes = array(
    'rest_refresh_primary' => array(
        'primary' => TRUE,
        'fields' => array('id' => array())
    ),
    'rest_refresh_user' => array(
        'fields' => array('user_id' => array())
    ),
    'rest_refresh_token_hash' => array(
        'fields' => array('token_hash' => array())
    ),
    'rest_refresh_expiry' => array(
        'fields' => array('expires_at' => array())
    ),
    'rest_refresh_device' => array(
        'fields' => array('device_id' => array())
    ),
    'rest_refresh_user_device' => array(
        'fields' => array('user_id' => array(), 'device_id' => array())
    )
);
?>
