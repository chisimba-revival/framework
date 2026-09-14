<?php
/** Read-only local integration check: constant SELECTs, no table/data writes. */
if(PHP_SAPI!=='cli')exit(1);
$app=$argv[1]??dirname(__DIR__,2).'/app';chdir($app);
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true;require_once 'classes/core/engine_class_inc.php';$engine=new engine();$db=$engine->getDbObj();
error_reporting(E_ALL);
set_error_handler(static function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
require_once 'MDB2/Iterator.php';
foreach([MDB2_Iterator::class,MDB2_BufferedIterator::class] as $class){
 $result=$db->query("SELECT 'first' AS label UNION ALL SELECT 'second' AS label");
 if(MDB2::isError($result))throw new RuntimeException('Constant SELECT failed');
 $iterator=new $class($result,MDB2_FETCHMODE_ASSOC);
 $rows=array_values(iterator_to_array($iterator));
 if($rows!==[['label'=>'first'],['label'=>'second']])throw new RuntimeException('Driver traversal mismatch');
 if($class===MDB2_BufferedIterator::class){
  if(array_values(iterator_to_array($iterator))!==$rows)throw new RuntimeException('Driver rewind mismatch');
  $iterator->seek(1);if($iterator->current()!==['label'=>'second'])throw new RuntimeException('Driver seek mismatch');
 }
 unset($iterator);
 $result=$db->query("SELECT 'empty' AS label WHERE 1=0");
 $iterator=new $class($result,MDB2_FETCHMODE_ASSOC);
 if(iterator_to_array($iterator)!==[])throw new RuntimeException('Driver empty result mismatch');
 unset($iterator);
}
echo "PASS: actual local MDB2/mysqli read-only traversal, empty results, buffered rewind and seek\n";
