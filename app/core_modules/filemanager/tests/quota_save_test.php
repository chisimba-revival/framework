<?php
/** Exercise real request handling with failure-injectable shared boundaries. */
$GLOBALS['kewl_entry_point_run']=true;
class controller {
    public function getParam($key,$default='') {return $default;}
    public $vars=[]; public $csrf;
    public function getObject($name,$module) { return new class($this->csrf) { public function __construct(private $csrf) {} public function build() {return ['csrf'=>$this->csrf];} }; }
    public function setVar($key,$value) {$this->vars[$key]=$value;}
    public function nextAction($action,$params=[]) {return $action;}
}
require __DIR__.'/../controller.php';
$c = new filemanager();
$c->csrf = new class { public function consume($context,$token){return $token==='valid';} public function issue($context){if(strlen($context)>80)throw new RuntimeException('Context too long');return 'renewed';} };
$c->objQuotas = new class {
    public $row=['id'=>'fixture','usedefault'=>'N','quota'=>50,'datemodified'=>'2026-10-17 00:00:00'];
    public $writes=0; public $fail=false; public $conflict=false;
    public function getQuotaFromId($id){return $id==='fixture'?$this->row:false;}
    public function saveQuotaChecked($old,$type,$size){if($this->fail)throw new RuntimeException('injected');if($this->conflict)return false;$this->writes++;return true;}
};
$method=new ReflectionMethod(filemanager::class,'__updatequota');
function check($ok){if(!$ok)throw new RuntimeException('Quota save regression');}
$_SERVER['REQUEST_METHOD']='GET'; check($method->invoke($c)==='access_denied_tpl.php');check(http_response_code()===405);
$_SERVER['REQUEST_METHOD']='POST';$_POST=['id'=>'fixture','quotatype'=>'N','customquota'=>'73','csrf_token'=>'expired'];
check($method->invoke($c)==='editquota_tpl.php');check($c->vars['quotaDraft']['quota']==='73' && $c->vars['quotaError']==='expired' && $c->objQuotas->writes===0);
$_POST['csrf_token']='valid';$_POST['quota_revision']=$c->vars['quotaRevision'];
$_POST['customquota']='-5';$method->invoke($c);check($c->vars['quotaError']==='invalid' && $c->objQuotas->writes===0);
$_POST['customquota']='73';$c->objQuotas->fail=true;$method->invoke($c);check($c->vars['quotaError']==='failed' && $c->vars['quotaDraft']['quota']==='73');
$c->objQuotas->fail=false;$c->objQuotas->conflict=true;$method->invoke($c);check($c->vars['quotaError']==='conflict' && $c->objQuotas->writes===0);
$c->objQuotas->conflict=false;check($method->invoke($c)==='quotamanager' && $c->objQuotas->writes===1);
$_POST['quota_revision']='stale';$method->invoke($c);check($c->vars['quotaError']==='conflict' && $c->objQuotas->writes===1);
echo "PASS method, CSRF renewal, draft retention, value validation, write failure and conflict handling\n";
