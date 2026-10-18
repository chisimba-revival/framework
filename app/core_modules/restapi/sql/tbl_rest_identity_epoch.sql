<?php
/**
 * Identity epoch, owned by the REST API module.
 *
 * The epoch is a per-user counter that only ever rises. It exists because a
 * revocation rule needs two checks, not one: a denylist entry records who was
 * revoked and when, and a token is refused if its epoch is lower than the epoch
 * in force at that moment, OR if it was issued before the revocation time. The
 * timestamp alone cannot see a reduction of somebody's roles — nothing about
 * their permissions changed, so nothing changed about when their tokens were
 * issued — and an epoch alone cannot see a deactivation followed by a
 * reactivation, because reactivation may leave the roles exactly as they were.
 *
 * It lives in a restapi-owned table rather than as a column on tbl_users,
 * because tbl_users belongs to another module and this is not an attribute of a
 * person, it is an attribute of what this API has issued against them. Adding a
 * column to another module's table for a local concern couples the two in the
 * direction that is hardest to undo.
 *
 * user_id is the Chisimba logical identifier (tbl_users.userid, a varchar), for
 * the same reason tbl_rest_api_keys stores it that way: the numeric primary key
 * is not what tokens carry, and coercing one into the other loses the identity
 * the rest of the system actually uses.
 *
 * An installation that has never had an epoch recorded for a user has an epoch
 * of zero rather than no epoch at all, so a token minted before this table
 * existed is still readable and simply carries epoch 0.
 */

$tablename = 'tbl_rest_identity_epoch';

$options = array(
    'comment' => 'REST API identity epoch, used to detect role reductions',
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
    'epoch' => array(
        'type' => 'integer',
        'length' => 8,
        'notnull' => TRUE,
        'default' => 0
    ),
    // The grants as they stood when this epoch was recorded, hashed. This is
    // what makes the epoch move by itself: the API compares a user's current
    // grants against this fingerprint whenever it mints a token, and raises the
    // epoch when they differ.
    //
    // Doing it this way rather than raising the epoch from wherever permissions
    // change catches every source of change — the group admin screens, a
    // direct database edit, an LDAP synchronisation, a module not yet written —
    // rather than only the two or three places somebody remembered to call.
    'grants_fingerprint' => array(
        'type' => 'text',
        'length' => 64,
        'notnull' => FALSE
    ),
    'updated_at' => array(
        'type' => 'timestamp',
        'notnull' => TRUE
    )
);

$tableIndexes = array();
$tableIndexes['rest_identity_epoch_user'] = array(
    'unique' => TRUE,
    'fields' => array('user_id' => array())
);
