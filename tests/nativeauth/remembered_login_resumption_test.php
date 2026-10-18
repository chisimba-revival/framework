<?php
require_once __DIR__.'/../../app/core_modules/security/classes/nativeauth/nativesessionservice.php';
require_once __DIR__.'/../../app/core_modules/security/classes/nativeauth/rememberedloginresumptionservice.php';
require_once __DIR__.'/../../app/core_modules/security/classes/nativeauth/csrftokenservice.php';
function checkResume($value,$label){if(!$value)throw new RuntimeException($label);echo "PASS: $label\n";}
function fixtureResume($active='1',$status='not_required',$rotate=true){
 $backend=new class {public $values=[];public function getSession($k,$d=null){return $this->values[$k]??$d;}public function setSession($k,$v){$this->values[$k]=$v;}public function unsetSession($k){unset($this->values[$k]);}};
 $session=new NativeSessionService($backend,fn()=>$rotate);
 $persistent=new class {public $calls=0,$cleared=false,$revoked=false,$identity='user';public function restoreAndRotate($now){$this->calls++;return $this->identity;}public function clear(){$this->cleared=true;}public function revokeAllForUser($id,$now){$this->revoked=true;}};
 $users=new class($active) {public function __construct(public $active){}public function findByUserId($id){return $this->active==='missing'?null:['userid'=>$id,'username'=>'tester','isactive'=>$this->active];}};
 $policy=new class($status){public function __construct(public $status){}public function evaluate($proof,$context){return ['status'=>$this->status];}};
 $context=new class {public function resolve($proof){return [];}};
 return [new RememberedLoginResumptionService($persistent,$session,$users,$policy,$context),$session,$persistent,$backend];
}
[$resume,$session,$persistent,$backend]=fixtureResume();
$oldBackend=clone $backend;$oldTokens=new CsrfTokenService($oldBackend);$oldToken=$oldTokens->issueForSession('editing');
checkResume($resume->resume(time()) && $session->getUserId()==='user','remembered credential establishes canonical identity');
checkResume(!isset($backend->values['roles'])&&!isset($backend->values['permissions'])&&!isset($backend->values['is_admin']),'restoration does not cache authority');
$newTokens=new CsrfTokenService($backend);
checkResume(!$newTokens->consume('editing',$oldToken),'previous session token remains invalid after restoration');
checkResume($newTokens->consume('editing',$newTokens->issueForSession('editing')),'fresh token permits protected form submission');
checkResume($resume->resume(time())&&$persistent->calls===1,'authenticated requests do not rotate remembered cookie repeatedly');
foreach(['0','missing'] as $active){[$r,$s,$p]=fixtureResume($active);checkResume(!$r->resume(time())&&!$s->isAuthenticated()&&$p->revoked,'inactive/missing account rejected: '.$active);}
foreach(['challenge_required','invalid'] as $status){[$r,$s,$p]=fixtureResume('1',$status);checkResume(!$r->resume(time())&&!$s->isAuthenticated()&&$p->cleared,'current MFA policy cannot be bypassed: '.$status);}
[$r,$s,$p]=fixtureResume('1','grace');checkResume($r->resume(time()),'policy grace is honoured');
[$r,$s,$p]=fixtureResume('1','not_required',false);checkResume(!$r->resume(time())&&!$s->isAuthenticated()&&$p->cleared,'session rotation failure fails closed');
[$r,$s,$p]=fixtureResume();$p->identity=false;checkResume(!$r->resume(time())&&!$s->isAuthenticated(),'expired/revoked credential grants no identity');
$engine=file_get_contents(__DIR__.'/../../app/classes/core/engine_class_inc.php');
checkResume(strpos($engine,'->resumeRememberedLogin()')<strpos($engine,"\$operations->shouldBlock"),'request resumption precedes maintenance and module dispatch');

$evicted=$newTokens->issueForSession('editing');
for($i=0;$i<13;$i++)$newTokens->issueForSession('editing');
checkResume(!$newTokens->consume('editing',$evicted)&&$session->isAuthenticated(),'token eviction is not loss of login');
checkResume($newTokens->consume('editing',$newTokens->issueForSession('editing')),'renewal recovers an evicted form');
