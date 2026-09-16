<?php
/** Unique declarations use MDB2's constraint API; ordinary/legacy primary paths remain unchanged. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {}
class PEAR { public static function isError($value){return $value instanceof Exception;} }
set_include_path(dirname(__DIR__,2).'/app'.PATH_SEPARATOR.get_include_path());
require dirname(__DIR__,2).'/app/classes/core/dbtablemanager_class_inc.php';
$db=new class {public $calls=[];public $fail=false;public function mgCreateConstraint(...$args){$this->calls[]='constraint';return $this->fail?new Exception('fixture'):true;}public function mgCreateIndex(...$args){$this->calls[]='index';return true;}};
$manager=new dbTableManager();$property=new ReflectionProperty(dbTableManager::class,'_db');$property->setValue($manager,$db);
$manager->createTableIndex('fixture','identity',['unique'=>true,'fields'=>['id'=>[]]]);
$manager->createTableIndex('fixture','lookup',['fields'=>['state'=>[]]]);
$manager->createTableIndex('fixture','legacy_primary',['primary'=>true,'fields'=>['id'=>[]]]);
if($db->calls!==['constraint','index','index'])throw new RuntimeException('Incorrect index API');
$db->fail=true;$failed=false;try{$manager->createTableIndex('fixture','identity',['unique'=>true,'fields'=>['id'=>[]]]);}catch(RuntimeException $e){$failed=true;}
if(!$failed)throw new RuntimeException('Constraint failure must not report success');
echo "PASS unique-constraint dispatch, unchanged legacy index paths and fail-closed errors.\n";
