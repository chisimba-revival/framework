<?php
/** Catalogue updates preserve site choices; explicit defaults remain deliberate. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {}
require dirname(__DIR__).'/classes/register_class_inc.php';
function check($value,$message){if(!$value)throw new RuntimeException($message);}
class NavigationStore {
    public array $rows=[];
    public function linksForModule($module){return array_values(array_filter($this->rows,fn($r)=>$r['module']===$module));}
    public function deleteLinksForModule($module,$preserve=false){$this->rows=array_values(array_filter($this->rows,fn($r)=>$r['module']!==$module||($preserve&&str_starts_with($r['category'],'site_'))));return true;}
    public function saveLinks($row){$this->rows[]=$row;return true;}
}
class NavigationRegistrar extends register {
    public array $data=[];
    public function canonicalRightForRegistration($data,$module,$scope='default'){return '';}
    public function getModuleData($module){return $this->data;}
}
$store=new NavigationStore();$registrar=new NavigationRegistrar();$registrar->objDbMenu=$store;
$custom=['id'=>'custom-contact','category'=>'site_31||view|mail|mod_contactform_title|','module'=>'contactform','adminonly'=>0,'permissions'=>null,'dependscontext'=>0,'contextplugin'=>0];
$other=['id'=>'other','category'=>'site_020||view|home|home','module'=>'sitepages'];
$store->rows=[$custom,$other,['module'=>'contactform','category'=>'page_admin_site|obsolete|mail|old']];
$data=['MODULE_ID'=>'contactform','PAGE'=>['admin_site|manage|inbox|mod_contactform_manage|site'],'SITE_NAV'=>['010||view|mail|default']];
$registrar->data=$data;
$registrar->replaceData($data);$registrar->replaceData($data);
check(in_array($custom,$store->rows,true),'Custom link, ID, permissions and scope survive repeat updates');
check(in_array($other,$store->rows,true),'Other module untouched');
check(count($store->rows)===3,'No duplicate site or page entries after repeat update');
check(!array_filter($store->rows,fn($r)=>str_contains($r['category'],'obsolete')),'Removed administrative default does not survive');
$registrar->replaceDefaults('contactform');
check(!in_array($custom,$store->rows,true),'Explicit Restore defaults replaces customised links');
check(count(array_filter($store->rows,fn($r)=>$r['module']==='contactform'&&str_starts_with($r['category'],'site_')))===1,'Declared site default installed');
$registrar->replaceData($data);check(count($store->rows)===3,'Fresh defaults also remain idempotent');
echo "PASS custom navigation preserved; defaults seeded and explicitly restorable; repeat updates idempotent\n";
