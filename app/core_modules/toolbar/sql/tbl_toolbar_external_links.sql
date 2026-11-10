<?php
/** Operator-authored destinations, independent of generated module defaults. */
$tablename = 'tbl_toolbar_external_links';
$options = ['comment'=>'Configured external site navigation', 'collate'=>'utf8mb4_unicode_ci', 'character_set'=>'utf8mb4'];
$fields = [
 'id'=>['type'=>'text','length'=>32,'notnull'=>true],
 'placement'=>['type'=>'text','length'=>16,'notnull'=>true,'default'=>'site'],
 'label'=>['type'=>'text','length'=>150,'notnull'=>true],
 'url'=>['type'=>'text','length'=>2048,'notnull'=>true],
 'group_key'=>['type'=>'text','length'=>100,'notnull'=>true,'default'=>''],
 'group_label'=>['type'=>'text','length'=>150,'notnull'=>true,'default'=>''],
 'position'=>['type'=>'integer','notnull'=>true,'default'=>10],
 'audience'=>['type'=>'text','length'=>16,'notnull'=>true,'default'=>'everyone'],
];
$tableIndexes = ['toolbar_external_primary'=>['primary'=>true,'fields'=>['id'=>[]]]];
