<?php
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 public array $vars=[]; public array $calls=[]; public array $plugins=[];
 public function getVar($k,$d=null){return $this->vars[$k]??$d;}
 public function getSession($k,$d=null){return $this->plugins;}
 public function getObject($name,$module){
  $this->calls[]=$module;
  return new class {
   public function getValue($k,$m){return 'fixture';}
   public function setCoreVersion($v){} public function setTheme($v){}
   public function setPlugins($v){} public function load(){return 'core';}
   public function show(){return 'wrapper';}
  };
 }
}
require getenv('CHISIMBA_SKIN_LOADER_TEST_FILE') ?: dirname(__DIR__,2).'/app/core_modules/skin/classes/skinjavascript_class_inc.php';
foreach([false,true] as $core){
 foreach([null,true,false] as $suppressed){
  $o=new skinjavascript();$o->plugins=['stale-session-plugin'];
  $o->objModules=new class($core){public function __construct(private bool $core){}public function checkIfRegistered($id){return $this->core;}};
  if($suppressed!==null)$o->vars['SUPPRESS_JQUERY']=$suppressed;
  $result=$o->getJQuery();
  if($suppressed===false){if($result!==($core?'core':'wrapper'))throw new Exception('Opt-in failed');}
  elseif($result!==null || $o->calls)throw new Exception('Suppressed/default page accessed loader services');
 }
}
echo "PASS default-off, explicit suppression, explicit opt-in, both loader branches and stale session plugins\n";
