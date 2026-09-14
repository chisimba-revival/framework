<?php
error_reporting(E_ALL);
set_error_handler(static function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
$GLOBALS['kewl_entry_point_run']=true;
$app=$argv[1]??dirname(__DIR__,2).'/app';
set_include_path($app.'/core_modules/htmlelements/classes'.PATH_SEPARATOR.get_include_path());
require $app.'/classes/core/object_class_inc.php';require $app.'/classes/core/access_class_inc.php';require $app.'/classes/core/controller_class_inc.php';
require $app.'/core_modules/skin/classes/skin_class_inc.php';require $app.'/core_modules/htmlelements/classes/geticon_class_inc.php';
class PresentationProbeEngine {
 public $lu=null,$luAdmin=null,$eventDispatcher=null,$appid='test',$version='26';
 public $_templateVars=[],$_templateRefs=[],$services=[],$path;
 public function getObject($name,$module){return $this->services[$name];}
 public function getParam($name,$default=null){return $default;}
 public function setVar($name,$value){$this->_templateVars[$name]=$value;}
 public function setVarByRef($name,&$value){$this->_templateRefs[$name]=&$value;}
 public function _findTemplate(...$args){return $this->path;}
}
$e=new PresentationProbeEngine();
$e->services=['altconfig'=>new class {
 public function getNoXML(){return false;}
 public function getskinRoot(){return '/skins/';}
},'language'=>(object)['label'=>'language'],'user'=>(object)['label'=>'user'],'browser'=>(object)['label'=>'browser']];
$skin=new skin($e,'skin');$e->services['skin']=$skin;
if($skin->objLanguage!==$e->services['language'] || $skin->browserInfo!==$e->services['browser'] || $skin->skinRoot!=='/skins/')throw new RuntimeException('Skin services changed');
$icon=new getIcon($e,'htmlelements');
if($icon->_objLanguage!==$e->services['language'] || $icon->_objSkin!==$skin)throw new RuntimeException('Icon services changed');
$e->path=tempnam(sys_get_temp_dir(),'chisimba-template-');
file_put_contents($e->path,'<?php echo $objLanguage->label,"/",$objUser->label,"/",$objSkin->skinRoot; $objUser->label="changed";');
try {
 $controller=(new ReflectionClass('controller'))->newInstanceWithoutConstructor();$controller->objEngine=$e;$controller->moduleName='test';
 if($controller->callTemplate('probe','content')!=='language/user//skins/')throw new RuntimeException('Template service binding changed');
 if($e->services['user']->label!=='changed')throw new RuntimeException('Template lost original object identity');
 foreach(['objConfig'=>'altconfig','objSkin'=>'skin','objUser'=>'user','objLanguage'=>'language'] as $name=>$service){
  if($e->_templateRefs[$name]!==$e->services[$service])throw new RuntimeException('Service references alias each other');
 }
 ob_start();$return=$controller->callTemplate('probe','content',false);$output=ob_get_clean();
 if($return!==null || $output!=='language/changed//skins/')throw new RuntimeException('Unbuffered rendering changed');
}finally{unlink($e->path);}
echo "PASS: strict skin/icon initialisation and distinct template object identity, buffered and unbuffered rendering\n";
