<?php
/** Public author biographies, separate from private account details. @author Derek Keats */
$tablename = 'tbl_userdetails_biographies';
$options = array('comment' => 'Reusable author biographies', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => true),
    'userid' => array('type' => 'text', 'length' => 25, 'notnull' => true),
    'biography' => array('type' => 'clob', 'notnull' => true),
    'links_json' => array('type' => 'clob', 'notnull' => true),
    'updated_at' => array('type' => 'timestamp', 'notnull' => true)
);
$tableIndexes = array(
    'userdetails_bio_primary' => array('primary' => true, 'fields' => array('id' => array())),
    'userdetails_bio_user' => array('unique' => true, 'fields' => array('userid' => array()))
);
