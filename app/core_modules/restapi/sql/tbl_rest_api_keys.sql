<?php
/**
 * REST API long-lived API key storage.
 *
 * user_id is the Chisimba logical user identifier (tbl_users.userid), which is a
 * varchar(25), NOT the numeric storage key (tbl_users.puid). Storing an int here
 * silently coerced non-numeric identifiers to 0 under STRICT_TRANS_TABLES, so
 * only accounts whose userid happened to be numeric could ever use an API key.
 *
 * Only the hash of the key is retained. key_prefix exists so an operator can
 * identify a key in a listing without the secret ever being recoverable.
 */

$tablename = 'tbl_rest_api_keys';

$options = array(
    'comment' => 'REST API long-lived access keys',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);

$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => 11,
        'notnull' => TRUE,
        'unsigned' => TRUE,
        'autoincrement' => TRUE
    ),
    'user_id' => array(
        'type' => 'text',
        'length' => 25,
        'notnull' => TRUE
    ),
    'name' => array(
        'type' => 'text',
        'length' => 100,
        'notnull' => TRUE
    ),
    'key_hash' => array(
        'type' => 'text',
        'length' => 64,
        'notnull' => TRUE
    ),
    'key_prefix' => array(
        'type' => 'text',
        'length' => 12,
        'notnull' => TRUE
    ),
    'scopes' => array(
        'type' => 'text',
        'length' => 1024
    ),
    'expires_at' => array(
        'type' => 'timestamp'
    ),
    'last_used_at' => array(
        'type' => 'timestamp'
    ),
    'created_at' => array(
        'type' => 'timestamp'
    ),
    'revoked_at' => array(
        'type' => 'timestamp'
    )
);

$tableIndexes = array(
    'rest_apikey_primary' => array(
        'primary' => TRUE,
        'fields' => array('id' => array())
    ),
    'rest_apikey_user' => array(
        'fields' => array('user_id' => array())
    ),
    'rest_apikey_hash' => array(
        'fields' => array('key_hash' => array())
    ),
    'rest_apikey_prefix' => array(
        'fields' => array('key_prefix' => array())
    )
);
?>
