<?php
/** Exercise administration, POST/CSRF and draft retention at the controller boundary. */
$GLOBALS['kewl_entry_point_run'] = true;
class controller {
    public $params = [], $vars = [], $objects = [];
    public function getParam($key,$default=''){return $this->params[$key]??$default;}
    public function getObject($name,$module=''){return $this->objects[$name];}
    public function setVarByRef($key,&$value){$this->vars[$key]=$value;}
    public function setVar($key,$value){$this->vars[$key]=$value;}
    public function nextAction($action){return $action;}
}
require dirname(__DIR__).'/controller.php';
function verify($condition,$message){if(!$condition)throw new RuntimeException($message);}
$c = new toolbar();
$c->objSecurity = new class {
    public $authenticated=true, $admin=true;
    public function isAuthenticated(){return $this->authenticated;}
    public function isSiteAdministrator(){return $this->admin;}
};
$csrf = new class {
    public $valid=true;
    public function consume($context,$token){$valid=$this->valid&&$token==='good';$this->valid=false;return $valid;}
    public function issue($context){return 'fresh';}
};
(new ReflectionProperty(toolbar::class,'csrf'))->setValue($c,$csrf);
$store = new class {
    public $saves=0, $deletes=0, $invalid=false;
    public function saveLink($input,$id){if($this->invalid)throw new InvalidArgumentException('invalid');$this->saves++;}
    public function removeLink($id){$this->deletes++;}
    public function link($id){return ['id'=>$id,'label'=>'Saved'];}
};
$c->objects=['externalnavigation'=>$store,'navigationservice'=>new class {public function editorGroups(){return [];}}];
$c->params=['toolbar_csrf'=>'good','label'=>'My draft','url'=>'https://example.org','position'=>'10','audience'=>'everyone'];
$_SERVER['REQUEST_METHOD']='POST';
foreach ([false,true] as $authenticated) {
    $c->objSecurity->authenticated=$authenticated;$c->objSecurity->admin=false;
    foreach (['addexternal','editexternal','saveexternal','deleteexternal'] as $action) {
        try{$c->dispatch($action);throw new LogicException('Unauthorised action accepted');}
        catch(RuntimeException $expected){verify($expected->getMessage()==='Toolbar administration denied','Denied before processing');}
    }
}
$c->objSecurity->authenticated=$c->objSecurity->admin=true;
$_SERVER['REQUEST_METHOD']='GET';
foreach(['saveexternal','deleteexternal'] as $action){
    try{$c->dispatch($action);throw new LogicException('GET mutation accepted');}
    catch(RuntimeException $expected){verify($expected->getMessage()==='Toolbar mutations require POST','POST enforced');}
}
$_SERVER['REQUEST_METHOD']='POST';$csrf->valid=false;
verify($c->dispatch('saveexternal')==='external_link_tpl.php','Expired form redisplayed');
verify($c->vars['externalLink']['label']==='My draft'&&$c->vars['toolbarCsrf']==='fresh','Draft kept with fresh token');
verify($store->saves===0,'Expired form never writes');
$csrf->valid=true;$store->invalid=true;
verify($c->dispatch('saveexternal')==='external_link_tpl.php'&&$c->vars['externalError']==='invalid','Invalid form retained');
$csrf->valid=true;$store->invalid=false;
verify($c->dispatch('saveexternal')==='editlinks'&&$store->saves===1,'Valid write redirects');
$c->dispatch('saveexternal');verify($store->saves===1,'Consumed token cannot replay');
$c->params['id']='fixture';
try{$c->dispatch('deleteexternal');throw new LogicException('Invalid CSRF deletion accepted');}
catch(RuntimeException $expected){verify($store->deletes===0,'CSRF deletion denied');}
$csrf->valid=true;
verify($c->dispatch('deleteexternal')==='editlinks'&&$store->deletes===1,'Authorised deletion');
echo "PASS: anonymous/member denial, POST/CSRF, replay prevention, draft retention and authorised writes.\n";
$categories = new class {
    public $saves=0,$deletes=0;
    public function saveCategory($input,$id){$this->saves++;}
    public function category($id){return ['id'=>$id,'label'=>'Saved category','position'=>'80'];}
    public function removeCategory($id){$this->deletes++;}
};
$c->objects['navigationcategories']=$categories;
$c->params=['toolbar_csrf'=>'good','label'=>'Category draft','position'=>'80'];
foreach([false,true] as $authenticated){
    $c->objSecurity->authenticated=$authenticated;$c->objSecurity->admin=false;
    foreach(['addcategory','editcategory','savecategory','deletecategory'] as $action){
        try{$c->dispatch($action);throw new LogicException('Unauthorised category action');}
        catch(RuntimeException $e){verify($e->getMessage()==='Toolbar administration denied','Category admin boundary');}
    }
}
$c->objSecurity->authenticated=$c->objSecurity->admin=true;
$_SERVER['REQUEST_METHOD']='GET';
foreach(['savecategory','deletecategory'] as $action){
    try{$c->dispatch($action);throw new LogicException('GET category mutation');}
    catch(RuntimeException $e){verify($e->getMessage()==='Toolbar mutations require POST','Category POST boundary');}
}
$_SERVER['REQUEST_METHOD']='POST';$csrf->valid=false;
verify($c->dispatch('savecategory')==='navigation_category_tpl.php'&&$c->vars['category']['label']==='Category draft','Expired category draft retained');
verify($categories->saves===0,'Expired category not saved');
$csrf->valid=true;verify($c->dispatch('savecategory')==='editlinks'&&$categories->saves===1,'Category save');
$c->dispatch('savecategory');verify($categories->saves===1,'Category token replay denied');
echo "PASS: category admin, POST/CSRF, draft retention and replay boundaries.\n";

$c->objRegister=new class{public function readModuleData($module){return [];}};
$c->objPermissionService=new class{public function areaIdForName(...$args){return null;}};
$c->objects['modulelinkdefaults']=new class{public function moduleName($module){return 'Contact form';}public function destinations($module,$data){return ['default'=>['label'=>'Contact']];}};
$c->params=['moduleName'=>'contactform','destination'=>'default','position'=>'25','groupCode'=>'','toolbar_csrf'=>'expired','menuLabel'=>'News and stories'];
$csrf->valid=false;
verify($c->dispatch('savesite')==='module_link_tpl.php','Expired module-link form redisplayed');
verify($c->vars['siteSubmitted']['destination']==='default'&&$c->vars['siteSubmitted']['position']==='25'&&$c->vars['toolbarCsrf']==='fresh','Module destination/order draft preserved');
echo "PASS: simplified module-link CSRF expiry preserves choices and renews token.\n";

verify($c->vars['siteSubmitted']['menuLabel']==='News and stories','Expired form retains custom menu label');
