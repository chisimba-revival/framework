<?php
$GLOBALS['kewl_entry_point_run']=true;
set_error_handler(static function($n,$s){throw new ErrorException($s,0,$n);});
class controller {
    public array $params=[],$session=[];
    public $actions;
    public function getParam($key,$default=null){return $this->params[$key]??$default;}
    public function setSession($key,$value){$this->session[$key]=$value;}
    public function getObject($name,$module=''){return $this->actions;}
    public function nextAction($action){return $action;}
}
require dirname(__DIR__).'/controller.php';
$c=new userdetails();
$c->objModules=new class{function checkIfRegistered($name){return false;}};
$c->objLanguage=new class{function languageText($key,$module){return $key;}};
$c->objOps=new class{public int $saves=0;public bool $fail=false;function validate($data){return false;}function save($data){++$this->saves;if($this->fail)throw new RuntimeException('write interrupted');}};
$c->actions=new class{function csrf(){return $this;}function consume($purpose,$token){return $token==='valid';}};
$method=new ReflectionMethod(userdetails::class,'__validate');
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
$c->session['errors']=['draft'=>'keep'];$_SERVER['REQUEST_METHOD']='GET';$method->invoke($c);
check(http_response_code()===405&&$c->session['errors']===['draft'=>'keep']&&$c->objOps->saves===0,'GET preserves draft and prevents writes');
$_SERVER['REQUEST_METHOD']='POST';$c->params=['first_name'=>'Typed name','password'=>'test-only','confirm_password'=>'test-only','csrf_token'=>'expired','last_name'=>['bad shape']];$method->invoke($c);
check(http_response_code()===403&&$c->objOps->saves===0,'invalid CSRF prevents writes');
check($c->session['errors']['data']['first_name']==='Typed name'&&$c->session['errors']['data']['password']===''&&$c->session['errors']['data']['confirm_password']===''&&$c->session['errors']['data']['last_name']==='','non-secret draft retained and input shape normalised');
$c->params['csrf_token']='valid';check($method->invoke($c)==='main'&&$c->objOps->saves===1,'valid request delegates once');
$c->objOps->fail=true;check($method->invoke($c)==='main_tpl.php'&&http_response_code()===422,'write failure handled');
check($c->session['errors']['data']['first_name']==='Typed name'&&$c->session['errors']['data']['password']===''&&$c->session['success']===[],'write failure retains draft without secrets or false success');
echo "PASS profile details method guard, CSRF, input shape, draft retention and interrupted save\n";
