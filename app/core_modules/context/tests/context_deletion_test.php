<?php
/** Behavioural orchestration test. In-memory identities and storage; no runtime session. */
$GLOBALS['kewl_entry_point_run']=true;
class dbTable {
    public $rows=['course'=>true,'messages'=>true,'groups'=>true]; public $services=[]; public $fail=false;
    public function getObject($name,$module){return $this->services[$name];}
    public function selectPrepared($sql,$args=[]){
        if(str_contains($sql,'ENGINE'))return [['engine'=>'InnoDB']];
        if(str_contains($sql,'@@in_transaction'))return [['active'=>1]];
        return $this->rows['course']?[['contextcode'=>'fixture']]:[];
    }
    public function executePrepared($sql,$args){if(str_contains($sql,'DELETE FROM tbl_context WHERE'))$this->rows['course']=false;}
    public function transactionWithSavepoint($fn){$before=$this->rows;try{return $fn();}catch(Throwable $e){$this->rows=$before;throw $e;}}
}
require dirname(__DIR__).'/classes/contextdeletion_class_inc.php';
function check($ok,$label){if(!$ok)throw new RuntimeException($label);echo "PASS $label\n";}
$d=new contextdeletion();
$actor=new class {public $admin=true;public $logged=true;public function isLoggedIn(){return $this->logged;}public function isAdmin(){return $this->admin;}public function userId(){return 'fixture';}};
$policy=new class {public $allow=true;public function canManageAuthorRoster($c,$u){return $this->allow;}};
$participant=new class($d) {private $d;public function __construct($d){$this->d=$d;}public function contextDeletionTables(){return [];}public function deleteForContext($c){$this->d->rows['messages']=false;return [];}};
$groups=new class($d) {private $d;public function __construct($d){$this->d=$d;}public function deleteContextGroups($c){if($this->d->fail)throw new RuntimeException('injected group failure');$this->d->rows['groups']=false;return true;}};
$d->services=['user'=>$actor,'contextauthorservice'=>$policy,'modules'=>new class {public function checkIfRegistered($m){return $m==='messaging';}},'conversationstore'=>$participant,'groupservice'=>$groups];
$initial=$d->rows;
$d->fail=true;try{$d->remove('fixture');throw new LogicException('failure missing');}catch(RuntimeException $e){check($e->getMessage()==='injected group failure','group failure propagated');}
check($d->rows===$initial,'course and messages roll back after group failure');
$d->fail=false;$policy->allow=false;try{$d->remove('fixture');throw new LogicException('unauthorised');}catch(DomainException $e){}
check($d->rows===$initial,'unauthorised deletion leaves records unchanged');$policy->allow=true;
try{$d->remove('fixture',true);throw new LogicException('existing repair');}catch(DomainException $e){}
check($d->rows===$initial,'orphan repair refuses an existing course');
check($d->remove('fixture')===true && !array_filter($d->rows),'successful deletion removes course last');
check($d->remove('fixture')===false,'repeat deletion does not report false success');
$actor->admin=false;try{$d->remove('fixture',true);throw new LogicException('non-admin repair');}catch(DomainException $e){}
check($d->rows['course']===false,'orphan repair restricted to administrator');
