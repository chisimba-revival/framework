<?php
/** Public course presentation, explicitly published independently of course content. @author Derek Keats */
$tablename = 'tbl_context_marketing';
$options = array('comment'=>'Public course marketing pages','collate'=>'utf8_general_ci','character_set'=>'utf8');
$fields = array(
 'id'=>array('type'=>'text','length'=>32,'notnull'=>true),
 'contextcode'=>array('type'=>'text','length'=>255,'notnull'=>true),
 'content_json'=>array('type'=>'clob','notnull'=>true),
 'published'=>array('type'=>'integer','default'=>0,'notnull'=>true),
 'updated_at'=>array('type'=>'timestamp','notnull'=>true)
);
$tableIndexes = array(
 'context_marketing_primary'=>array('primary'=>true,'fields'=>array('id'=>array())),
 'context_marketing_context'=>array('unique'=>true,'fields'=>array('contextcode'=>array()))
);
