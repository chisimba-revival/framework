<?php
/** Learning placement stays separate from site links and module permissions. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    public static $objects=[];
    public function getObject($name,$module=''){return self::$objects[$name];}
    public function uri($params,$module){return '/index.php?'.http_build_query(['module'=>$module]+($params??[]));}
}
class dbTable extends ChisimbaObject {}
class link {
    public $link='';
    public function __construct(private $url){}
    public function show(){return '<a href="'.htmlspecialchars($this->url,ENT_QUOTES).'">'.$this->link.'</a>';}
}
require dirname(__DIR__).'/classes/externalnavigation_class_inc.php';
require dirname(__DIR__).'/classes/menu_class_inc.php';
require dirname(__DIR__).'/classes/cssmenu_class_inc.php';
function verify($condition,$message){if(!$condition)throw new RuntimeException($message);}
$store=new class extends externalnavigation {
 public function links(){return [
 ['placement'=>'site','audience'=>'everyone','url'=>'https://site.example','label'=>'Site only'],
 ['placement'=>'learning','audience'=>'members','url'=>'https://learn.example/?a=1&b=2','label'=>'Resources <script>'],
 ['placement'=>'learning','audience'=>'everyone','url'=>'https://bad.example','label'=>'Wrong audience'],
 ['placement'=>'learning','audience'=>'members','url'=>'javascript:alert(1)','label'=>'Unsafe']
 ];}
};
ChisimbaObject::$objects['externalnavigation']=$store;
$security=new class {
 public $auth=true;
 public function isAuthenticated(){return $this->auth;}
 public function isSiteAdministrator(){return false;}
 public function isLecturer(){return false;}
 public function hasStudentLearning(){return false;}
};
$lang=new class {
 public function languageText($code,$module,$fallback=''){return $fallback?:$code;}
 public function code2Txt($code,$module,$arg=null,$fallback=''){return $fallback?:$code;}
 public function abstractText($v){return $v;}
};
$m=new menu();$m->securityContext=$security;$m->objLanguage=$lang;$m->context=false;
$m->objModule=new class {public function checkIfRegistered($module){return false;}};
$m->dbmenu=new class {public function getModules(...$args){return [];}};
$css=new cssmenu();$css->iconService=new class {public function render(...$args){return '<svg></svg>';}};
$css->moduleIconResolver=new class {public function render(...$args){return '<svg></svg>';}};
$m->cssMenu=$css;
$items=$m->menuBar();
verify(count($items['Learning'])===2,'Built-in browse link followed by authored link');
verify($items['Learning'][0]['module']==='context','Built-in route retained');
$html=$m->buildMenu($items);
verify(str_contains($html,'https://learn.example/?a=1&amp;b=2'),'External URL retained and escaped');
verify(str_contains($html,'Resources &lt;script&gt;')&&!str_contains($html,'Resources <script>'),'Authored text escaped');
verify(!str_contains($html,'site.example')&&!str_contains($html,'bad.example')&&!str_contains($html,'javascript:'),'Invalid and other-placement links excluded');
$security->auth=false;verify(count($m->menuBar()['Learning'])===1,'Anonymous never receives authored Learning links');
verify(!isset($items['Teaching'])&&!isset($items['Administration']),'No teaching/admin permissions granted');
echo "PASS: Learning placement, authentication, built-in routes, permission boundaries and escaped external rendering.\n";
