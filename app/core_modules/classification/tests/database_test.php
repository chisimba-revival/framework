<?php
/** Local integration test. Requires an installed module; all fixture writes roll back. @author Derek Keats */
if (PHP_SAPI!=='cli') exit(1);
$app=$argv[1]??dirname(__DIR__,3);
chdir($app);$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true;require_once 'classes/core/engine_class_inc.php';$engine=new engine();
$store=$engine->getObject('classificationstore','classification');
$service=$engine->getObject('classificationservice','classification');
function verify($value,$message){if(!$value)throw new RuntimeException('FAIL: '.$message);}
$scope='test_'.bin2hex(random_bytes(8));
$provider=new class($scope) {
    public $scope; public $read=true;
    function __construct($scope){$this->scope=$scope;}
    function classificationAccess($id){return ['scope_type'=>'personal','scope_id'=>$this->scope,'read'=>$this->read,'edit'=>true];}
};
$service->registerProvider('classification_fixture',$provider);
foreach (['vocabularies','terms','links'] as $table) {
    $rows=$store->query("SHOW TABLE STATUS WHERE Name='tbl_classification_$table'");
    verify(strtolower($rows[0]['engine']??$rows[0]['Engine']??'')==='innodb','transactional '.$table);
}
// Use a test identity only at the service seam; persistence is the real installed database.
class ClassificationDatabaseManager extends classificationservice {
    public $engine;
    public function getObject($name,$moduleName='') {
        if ($name==='user') return new class { function isLoggedIn(){return true;} function isAdmin(){return true;} };
        return $this->engine->getObject($name,$moduleName);
    }
}
$manager=new ClassificationDatabaseManager();$manager->engine=$engine;$manager->init();
$store->query('START TRANSACTION');
try {
    $root=$manager->saveTerm('personal',$scope,'category','Nature');
    $child=$manager->saveTerm('personal',$scope,'category','Birds','',$root['id']);
    $grandchild=$manager->saveTerm('personal',$scope,'category','Sunbirds','',$child['id']);
    verify($grandchild['parent_id']===$child['id'],'persisted hierarchy');
    try {$manager->saveTerm('personal',$scope,'category','Nature','',$grandchild['id'],$root['id']);throw new RuntimeException('Cycle accepted');}
    catch(DomainException $expected){}
    $service->tag('classification_fixture','record',['Bird','Birdsong','BIRD']);
    $terms=$service->forItem('classification_fixture','record','tag');
    verify(count($terms)===2,'round trip and deduplication');
    $term=$terms[0];
    verify(count($service->browse('personal',$scope,'tag',$term['id']))===1,'exact-term browse');
    $provider->read=false;
    verify($service->browse('personal',$scope,'tag',$term['id'])===[],'visibility checked at read time');
    $provider->read=true;
    try {$service->tag('classification_fixture','record',['Transient','<script>']);throw new RuntimeException('Invalid tag accepted');}
    catch(DomainException $expected){}
    verify(count($service->forItem('classification_fixture','record','tag'))===2,'nested rollback preserved associations');
    $v=classificationservice::vocabulary('personal',$scope,'tag');
    verify(count($store->terms($v['id']))===2,'nested rollback removed transient terms');
    $service->assign('classification_fixture','record','tag',[]);
    verify($service->forItem('classification_fixture','record','tag')===[],'clear associations');
} finally { $store->query('ROLLBACK'); }
verify($store->vocabulary(classificationservice::vocabulary('personal',$scope,'tag')['id'])===null,'outer rollback retained ownership of transaction');
$facet=$engine->getObject('systext_facet','systext');
$language=$engine->getObject('language','language');
$singular=$language->code2Txt('mod_classification_category','classification');
$plural=$language->code2Txt('mod_classification_categories','classification');
verify(strpos($singular,'[-')===false && strpos($plural,'[-')===false,'systext resolved');
echo "PASS: live storage, transactions, tag associations, exact browsing, private visibility and systext ($singular/$plural); fixtures rolled back\n";
