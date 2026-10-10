<?php
/** Actual preference controller and document service; disposable files only. */
if (PHP_SAPI!=='cli') exit(2);
$app=realpath($argv[1] ?? dirname(__DIR__,2).'/app');
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 public $objects=array(),$params=array(),$vars=array();
 public function getObject($name,$module=null) { return $this->objects[$name]; }
 public function getParam($name,$default=null) { return $this->params[$name]??$default; }
 public function setVar($name,$value) { $this->vars[$name]=$value; }
 public function nextAction($action,$params,$module=null) { return array($action,$module); }
 public function setLayoutTemplate($name) {}
 public function uri($params) { return '/fixture?'.http_build_query($params); }
 public function render($file) { extract($this->vars); ob_start(); include $file; return ob_get_clean(); }
}
class controller extends ChisimbaObject {}
class FixtureUser { public $logged=true; public function isLoggedIn(){return $this->logged;} public function isAdmin(){return false;} public function userId(){return 'fixture';} }
class FixturePaths { public $base; public function getcontentBasePath(){return $this->base;} }
class FixtureLanguage { public function languageText($key,$module=null,$fallback=null){return $fallback??$key;} }
class FixtureTokens { public function issue($context){return 'fresh';} public function consume($context,$token){return $context==='userparams_save'&&$token==='valid';} }
class FixtureComposition { public function build(){return array('csrf'=>new FixtureTokens());} }
class FixtureModules { public function checkIfRegistered($name){return false;} }
require $app.'/core_modules/userparamsadmin/classes/dbuserparamsadmin_class_inc.php';
require $app.'/core_modules/userparamsadmin/controller.php';
set_error_handler(static function($severity,$message){throw new ErrorException($message,0,$severity);});
$base=sys_get_temp_dir().'/chisimba-preferences-http-'.bin2hex(random_bytes(8));mkdir($base,0700);$count=0;
function check($ok,$label){global $count;if(!$ok)throw new RuntimeException($label);$count++;}
try {
 $user=new FixtureUser();$paths=new FixturePaths();$paths->base=$base;
 $objects=array('user'=>$user,'altconfig'=>$paths,'language'=>new FixtureLanguage(),'nativeauthwebcomposition'=>new FixtureComposition(),'modules'=>new FixtureModules());
 $prefs=new dbuserparamsadmin();$prefs->objects=$objects;$prefs->init();$objects['dbuserparamsadmin']=$prefs;
 $c=new userparamsadmin();$c->objects=$objects;$c->init();
 $params=array('action'=>'save','mode'=>'add','pname'=>'canvas','ptag'=>'Draft " & 日本語','preferences_csrf'=>'valid','preferences_revision'=>$prefs->getRevision());
 foreach(array('get','csrf','revision','stale','array') as $case){
  $_SERVER['REQUEST_METHOD']='POST';$c->params=$params;
  if($case==='get')$_SERVER['REQUEST_METHOD']='GET';
  if($case==='csrf')$c->params['preferences_csrf']='invalid';
  if($case==='revision')unset($c->params['preferences_revision']);
  if($case==='stale')$c->params['preferences_revision']=str_repeat('0',64);
  if($case==='array')$c->params['pname']=array('bad');
  check($c->dispatch()==='edit_tpl.php',$case.' rejected');
  check(!is_dir($base.'/users'),$case.' no write side effect');
  $html=$c->render($app.'/core_modules/userparamsadmin/templates/content/edit_tpl.php');
  check(str_contains($html,'role="alert"')&&str_contains($html,'Draft &quot; &amp; 日本語'),$case.' escaped draft retained');
 }
 $_SERVER['REQUEST_METHOD']='POST';$c->params=$params;
 check(is_array($c->dispatch())&&$prefs->getValue('canvas')===$params['ptag'],'valid save persists');
 check($c->dispatch()==='edit_tpl.php','repeated stale form rejected');
 $c->params=array('action'=>'edit','key'=>'canvas');check($c->dispatch()==='edit_tpl.php'&&$c->vars['valueEdit']===$params['ptag'],'edit reads persisted value');
 $revision=$prefs->getRevision();$c->params=array('action'=>'delete','key'=>'canvas','preferences_csrf'=>'valid','preferences_revision'=>$revision);
 $_SERVER['REQUEST_METHOD']='GET';check($c->dispatch()==='main_tpl.php'&&$prefs->getValue('canvas')!==null,'GET deletion refused');
 $_SERVER['REQUEST_METHOD']='POST';check(is_array($c->dispatch())&&$prefs->getValue('canvas')===null,'POST deletion succeeds');
 $user->logged=false;$c->params=$params;check($c->dispatch()===array(null,'security'),'anonymous redirected without save');
 check(!class_exists('Config',false),'controller no PEAR Config');
 echo "PASS: $count preference controller/save/delete/draft checks\n";
}finally{restore_error_handler();$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($it as $e){if($e->isDir())rmdir($e->getPathname());else unlink($e->getPathname());}rmdir($base);}
