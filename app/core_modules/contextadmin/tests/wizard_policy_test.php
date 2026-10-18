<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {function getObject($name,$module=''){return $GLOBALS['objects'][$name];}}
require dirname(__DIR__).'/classes/coursewizardservice_class_inc.php';
function check($ok,$why){if(!$ok)throw new RuntimeException($why);}
$objects['contextsettingsaccess']=new class {public $allowed=true;function canEdit($code){return $this->allowed&&$code==='course';}};
$csrf=new class {public $purpose;function consume($purpose,$token){$this->purpose=$purpose;return $token==='valid';}};
$objects['nativeauthwebcomposition']=new class($csrf){function __construct(private $csrf){}function build(){return ['csrf'=>$this->csrf];}};
$objects['modules']=new class {function getListContextPlugins(){return [['module_id'=>'offered']];}};
$objects['coursewizardstore']=new class {public $writes=0,$error='';function save($code,$step,$revision,$changes,$allowed){if($this->error!=='')throw new RuntimeException($this->error);++$this->writes;}};
$s=new coursewizardservice();$request=['contextCode'=>'course','csrf_token'=>'valid','revision'=>str_repeat('a',64),'changes'=>[]];
foreach(['outcomes','plugins'] as $step){
 check($s->submit('course',$step,$request,'GET')==='method','GET denial');
 $bad=$request;$bad['contextCode']='foreign';check($s->submit('course',$step,$bad,'POST')==='denied','scope denial');
 foreach([[],null,'expired'] as $token){$bad=$request;$bad['csrf_token']=$token;check($s->submit('course',$step,$bad,'POST')==='expired','token denial');}
 $objects['contextsettingsaccess']->allowed=false;check($s->submit('course',$step,$request,'POST')==='denied','revoked authority');$objects['contextsettingsaccess']->allowed=true;
}
check($objects['coursewizardstore']->writes===0,'denials do not write');
check($s->purpose('course','outcomes')!==$s->purpose('course','plugins'),'step-separated tokens');
check($s->purpose('course','outcomes')!==$s->purpose('foreign','outcomes'),'course-separated tokens');
$add=$request;$add['operation']='add';check($s->submit('course','outcomes',$add,'POST')==='add'&&$objects['coursewizardstore']->writes===0,'add field does not save');
foreach(['stale','linked','invalid','save'] as $error){$objects['coursewizardstore']->error=$error;check($s->submit('course','outcomes',$request,'POST')===$error,'recoverable failure');}
$objects['coursewizardstore']->error='';check($s->submit('course','plugins',$request,'POST')===''&&$objects['coursewizardstore']->writes===1,'authorised save');
echo "PASS wizard current authority, POST, scope, step-specific CSRF, no-write add and error recovery\n";
