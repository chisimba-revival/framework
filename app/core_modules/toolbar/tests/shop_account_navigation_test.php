<?php
/** Optional commerce navigation follows the owning module's canonical permission. */
class ChisimbaObject {}
require dirname(__DIR__).'/classes/sitenavigation_class_inc.php';
class ShopNavFixture extends sitenavigation {
 public $authenticated=true,$installed=true,$manager=false,$admin=false,$checks=0;
 public function getObject($name,$module){return match($name){
 'navigationservice'=>new class{public function usesSiteProfile(){return true;}public function links(){return [];}},
 'toolbarsecuritycontext'=>new class($this){public function __construct(private $f){} public function isAuthenticated(){return $this->f->authenticated;}public function isSiteAdministrator(){return $this->f->admin;}public function logoutForm($label){return '<button>Logout</button>';}},
 'modules'=>new class($this){public function __construct(private $f){}public function checkIfRegistered($id){return $id==='shop'&&$this->f->installed;}},
 'shopservice'=>new class($this){public function __construct(private $f){}public function canManage(){++$this->f->checks;return $this->f->manager;}},
 'language'=>new class{public function code2Txt($key,$module){return $key==='mod_shop_manage'?'Manage shop':($key==='mod_shop_my_purchases'?'My purchases':$key);}},
 'iconservice'=>new class{public function render($name,$options){return '<svg data-icon="'.$name.'"></svg>';}},
 'notificationmenu'=>new class{public function show(){return '';}}
 };}
 public function uri($params,$module){return '/index.php?'.http_build_query(['module'=>$module]+($params??[]));}
 public function getResourceUri($file,$module){return $file;}
}
foreach([[false,true,false,false,false],[true,false,true,true,false],[true,true,false,false,false],[true,true,true,false,true],[true,true,true,true,true]] as [$auth,$installed,$manager,$admin,$expected]){
 $f=new ShopNavFixture;$f->authenticated=$auth;$f->installed=$installed;$f->manager=$manager;$f->admin=$admin;$html=$f->show();
 if(str_contains($html,'My purchases')!==($auth&&$installed))throw new RuntimeException('Incorrect customer purchase link audience');
 if(str_contains($html,'Manage shop')!==$expected)throw new RuntimeException('Incorrect shop link audience');
 if((!$auth||!$installed)&&$f->checks)throw new RuntimeException('Unavailable shop service was loaded');
 if($expected&&!str_contains($html,'module=shop&amp;action=manage'))throw new RuntimeException('Wrong management route');
}
echo "PASS: anonymous, uninstalled, ordinary member, delegated manager and administrator navigation\n";
