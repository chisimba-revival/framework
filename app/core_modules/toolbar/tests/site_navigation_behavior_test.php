<?php
/** Public navigation must preserve canonical role/context boundaries. @author Derek Keats */
class ChisimbaObject {
    public static $objects = array();
    public static $params = array('module'=>'webinar','action'=>'archive');
    public function getObject($name, $module) { return self::$objects[$name]; }
    public function getParam($key, $default) { return self::$params[$key] ?? $default; }
    public function getResourceUri($path, $module) { return '/resources/'.$path; }
    public function uri($params, $module) { return '/index.php?' . http_build_query(array('module'=>$module)+$params); }
}
require dirname(__DIR__).'/classes/navigationservice_class_inc.php';
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$config = new class { public $profile='site'; public function getValue(...$args) { return $this->profile; } };
$security = new class {
    public $admin=false; public $granted=false;
    public function isSiteAdministrator() { return $this->admin; }
    public function mayUseRight($right) { return $right==='' || $this->granted; }
};
$context = new class { public $inside=false; public function isInContext() { return $this->inside; } public function getContextCode(){return 'test';} };
$plugins = new class { public $enabled=false; public function isContextPlugin(...$args){return $this->enabled;} };
$store = new class { public $rows=[]; public function siteLinks(){return $this->rows;} };
ChisimbaObject::$objects = array('dbsysconfig'=>$config,'toolbarsecuritycontext'=>$security,'dbcontext'=>$context,'dbcontextmodules'=>$plugins,'dbmenu'=>$store,
    'modules'=>new class{public function checkIfRegistered($module){return $module!=='missing';}},
    'language'=>new class{public function code2Txt($key,$module){return $key;}});
function row($id,$category,$right='',$admin=0,$depends=0,$module='webinar') { return array('id'=>$id,'category'=>$category,'permissions'=>$right,'adminonly'=>$admin,'dependscontext'=>$depends,'module'=>$module); }
$store->rows=[row('b','site_020||speakers|users|speakers'),row('a','site_010||archive|calendar|webinars'),row('c','site_030||private|lock|private','7'),row('d','site_040||admin|settings|admin','',1),row('e','site_050||course|book|course','',0,1),row('f','site_060||bad?url|link|bad'),row('g','site_070||gone|link|gone','',0,0,'missing')];
$service=new navigationservice();
check(array_column($service->links(),'id')===['a','b'],'Guest only sees public installed destinations in numeric order');
check($service->links()[0]['active'],'Active destination exposed');
check($service->provides('webinar',['archive','speakers']),'Duplicate local navigation may be omitted');
check(!$service->provides('webinar',['private']),'Restricted route not reported present');
$security->granted=true;check(array_column($service->links(),'id')===['a','b','c'],'Member gets granted destination without administrator item');
$security->admin=true;check(array_column($service->links(),'id')===['a','b','c','d'],'Administrator item appears');
$context->inside=true;check(count($service->links())===4,'Disabled context module hidden');
$plugins->enabled=true;check(count($service->links())===5,'Enabled context module available');
$config->profile='dropdown';check($service->links()===[] && !$service->provides('webinar',['archive']),'Learning profile untouched');
echo "PASS: ordering, guests, members, administrators, right grants, context enablement, invalid/uninstalled routes, active state and default profile.\n";

require dirname(__DIR__).'/classes/sitenavigation_class_inc.php';
ChisimbaObject::$objects['navigationservice'] = new class {
    public function usesSiteProfile(){return true;}
    public function links(){return array(array('id'=>'x','module'=>'webinar','action'=>'archive','icon'=>'calendar','group'=>'g','groupLabel'=>'<script>group</script>','label'=>'<script>label</script>','url'=>'/index.php?module=webinar&action=archive','active'=>true));}
};
ChisimbaObject::$objects['toolbarsecuritycontext'] = new class { public function isAuthenticated(){return false;} };
ChisimbaObject::$objects['iconservice'] = new class { public function render(...$args){return '<svg></svg>';} };
$output=(new sitenavigation())->show();
check(!str_contains($output,'<script>group') && !str_contains($output,'<script>label') && str_contains($output,'&lt;script&gt;label'),'Authored labels and groups must be escaped');
check(str_contains($output,'&amp;action=archive'),'URL attributes escaped');
check(str_contains($output,'aria-current="page"'),'Current link has semantic state');
echo "PASS: renderer escapes authored labels/groups and URL attributes.\n";
