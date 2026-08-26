<?php

// Table Name
$tablename = 'tbl_sysconfig_properties';

//Options line for comments, encoding and character set
$options = array('comment' => 'system properties', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');

// Fields
$fields = array(
    'id' => array(
        'type' => 'text',
        'length' => 32,
        ),
    'pmodule' => array(
        'type' => 'text',
        'length' => 64,
        'notnull' => 1,
        'default' => 'unknown'
        ),
    'pname' => array(
        'type' => 'text',
        'length' => 128,
        'notnull' => 1,
        'default' => 'novalue'
        ),
    'pvalue' => array(
        'type' => 'clob',
        'length' => 1024,
        'notnull' => 0
        ),
    'pdesc' => array(
        'type' => 'clob'
        ),
    'creatorId' => array(
        'type' => 'text',
        'length' => 25
        ),
    'dateCreated' => array(
        'type' => 'timestamp',
        ),
    'modifierId' => array(
        'type' => 'text',
        'length' => 25
        ),
    'dateModified' => array(
        'type' => 'timestamp'
        )
    );

// A system parameter has one canonical owner and value per module.
$name = 'sysconfig_module_parameter';
$indexes = array(
    'unique' => TRUE,
    'fields' => array(
        'pmodule' => array(),
        'pname' => array()
    )
);

?>
