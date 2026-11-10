<?php
/** Defaults are server-owned; operator forms require no implementation identifiers. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject { public static $objects=[];public function getObject($name,$module){return self::$objects[$name];} }
require dirname(__DIR__).'/classes/navigationservice_class_inc.php';
require dirname(__DIR__).'/classes/modulelinkdefaults_class_inc.php';
function verify($ok,$message){if(!$ok)throw new RuntimeException($message);}
ChisimbaObject::$objects['register']=new class {
 public function getModuleData($module){return match($module){
 'contactform'=>['MODULE_NAME'=>'Contact form','MODULE_ICON'=>'mail','TEXT'=>['mod_contactform_title|Title|Contact','mod_contactform_name|Name|Contact form']],
 'photogallery'=>['MODULE_NAME'=>'Photos','MODULE_ICON'=>'image','SITE_NAV'=>['032||view|camera|mod_photogallery_flickr||provider=flickr']],
 'removed'=>false,
 default=>['MODULE_NAME'=>'Example module','MODULE_ICON'=>'unsafe?icon']};}
};
ChisimbaObject::$objects['language']=new class {public function languageText($code,$module,$fallback){return ['mod_contactform_title'=>'Contact','mod_contactform_name'=>'Contact form','mod_photogallery_flickr'=>'Flickr photos'][$code]??$fallback;}};
$d=new modulelinkdefaults();$options=$d->destinations('contactform');
verify($options['default']===['action'=>'','icon'=>'mail','code'=>'mod_contactform_title','arguments'=>'','label'=>'Contact'],'Contact defaults without operator input');
verify($d->moduleName('contactform')==='Contact form','Human readable module name');
$photo=$d->destinations('photogallery');$route=array_values($photo)[1];
verify($route['action']==='view'&&$route['arguments']==='provider=flickr'&&$route['label']==='Flickr photos','Declared route safely preserves arguments');
$existing=['module'=>'photogallery','category'=>'site_032||view|camera|mod_photogallery_flickr||provider=flickr'];
verify($d->destinations('photogallery',$existing)['current']===$route,'Existing custom route retained');
$broken=['module'=>'contactform','category'=>'site_010||||en|'];
verify($d->destinations('contactform',$broken)['current']['code']==='mod_contactform_title','Legacy locale-as-code repaired on edit');
verify($d->destinations('example')['default']['icon']==='link'&&$d->label('missing','example')==='Example module','Safe icon and readable fallback');
verify($d->label('mod_removed_name','removed')==='Removed','Missing module remains editable without a rendering exception');
$form=file_get_contents(dirname(__DIR__).'/templates/content/module_link_tpl.php');
foreach(['name="icon"','name="code"','name="actionName"','name="destinationArguments"'] as $field)verify(!str_contains($form,$field),'Technical field removed: '.$field);
echo "PASS: registered defaults, readable module names/fallback, declared and existing destinations, locale-code recovery, technical fields absent.\n";
class controller extends ChisimbaObject {
 public $params=[];
 public function getParam($key,$default=''){return $this->params[$key]??$default;}
 public function nextAction($action,$params=[]){return $action;}
}
require dirname(__DIR__).'/controller.php';
$c=new toolbar();$c->objLanguage=ChisimbaObject::$objects['language'];
$c->objPermissionService=new class{public function areaIdForName(...$args){return null;}};
$c->objDbMenu=new class{public $saved;public function saveLinks($fields,$id){$this->saved=$fields;return true;}};
ChisimbaObject::$objects['modulelinkdefaults']=$d;
ChisimbaObject::$objects['navigationcategories']=new class{public function mutate($operation){return $operation();}};
ChisimbaObject::$objects['navigationservice']=new class{public function linkPosition(...$args){return 10;}public function destinationArguments($value){return navigationservice::destinationArguments($value);}};
$c->params=['moduleName'=>'contactform','destination'=>'default','position'=>'','groupCode'=>'','icon'=>'bad','code'=>'en','actionName'=>'manage','destinationArguments'=>'action=delete'];
$save=new ReflectionMethod(toolbar::class,'saveSiteLink');
verify($save->invoke($c)==='editlinks','Valid default saves');
verify($c->objDbMenu->saved['category']==='site_010|||mail|mod_contactform_title|','Posted technical fields cannot override registered defaults');
$c->params['destination']='forged';
try{$save->invoke($c);throw new RuntimeException('Forged destination accepted');}catch(InvalidArgumentException $expected){}
echo "PASS: controller ignores posted technical fields and rejects unregistered destination choices.\n";

ChisimbaObject::$objects['modules']=new class {public $installed=true;public function checkIfRegistered($module){return $this->installed;}};
ChisimbaObject::$objects['dbsitepages']=new class {
 public $rows=[['id'=>'abc123','title'=>'About us','slug'=>'about-us','status'=>'published'],['id'=>'draft123','title'=>'Private draft','slug'=>'draft','status'=>'draft']];
 public function activeRows(){return $this->rows;}
 public function find($id){foreach($this->rows as $row)if($row['id']===$id)return $row;return false;}
};
$pages=$d->destinations('sitepages');
verify(array_keys($pages)===['page_abc123'],'Only published pages offered, no ambiguous module default');
verify($pages['page_abc123']['label']==='About us','Page titles supplied without language codes');
ChisimbaObject::$objects['dbsitepages']->rows[0]['title']='Our story';
ChisimbaObject::$objects['dbsitepages']->rows[0]['slug']='our-story';
verify($d->label('page_abc123','sitepages')==='Our story'&&$d->page('page_abc123')['slug']==='our-story','Identity follows title and slug changes');
ChisimbaObject::$objects['modules']->installed=false;
verify($d->page('page_abc123')===null,'Uninstalled page module fails closed');
echo "PASS: published page choices, title and address changes, unavailable module.\n";

$c->params=['moduleName'=>'contactform','destination'=>'default','position'=>'','groupCode'=>'','menuLabel'=>'News & stories'];
$save->invoke($c);verify($c->objDbMenu->saved['menu_label']==='News & stories','Custom menu label saved');
$c->params['menuLabel']='  ';$save->invoke($c);verify($c->objDbMenu->saved['menu_label']===null,'Blank restores default');
foreach([['bad'],str_repeat('a',151),"bad\nlabel"] as $label){$c->params['menuLabel']=$label;try{$save->invoke($c);throw new RuntimeException('Invalid label accepted');}catch(InvalidArgumentException $expected){}}
echo "PASS: custom label persistence, default restoration and invalid input rejection.\n";
