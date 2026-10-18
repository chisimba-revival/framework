<?php
/** Exercise the actual catalogue hooks, including the hyphenated module ID. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    public $objEngine, $admin, $permissions;
    public function getObject($name,$module=null){return $name === 'permissionservice' ? $this->permissions : $this->admin;}
}
class dbTable extends ChisimbaObject {}
$workspace=dirname(__DIR__,3);
require $workspace.'/modules/simpleblog/patches/installscripts_class_inc.php';
require $workspace.'/modules/payment-service/patches/installscripts_class_inc.php';
foreach (['simpleblog_installscripts'=>1,'payment-service_installscripts'=>2] as $name=>$statements) {
    $admin=new class {
        public $tables=[], $queries=[], $fail=false;
        public function listDbTables(){return $this->tables;}
        public function executeSchemaStatement($sql){$this->queries[]=$sql;return !$this->fail;}
    };
    $hook=new $name();$hook->admin=$admin;
    $hook->permissions=new class {
        public $rights=[];
        public function ensureArea($app,$module){return 'simpleblog';}
        public function ensureRight($area,$right){$this->rights[]=$right;return true;}
    };
    $hook->preinstall();
    if($admin->queries)throw new RuntimeException('Fresh install must wait for registered tables');
    $admin->tables=['tbl_simpleblog_posts','tbl_payment_service_intents','tbl_payment_service_prices'];
    $hook->preinstall();$hook->postinstall();
    if($name==='simpleblog_installscripts' && $hook->permissions->rights!==['personal_publish','site_publish','site_manage'])throw new RuntimeException('Publishing capability definitions lost during reconciliation');
    if(count($admin->queries)!==$statements*2)throw new RuntimeException('Migration not called through both catalogue hooks');
    foreach($admin->queries as $sql)if(!str_starts_with(trim($sql),'ALTER TABLE tbl_'))throw new RuntimeException('Unexpected migration statement');
    $admin->fail=true;
    try{$hook->preinstall();throw new LogicException('Failed migration accepted');}catch(RuntimeException $expected){}
    echo 'PASS: '.$name." fresh/update/repeat hooks and failure propagation\n";
}
