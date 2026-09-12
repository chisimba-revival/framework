<?php
/** Shared classification storage. @author Derek Keats */
$tablename = 'tbl_classification_vocabularies';
$options = array('type'=>'innodb','collate'=>'utf8mb4_bin','charset'=>'utf8mb4');
$fields = array(
    'id'=>array('type'=>'text','length'=>32,'notnull'=>true),
    'scope_type'=>array('type'=>'text','length'=>16,'notnull'=>true),
    'scope_id'=>array('type'=>'text','length'=>64,'notnull'=>true),
    'kind'=>array('type'=>'text','length'=>16,'notnull'=>true),
);
$name = 'classification_vocabularies_lookup';
$indexes = array('fields'=>array('scope_type'=>array(),'scope_id'=>array()));
