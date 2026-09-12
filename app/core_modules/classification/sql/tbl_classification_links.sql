<?php
/** Shared classification storage. @author Derek Keats */
$tablename = 'tbl_classification_links';
$options = array('type'=>'innodb','collate'=>'utf8mb4_bin','charset'=>'utf8mb4');
$fields = array(
    'id'=>array('type'=>'text','length'=>32,'notnull'=>true),
    'vocabulary_id'=>array('type'=>'text','length'=>32,'notnull'=>true),
    'term_id'=>array('type'=>'text','length'=>32,'notnull'=>true),
    'module_id'=>array('type'=>'text','length'=>64,'notnull'=>true),
    'item_id'=>array('type'=>'text','length'=>64,'notnull'=>true),
);
$name = 'classification_links_lookup';
$indexes = array('fields'=>array('vocabulary_id'=>array(),'term_id'=>array()));
