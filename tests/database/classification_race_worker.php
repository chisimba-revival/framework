<?php
/** Child process, used only inside the isolated fixture network. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_DB_BASELINE') !== '1') exit(64);
$app = '/framework/app';
set_include_path($app . PATH_SEPARATOR . $app . '/lib/pear' . PATH_SEPARATOR . get_include_path());
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject {}
require_once $app . '/classes/core/dbtable_class_inc.php';
$dsn = ['phptype'=>'mysqli','hostspec'=>'baseline-db','username'=>'root','password'=>getenv('CHISIMBA_DB_PASSWORD'),'database'=>'contract_fixture'];
if ($argv[1] === 'PDO') {
    $db = ChisimbaDatabaseConnection::fromLegacyDsn($dsn);
} else {
    require_once 'MDB2.php';
    $db = MDB2::connect($dsn);
    if (PEAR::isError($db)) throw new RuntimeException('Fixture connection failed');
    $db->setFetchMode(MDB2_FETCHMODE_ASSOC);
    $db->setOption('portability', MDB2_PORTABILITY_ALL);
}
$table = new dbTable();
(new ReflectionProperty(dbTable::class, '_db'))->setValue($table, $db);
require_once $app.'/core_modules/classification/classes/classificationstore_class_inc.php';
require_once $app.'/core_modules/classification/classes/classificationservice_class_inc.php';
$store=new classificationstore();
(new ReflectionProperty(dbTable::class,'_db'))->setValue($store,$db);
$store->dbType='mysqli';
$service=new class extends classificationservice {
    public $fixtureStore;
    public function getObject($name,$module=null){
        if($name==='classificationstore')return $this->fixtureStore;
        throw new LogicException('Unexpected worker dependency');
    }
};
$service->fixtureStore=$store;$service->init();
$service->registerProvider('fixture',new class {
    public function classificationAccess($id){return ['scope_type'=>'personal','scope_id'=>'race-classification','read'=>true,'edit'=>true];}
});
$service->tag('fixture','race-item-'.$argv[2],['Shared']);
echo 'worker-ok';
