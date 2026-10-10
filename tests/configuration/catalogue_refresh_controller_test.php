<?php
/** Exercise the actual refresh action without touching installed files or records. */
if(PHP_SAPI!=='cli')exit(2);
$app=realpath($argv[1]??dirname(__DIR__,2).'/app');
class ChisimbaObject {}
class controller {
 public $params=array(),$objects=array(),$vars=array();
 public function getParam($name,$default=null){return $this->params[$name]??$default;}
 public function getParm($name,$default=null){return $this->getParam($name,$default);}
 public function getObject($name,$module=null){return $this->objects[$name];}
 public function setVar($name,$value){$this->vars[$name]=$value;}
 public function getSession($name,$default=null){return $default;}
 public function setSession($name,$value){}
 public function setLayoutTemplate($name){}
 public function nextAction($action,$params){return $params;}
}
class customException extends Exception{}
class Identity {public $admin=true;public function isAdmin(){return $this->admin;}}
class Tokens {public function issue($context){return 'fresh';}public function consume($context,$token){return $context==='modulecatalogue_refresh'&&$token==='valid';}}
class Composition {public function build(){return array('csrf'=>new Tokens());}}
class Language {public function languageText($key,$module,$fallback=null){return $fallback??$key;}}
class Writer {public $calls=0,$fail=false;public function writeCatalogue(){$this->calls++;if($this->fail)throw new RuntimeException('fixture');return array('discovered'=>2,'removed'=>array(),'reconciled'=>true);}}
require $app.'/core_modules/modulecatalogue/controller.php';
require $app.'/core_modules/modulecatalogue/classes/catalogueviewfilter_class_inc.php';
$ref=new ReflectionClass('modulecatalogue');$c=$ref->newInstanceWithoutConstructor();$identity=new Identity();$writer=new Writer();
foreach(array('objUser'=>$identity,'objLanguage'=>new Language(),'objCatalogueConfig'=>$writer)as $name=>$value){$ref->getProperty($name)->setValue($c,$value);}
$c->objects=array('nativeauthwebcomposition'=>new Composition(),'catalogueviewfilter'=>new catalogueviewfilter());
$count=0;function check($ok,$label){global $count;if(!$ok)throw new RuntimeException($label);$count++;}
set_error_handler(static function($severity,$message){throw new ErrorException($message,0,$severity);});
foreach(array('anonymous','get','csrf')as $case){$identity->admin=$case!=='anonymous';$_SERVER['REQUEST_METHOD']=$case==='get'?'GET':'POST';$c->params=array('action'=>'updatexml','csrf_token'=>$case==='csrf'?'bad':'valid');$result=$c->dispatch();check($writer->calls===0,$case.' cannot refresh');if($case!=='anonymous')check(str_contains($result['message'],'did not complete'),$case.' visible failure');}
$identity->admin=true;$_SERVER['REQUEST_METHOD']='POST';$c->params['csrf_token']='valid';$result=$c->dispatch();check($writer->calls===1&&str_contains($result['message'],'xmlupdated'),'valid refresh');
$writer->fail=true;$result=$c->dispatch();check(str_contains($result['message'],'did not complete')&&!str_contains($result['message'],'xmlupdated'),'write failure never reports success');
restore_error_handler();echo "PASS: $count actual catalogue refresh action checks\n";
