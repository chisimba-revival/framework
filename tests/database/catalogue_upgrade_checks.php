<?php
/** Actual catalogue table path and repeatable hooks on disposable fixtures. */
require_once $app.'/classes/core/dbtablemanager_class_inc.php';
require_once $app.'/core_modules/modulecatalogue/classes/modulesadmin_class_inc.php';
require_once $app.'/core_modules/modulecatalogue/classes/tableinfo_class_inc.php';
class FixtureCatalogue extends modulesadmin {
    public function attach($connection) {$this->useConnection($connection);}
    public function newObject($name,$moduleName='') {
        if ($name!=='tableinfo') throw new LogicException('Unexpected catalogue dependency');
        $info=new tableinfo();injectCallerTable($info,'tbl_users');return $info;
    }
    public function errorCallback($error) {throw new RuntimeException('Catalogue fixture failed: '.$error);}
}
$catalogue=new FixtureCatalogue();
$connection=(new ReflectionProperty(dbTable::class,'_db'))->getValue($table);
$catalogue->attach($connection);
$catalogue->objModFile=new class {
    public function findSqlFile($module,$name) {return '/modules/'.$module.'/sql/'.$name.'.sql';}
};
$makeTable=new ReflectionMethod(modulesadmin::class,'makeTable');
require_once '/modules/shop/classes/shopstore_class_inc.php';
require_once '/modules/shop/patches/installscripts_class_inc.php';
class FixtureShopHook extends shop_installscripts {
    public $services;
    public function getObject($name,$moduleName='') {return $this->services[$name];}
}
$shop=new shopstore();injectCallerTable($shop,null);
$hook=new FixtureShopHook();injectCallerTable($hook,null);
$hook->services=['modulesadmin'=>$catalogue,'shopstore'=>$shop,
 'permissionservice'=>new class {public function ensureArea($a,$b){return 'fixture';}public function ensureRight($a,$b){return true;}},
 'dbsysconfig'=>new class {public function getValue($a,$b){return 'fixture-existing-signing-key';}}];
$shopTables=[];
foreach(glob('/modules/shop/sql/tbl_shop_*.sql') as $file) {
 $name=basename($file,'.sql');$shopTables[]=$name;
 ensureBaseline($makeTable->invoke($catalogue,$name,'shop')===true,'Catalogue create failed');
}
// Simulate a pre-upgrade installation with real stored data.
foreach(['product_type','product_options','display_order'] as $column) $catalogue->executeSchemaStatement('ALTER TABLE tbl_shop_books DROP COLUMN '.$column);
$catalogue->executeSchemaStatement('ALTER TABLE tbl_shop_orders DROP COLUMN user_id');
$shop->add('books',['id'=>str_repeat('a',32),'title'=>"Preserve O'Reilly",'isbn'=>'','description'=>'','image_url'=>'','price_minor'=>15000,'stock'=>12,'status'=>'published','revision'=>4]);
$hook->postinstall();
$before=$table->selectExactPrepared('SELECT * FROM tbl_shop_books');
$indexes=$table->selectExactPrepared('SHOW INDEX FROM tbl_shop_books');
foreach($shopTables as $name) ensureBaseline($makeTable->invoke($catalogue,$name,'shop')===true,'Catalogue repeated create failed');
$hook->postinstall();
ensureBaseline($before===$table->selectExactPrepared('SELECT * FROM tbl_shop_books'),'Repeated upgrade changed product data');
ensureBaseline($indexes===$table->selectExactPrepared('SHOW INDEX FROM tbl_shop_books'),'Repeated upgrade changed indexes');
$row=$shop->one('books',str_repeat('a',32));
ensureBaseline($row['title']==="Preserve O'Reilly" && (int)$row['stock']===12 && (int)$row['revision']===4 && $row['product_type']==='physical','Legacy upgrade lost data');
$shop->save('books',str_repeat('a',32),['product_type'=>'virtual','product_options'=>'{"kind":"fixture"}']);
$hook->postinstall();ensureBaseline($shop->one('books',str_repeat('a',32))['product_type']==='virtual','Upgrade reset product type');
// Preserve MDB2's logical index names over existing physical *_idx names.
$catalogue->createConstraint('tbl_shop_books','fixture_identity',['unique'=>true,'fields'=>['id'=>[]]]);
ensureBaseline(in_array('fixture_identity',$catalogue->listTableConstraints('tbl_shop_books'),true),'Constraint logical name changed');
$catalogue->dropConstraint('tbl_shop_books','fixture_identity');
foreach($shopTables as $name) $catalogue->dropTable($name);
echo "PASS: actual catalogue table creation/repeat; shop legacy upgrade twice preserves data and constraints, shared logical index names\n";
