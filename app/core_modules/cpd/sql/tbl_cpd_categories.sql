<?php
/** CPD scheme-category table definition. @author Derek Keats @package cpd */
$tablename = 'tbl_cpd_categories';
$options = array('comment' => 'CPD scheme categories', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'scheme_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'category_key' => array('type' => 'text', 'length' => 100, 'notnull' => TRUE),
    'name' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'description' => array('type' => 'clob'),
    'status' => array('type' => 'text', 'length' => 24, 'notnull' => TRUE, 'default' => 'active'),
    'created_by' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'date_created' => array('type' => 'timestamp', 'notnull' => TRUE),
    'date_updated' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'cpd_categories_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'cpd_categories_scheme_key' => array('unique' => TRUE, 'fields' => array('scheme_id' => array(), 'category_key' => array())),
    'cpd_categories_scheme' => array('fields' => array('scheme_id' => array()))
);
?>
