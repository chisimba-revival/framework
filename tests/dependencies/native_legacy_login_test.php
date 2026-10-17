<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 public $services=[];
 public function getObject($name,$module){return $this->services[$name];}
}
require __DIR__.'/../../app/core_modules/login/classes/showlogin_class_inc.php';
require __DIR__.'/../../app/core_modules/userregistration/classes/loginbox_class_inc.php';
require __DIR__.'/../../app/core_modules/skin/classes/skinjavascript_class_inc.php';
$native=new class {public $calls=[];public function renderLoginBox($module){$this->calls[]=$module;return '<form data-native-login></form>';}};
$user=new class {public $logged=false;function isLoggedIn(){return $this->logged;}function fullName(){return '<script>test</script>';}};
$view=new showlogin();$view->services=['logininterface'=>$native,'user'=>$user,'language'=>new class{function languageText($key,$module=null){return 'Sign <in>';}}];
foreach([false,true] as $ajax){if($view->renderLoginBox('calendar',$ajax)!=='<form data-native-login></form>')throw new RuntimeException('Did not delegate');}
$html=$view->show('calendar',true,'renderLoginAsDropdown');
if(!str_contains($html,'<details')||!str_contains($html,'Sign &lt;in&gt;')||!str_contains($html,'data-native-login')||str_contains($html,'<script'))throw new RuntimeException('Unsafe native disclosure');
$user->logged=true;$html=$view->renderLoginAsDropdown();
if(str_contains($html,'<script>')||!str_contains($html,'&lt;script&gt;'))throw new RuntimeException('Unescaped identity');
if($view->show(null,false,'unexpected_method')!=='<form data-native-login></form>')throw new RuntimeException('Untrusted dispatch');
if($view->renderProveHuman()!=='<form data-native-login></form>')throw new RuntimeException('Legacy nonce gate returned');
$view->loadAllScripts();
$panel=new loginbox();$panel->setInits();if($panel->show()!==''||$panel->isEnable())throw new RuntimeException('Disabled panel revived');
$skin=new skinjavascript();if($skin->getChromeFrame()!=='')throw new RuntimeException('Obsolete plugin loaded');
echo "PASS native login delegation, disclosure escaping, safe dispatch and retired panel/plugin\n";
