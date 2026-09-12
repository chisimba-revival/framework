<?php
/** Shared classification storage. @author Derek Keats */
$tablename = 'tbl_classification_terms';
$options = array('type'=>'innodb','collate'=>'utf8mb4_bin','charset'=>'utf8mb4');
$fields = array(
    'id'=>array('type'=>'text','length'=>32,'notnull'=>true),
    'vocabulary_id'=>array('type'=>'text','length'=>32,'notnull'=>true),
    'name'=>array('type'=>'text','length'=>150,'notnull'=>true),
    'slug'=>array('type'=>'text','length'=>190,'notnull'=>true),
    'parent_id'=>array('type'=>'text','length'=>32,'notnull'=>true),
);
$name = 'classification_terms_lookup';
$indexes = array('fields'=>array('vocabulary_id'=>array(),'name'=>array()));
