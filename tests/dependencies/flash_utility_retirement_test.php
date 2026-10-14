<?php
/** Retired endpoints, preserved block access, and dynamic plugin loader regression. */
$GLOBALS['kewl_entry_point_run']=true;
set_error_handler(function($n,$s){throw new RuntimeException($s);});
class ChisimbaObject {
    public array $services=[];
    function getObject($name,$module=null){return $this->services[$name];}
    function uri($params,$module=null){return '/index.php?'.http_build_query(['module'=>$module]+$params);}
    function getResourcePath($file,$module){return dirname(__DIR__,2).'/app/core_modules/'.$module.'/resources/'.$file;}
}
class controller extends ChisimbaObject {
    public string $action='';
    function getParam($name,$default=null){return $name==='action'?$this->action:$default;}
}
function check($value,$label){if(!$value)throw new RuntimeException($label);}
$root=dirname(__DIR__,2);
require $root.'/app/core_modules/context/classes/block_contextstats_class_inc.php';
require $root.'/app/core_modules/logger/controller.php';
require $root.'/app/core_modules/jquerycore/classes/coreloader_class_inc.php';
$context=new class {public string $code='course-a';function getContextCode(){return $this->code;}};
$user=new class {public bool $logged=false,$admin=false;function isLoggedIn(){return $this->logged;}function isAdmin(){return $this->admin;}};
$groups=new class {public bool $teacher=false;function isContextLecturer(){return $this->teacher;}};
$modules=new class {public bool $registered=true;function checkIfRegistered($name){return $this->registered;}};
$language=new class {function languageText(...$args){return 'View <activity>'; }function code2Txt(...$args){return 'Course statistics';}};
$block=new block_contextstats();$block->services=['dbcontext'=>$context,'user'=>$user,'managegroups'=>$groups,'modules'=>$modules,'language'=>$language];$block->init();
check($block->show()==='','Anonymous block hidden');
$user->logged=true;check($block->show()==='','Student/outsider block hidden');
$groups->teacher=true;$html=$block->show();check(str_contains($html,'action=viewlogdetails')&&str_contains($html,'View &lt;activity&gt;'),'Lecturer gets escaped report link');
$groups->teacher=false;check($block->show()==='','Revoked instructor hidden');
$user->admin=true;check($block->show()!=='','Administrator allowed');
$context->code='root';check($block->show()==='','Root has no course report');
$context->code='course-a';$modules->registered=false;check($block->show()==='','Missing optional report module hidden');
$logger=new logger();$logger->action='modulestats';$logger->init();ob_start();$result=$logger->dispatch();$out=ob_get_clean();check($result===null&&$out===''&&http_response_code()===410,'Legacy statistics route retired without data access');
$loader=new coreloader();(new ReflectionMethod(coreloader::class,'getPluginDeps'))->invoke($loader);
$loader->setPlugins(['flash','tooltip']);$loader->setPlugin('flash');
check((new ReflectionProperty(coreloader::class,'plugins'))->getValue($loader)===['tooltip'],'Stale Flash session request ignored; supported plugin retained');
foreach(json_decode(file_get_contents(__DIR__.'/retired-flash-utility-paths.json'),true) as $path)check(!file_exists($root.'/'.$path),'Retired path returned: '.$path);
$moduleRoot=dirname($root).'/modules';
if(is_file($moduleRoot.'/formbuilder/controller.php')){
    require $moduleRoot.'/formbuilder/controller.php';$form=new formbuilder();$form->action='newtest';$form->init();
    ob_start();$result=$form->dispatch();$out=ob_get_clean();check($result===null&&$out===''&&http_response_code()===410,'Legacy form demo retired');
}
echo "PASS block permissions, escaped report link, retired endpoints, stale plugin sessions and removed assets\n";
