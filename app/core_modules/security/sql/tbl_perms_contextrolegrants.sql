<?php
// Table Name
$tablename = 'tbl_perms_contextrolegrants';

// Options line for comments, encoding and character set
$options = array(
    'comment' => 'Canonical contextual role grant templates',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);

// Fields
$fields = array(
    'id' => array(
        'type' => 'text',
        'length' => 32
    ),
    'right_id' => array(
        'type' => 'integer',
        'length' => 50
    ),
    'role_name' => array(
        'type' => 'text',
        'length' => 50
    ),
);

$name = 'context_role_right';
$indexes = array(
    'fields' => array(
        'right_id' => array(),
        'role_name' => array(),
    )
);
?>
