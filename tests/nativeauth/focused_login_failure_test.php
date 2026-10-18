<?php
$GLOBALS['kewl_entry_point_run']=true;$_SERVER['SCRIPT_NAME']='/index.php';
class controller {
 public $session=[],$vars=[];
 function getSession($k,$d=null){return $this->session[$k]??$d;}function unsetSession($k){unset($this->session[$k]);}function setVar($k,$v){$this->vars[$k]=$v;}function getParam($k,$d=null){return $d;}
 function getObject($n,$m=null){return new class {function getValue($k,$m){return '';}function build(){return ['sessions'=>new class{function isAuthenticated(){return false;}},'csrf'=>new class{function issue($k){return 'fresh';}},'abuse'=>new class{function issueFormEvidence($k){return [];}}];}};}
}
require __DIR__.'/../../app/core_modules/security/controller.php';
$c=new security();$c->objLanguage=new class{function languageText($k,$m){return $k;}};
$c->session['native_auth_login_failure']=['message_key'=>'mod_security_authenticationfailed','username'=>'Example','return_to'=>'/index.php?module=webinar'];
$method=new ReflectionMethod($c,'nativeLoginPage');$method->invoke($c);
if($c->vars['nativeLoginLabels']['failure']!=='mod_security_authenticationfailed'||$c->vars['nativeLoginUsername']!=='Example'||$c->vars['nativeReturnTo']!=='/index.php?module=webinar'||$c->session)throw new RuntimeException('Failure state lost');
$method->invoke($c);if($c->vars['nativeLoginLabels']['failure']!=='')throw new RuntimeException('Failure not one-use');
$p=new ReflectionMethod($c,'failedLoginPath');if($p->invoke($c)!=='/index.php?module=security&action=showlogin')throw new RuntimeException('Wrong failure destination');
echo "PASS focused login failure, retained username/return route and one-use message.\n";
