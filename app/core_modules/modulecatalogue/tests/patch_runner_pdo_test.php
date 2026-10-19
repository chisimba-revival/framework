<?php
/** Real PDO/XML regression fixture. Only random test tables/module rows are touched. */
if(PHP_SAPI!=='cli'||getenv('CHISIMBA_PATCH_TEST')!=='1')exit("Set CHISIMBA_PATCH_TEST=1 in a disposable/local runtime.\n");
$GLOBALS['kewl_entry_point_run']=true;
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']=$argv[1]??'chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';$engine=new engine();
$real=$engine->getObject('patch','modulecatalogue');$db=$engine->getObject('modules','modulecatalogue');$schema=$engine->getObject('modulesadmin','modulecatalogue');
class FixturePatch extends patch {
 public $fixtures=[]; public $failReceipt=false;
 public function executePrepared($sql,array $parameters=[]): int {if($this->failReceipt && str_starts_with($sql,'INSERT INTO tbl_module_patches'))throw new RuntimeException('injected receipt failure');return parent::executePrepared($sql,$parameters);}
 public function getObject($name,$module=null){return $this->fixtures[$name]??parent::getObject($name,$module);}
}
function check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
$suffix=bin2hex(random_bytes(6));$module='patchtest_'.$suffix;$table='tbl_patchtest_'.$suffix;$xml=tempnam(sys_get_temp_dir(),'patch-xml-');
$files=new class($module,$xml) {
 public $target='3.036';public $deps=[];
 public function __construct(public $module,public $file){}
 public function findRegisterFile($m){return $m;}
 public function readRegisterFile($f){return ['MODULE_ID'=>$this->module,'MODULE_VERSION'=>$this->target,'DEPENDS'=>$this->deps];}
 public function findSqlXML($m){return $this->file;}
 public function getLocalModulelist(){return [];}
};
$admin=new class($schema,$db) {
 public $fail=false;public $validations=0;public $executions=0;
 public function __construct(private $schema,private $db){}
 public function alterTable($t,$d,$check){if($check)$this->validations++;else $this->executions++;return $this->schema->alterTable($t,$d,$check);}
 public function installModule($r,$update){if($this->fail)return false;$this->db->executePrepared('UPDATE tbl_modules SET module_version=? WHERE module_id=?',[$r['MODULE_VERSION'],$r['MODULE_ID']]);return true;}
 public function getLastErrorCode(){return 'fixture_registration_failure';}
 public function loadData($m){}
};
$patch=new FixturePatch($engine,'modulecatalogue');$patch->fixtures=['modulefile'=>$files,'modules'=>$db,'modulesadmin'=>$admin];
$write=function($body)use($xml){file_put_contents($xml,$body);};
try {
 $schema->createTable($table,['id'=>['type'=>'text','length'=>32],'userid'=>['type'=>'text','length'=>10,'notnull'=>true],'modifierid'=>['type'=>'text','length'=>10,'notnull'=>true]],['type'=>'InnoDB']);
 $db->executePrepared('INSERT INTO tbl_modules (id,module_id,module_version) VALUES (?,?,?)',[$suffix,$module,'3.035']);
 $db->executePrepared('INSERT INTO '.$table.' (id,userid,modifierid) VALUES (?,?,?)',['keep','123','456']);
 try{$db->insert(['moduleid'=>$module,'version'=>3.036,'tablename'=>simplexml_load_string('<table>'.$table.'</table>'),'patchdata'=>['change'=>[]],'applied'=>date('Y-m-d')],'tbl_module_patches');throw new LogicException('old receipt accepted');}catch(InvalidArgumentException $e){check(true,'original non-scalar receipt failure reproduced');}
 $discussion=file_get_contents('packages/discussion/sql/sql_updates.xml');
 $write(str_replace('tbl_discussion_post_ratings',$table,$discussion));
 $admin->fail=true;
 try{$patch->applyUpdates($module);throw new LogicException('registration failure hidden');}catch(RuntimeException $e){check(str_contains($e->getMessage(),'registration failed'),'registration failure propagates');}
 $columns=$db->selectPrepared('SELECT COLUMN_NAME AS name,CHARACTER_MAXIMUM_LENGTH AS size FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME IN (?,?)',[$table,'userid','modifierid']);
 check(count($columns)===2&&!array_filter($columns,fn($r)=>(int)$r['size']!==32),'real Discussion XML widens both columns');
 $receipts=$db->selectPrepared('SELECT patchdata FROM tbl_module_patches WHERE moduleid=?',[$module]);
 check(count($receipts)===1&&json_decode($receipts[0]['patchdata'],true)['state']==='applied','single scalar applied receipt');
 check($patch->getVersion($module)==='3.035','failed registration does not advance version');
 $executions=$admin->executions;$admin->fail=false;$patch->applyUpdates($module);
 check($admin->executions===$executions,'retry after registration failure skips confirmed DDL');
 $patch->applyUpdates($module);check(count($db->selectPrepared('SELECT id FROM tbl_module_patches WHERE moduleid=?',[$module]))===1,'repeat has no duplicate receipt');
 check($db->selectPrepared('SELECT userid,modifierid FROM '.$table.' WHERE id=?',['keep'])===[['userid'=>'123','modifierid'=>'456']],'business row preserved');
 $files->target='3.037';$write('<updates><update><version>3.037</version><table>'.$table.'</table><data><insert><id>quote</id><userid>O\'Brien</userid><modifierid>safe</modifierid></insert><deleteRows><userid>O\'Brien</userid></deleteRows></data></update></updates>');
 $patch->applyUpdates($module);check(!$db->selectPrepared('SELECT id FROM '.$table.' WHERE id=?',['quote']),'bound deleteRows handles apostrophes');
 $files->target='3.038';$write('<updates><update><version>3.038</version><table>'.$table.'</table><data><insert><id>rollback</id><userid>safe</userid><modifierid>safe</modifierid></insert></data></update></updates>');
 $patch->failReceipt=true;
 try{$patch->applyUpdates($module);throw new LogicException('receipt failure hidden');}catch(RuntimeException $e){check($e->getMessage()==='injected receipt failure','receipt write error propagates');}
 check(!$db->selectPrepared('SELECT id FROM '.$table.' WHERE id=?',['rollback']),'DML rolls back with its receipt');$patch->failReceipt=false;
 $files->target='3.038';$write('<updates><update><version>3.038</version><table>'.$table.'</table><data><add><name>userid</name><type>text</type><length>32</length></add></data></update></updates>');
 try{$patch->applyUpdates($module);throw new LogicException('DDL failure hidden');}catch(Throwable $e){check(!$e instanceof LogicException,'DDL error propagates');}
 $executions=$admin->executions;try{$patch->applyUpdates($module);throw new LogicException('uncertain DDL retried');}catch(RuntimeException $e){check(str_contains($e->getMessage(),'incomplete receipt'),'uncertain DDL blocks automatic replay');}
 check($admin->executions===$executions&&$patch->getVersion($module)==='3.037','failed DDL neither repeats nor advances version');
 $write('<updates><broken>');try{$patch->applyUpdates($module);throw new LogicException('XML accepted');}catch(RuntimeException $e){check(str_contains($e->getMessage(),'Invalid module update XML'),'malformed XML throws without exiting');}
 $files->deps=['missing_fixture_dependency'];$result=$patch->applyUpdates($module);check(isset($result['unMetDep'])&&$result['missing']===['missing_fixture_dependency'],'dependency contract retained');
 echo "PASS patch runner PDO fixture completed\n";
} finally {
 $schema->dropTable($table);
 $db->executePrepared('DELETE FROM tbl_module_patches WHERE moduleid=?',[$module]);
 $db->executePrepared('DELETE FROM tbl_modules WHERE module_id=?',[$module]);
 unlink($xml);
}
