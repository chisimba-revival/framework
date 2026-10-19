<?php
/** In-memory group service tests; never establishes an application session. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {}
require dirname(__DIR__).'/classes/groupservice_class_inc.php';
class ContextGroupFixture {
 public $admin=true;public $writes=[];public $failAt=0;public $foreign=false;
 public function isLoggedIn(){return true;}public function isAdmin(){return $this->admin;}
 public function selectPrepared($sql,$args){
  if(str_contains($sql,'SELECT group_id, group_define_name'))return [['group_id'=>11,'group_define_name'=>'fixture'],['group_id'=>12,'group_define_name'=>'fixture^Lecturers']];
  return [['group_id'=>$this->foreign?99:11,'subgroup_id'=>12]];
 }
 public function executePrepared($sql,$args){$this->writes[]=$sql;if(count($this->writes)===$this->failAt)throw new RuntimeException('injected write failure');}
 public function transactionWithSavepoint($fn){$before=$this->writes;try{return $fn();}catch(Throwable $e){$this->writes=$before;throw $e;}}
}
function check($ok,$message){if(!$ok)throw new RuntimeException($message);echo "PASS $message\n";}
$groups=new groupservice();$db=new ContextGroupFixture();
foreach(['objUser'=>$db,'objContext'=>new class {public function getContextDetails($code){return false;}}] as $name=>$value){$p=new ReflectionProperty(groupservice::class,$name);$p->setValue($groups,$value);}
$db->admin=false;try{$groups->deleteContextGroups('fixture');throw new LogicException('denial missing');}catch(DomainException $e){}
check(!$db->writes,'non-administrator orphan repair denied');$db->admin=true;
$db->foreign=true;try{$groups->deleteContextGroups('fixture');throw new LogicException('foreign group accepted');}catch(RuntimeException $e){}
check(!$db->writes,'shared parent rejected before writes');$db->foreign=false;
$db->failAt=3;try{$groups->deleteContextGroups('fixture');throw new LogicException('failure missing');}catch(RuntimeException $e){}
check(!$db->writes,'failed group relationship cleanup rolls back');$db->failAt=0;
check($groups->deleteContextGroups('fixture')===true&&count($db->writes)===4,'canonical group family and relationships removed');
try{$groups->deleteContextGroups('Site Admin');throw new LogicException('protected group accepted');}catch(DomainException $e){}
check(count($db->writes)===4,'site administrator group protected');
