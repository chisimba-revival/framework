<?php
/** Disposable schema checks against the recorded MDB2 DDL contract (MariaDB 10.11.18). */
require_once $app.'/classes/core/chisimbadatabaseschema.php';
$schema=new ChisimbaDatabaseSchema($table);
$baseline=json_decode(file_get_contents(__DIR__.'/schema-baseline.json'),true,512,JSON_THROW_ON_ERROR);
$definitions=array_merge(glob('/modules/kanban/sql/*.sql'),glob('/modules/knowledgemap/sql/*.sql'),glob('/modules/questiongenerator/sql/*.sql'));
ensureBaseline(count($definitions)>8,'Module schema fixtures missing');
foreach ($definitions as $definition) {
    $fields=$options=$tableIndexes=[];include $definition;
    $schema->createTable($tablename,$fields,$options);
    $columns=function($name) use($table) {return $table->selectExactPrepared('SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION',[$name]);};
    ensureBaseline(($baseline[$tablename] ?? null)===$columns($tablename),'Schema differs from MDB2: '.$tablename.' '.json_encode([$baseline[$tablename] ?? null,$columns($tablename)]));
    foreach ($tableIndexes as $name=>$index) $schema->createIndex($tablename,$name,$index);
}
$schema->createTable('alter_fixture',['id'=>['type'=>'text','length'=>32]]);
$table->executePrepared('INSERT INTO alter_fixture (id) VALUES (?)',['preserve']);
$changes=['add'=>['body'=>['type'=>'text','length'=>40,'notnull'=>true,'default'=>"O'Reilly"]]];
$schema->alterTable('alter_fixture',$changes,true);
ensureBaseline(!in_array('body',$schema->columns('alter_fixture'),true),'Schema check wrote data');
$schema->alterTable('alter_fixture',$changes,false);
ensureBaseline($table->selectExactPrepared('SELECT body FROM alter_fixture')[0]['body']==="O'Reilly",'DDL default lost quoting');
try {$schema->alterTable('alter_fixture',['add'=>['ok'=>['type'=>'text']],'bogus'=>[]]);throw new Exception('Invalid schema accepted');} catch(InvalidArgumentException $expected){}
ensureBaseline(!in_array('ok',$schema->columns('alter_fixture'),true),'Invalid alteration partially applied');
$table->transaction(function() use($schema){try{$schema->dropTable('alter_fixture');throw new Exception('DDL permitted in transaction');}catch(LogicException $expected){}});
ensureBaseline($table->selectPrepared('SELECT id FROM alter_fixture')[0]['id']==='preserve','Schema guard lost data');
$schema->dropTable('alter_fixture');
echo "PASS: module schema definitions match recorded MDB2 column semantics; DDL validation, dry-run and transaction guard\n";
