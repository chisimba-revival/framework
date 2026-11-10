<?php
/** Category position belongs to the heading, never its first child. */
$tablename = 'tbl_toolbar_categories';
$options = ['comment'=>'Site navigation categories', 'collate'=>'utf8mb4_unicode_ci', 'character_set'=>'utf8mb4'];
$fields = [
 'id'=>['type'=>'text','length'=>32,'notnull'=>true],
 'group_key'=>['type'=>'text','length'=>100,'notnull'=>true],
 'label'=>['type'=>'text','length'=>150,'notnull'=>true],
 'position'=>['type'=>'integer','notnull'=>true],
 // Immutable migration anchors preserve ties with existing top-level links.
 'sort_module'=>['type'=>'text','length'=>64,'notnull'=>true,'default'=>''],
 'sort_id'=>['type'=>'text','length'=>64,'notnull'=>true],
];
$tableIndexes = ['toolbar_category_key'=>['unique'=>true,'fields'=>['group_key'=>[]]]];
