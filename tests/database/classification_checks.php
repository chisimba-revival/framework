<?php
/** Actual classification service/store over both real drivers; synthetic data only. */
require_once $app.'/core_modules/classification/classes/classificationstore_class_inc.php';
require_once $app.'/core_modules/classification/classes/classificationservice_class_inc.php';
$table->executeExactPrepared('CREATE TABLE tbl_classification_vocabularies (id VARCHAR(32) NOT NULL,scope_type VARCHAR(16) NOT NULL,scope_id VARCHAR(64) NOT NULL,kind VARCHAR(16) NOT NULL,UNIQUE KEY classification_id_unique(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin');
$table->executeExactPrepared('CREATE TABLE tbl_classification_terms (id VARCHAR(32) NOT NULL,vocabulary_id VARCHAR(32) NOT NULL,name VARCHAR(150) NOT NULL,slug VARCHAR(190) NOT NULL,parent_id VARCHAR(32) NOT NULL,UNIQUE KEY classification_id_unique(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin');
$table->executeExactPrepared('CREATE TABLE tbl_classification_links (id VARCHAR(32) NOT NULL,vocabulary_id VARCHAR(32) NOT NULL,term_id VARCHAR(32) NOT NULL,module_id VARCHAR(64) NOT NULL,item_id VARCHAR(64) NOT NULL,UNIQUE KEY classification_id_unique(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin');
$classificationStore=new classificationstore();injectCallerTable($classificationStore,'tbl_classification_vocabularies');
class FixtureClassificationService extends classificationservice {
    public $fixtureStore;
    public function getObject($name,$module=null) {
        if($name==='classificationstore')return $this->fixtureStore;
        if($name==='user')return new class {public function isLoggedIn(){return true;} public function isAdmin(){return true;}};
        throw new LogicException('Unexpected fixture dependency');
    }
}
$classification=new FixtureClassificationService();$classification->fixtureStore=$classificationStore;$classification->init();
$provider=new class {
    public $read=true;
    public function classificationAccess($id){return ['scope_type'=>'personal','scope_id'=>"scope'one",'read'=>$this->read,'edit'=>true];}
};
$classification->registerProvider('fixture',$provider);
// Raw SQL outer transactions are used by existing callers: the shared savepoint
// path must recognise server state even when MDB2's client flag is false.
$table->executeExactPrepared('START TRANSACTION');
try {
    $classification->tag('fixture',"item'one",['Bird','BIRD','Birdsong']);
    $terms=$classification->forItem('fixture',"item'one",'tag');
    ensureBaseline(count($terms)===2 && $terms[0]['parent_id']==='', 'Classification deduplication/empty parent');
    $v=classificationservice::vocabulary('personal',"scope'one",'tag');
    try {$classification->tag('fixture',"item'one",['Transient','<script>']);throw new RuntimeException('Invalid tag accepted');}
    catch(DomainException $expected){}
    ensureBaseline(count($classification->forItem('fixture',"item'one",'tag'))===2 && count($classificationStore->terms($v['id']))===2, 'Savepoint rollback failed');
    ensureBaseline(count($classification->browse('personal',"scope'one",'tag',$terms[0]['id']))===1,'Exact-term browsing');
    $provider->read=false;
    ensureBaseline($classification->browse('personal',"scope'one",'tag',$terms[0]['id'])===[],'Revoked visibility leaked item');
    $provider->read=true;
    ensureBaseline($classificationStore->itemTerms($v['id'],'fixture',"item'one' OR 1=1 --")===[], 'Classification binding isolation');
    $root=$classification->saveTerm('personal',"scope'one",'category','Nature');
    $child=$classification->saveTerm('personal',"scope'one",'category','Birds','',$root['id']);
    try {$classification->deleteTerm('personal',"scope'one",'category',$root['id']);throw new RuntimeException('Parent with child deleted');}
    catch(DomainException $expected){}
    ensureBaseline((int)$table->selectExactPrepared('SELECT @@in_transaction AS active')[0]['active']===1,'Classification committed caller transaction');
} finally {$table->executeExactPrepared('ROLLBACK');}
ensureBaseline($classificationStore->vocabulary($v['id'])===null,'Outer rollback failed');
// The newer checked transaction owner must likewise retain control over savepoints.
$table->transaction(function() use ($classification,$table) {
    $classification->tag('fixture','committed',['Kept']);
    try {$classification->tag('fixture','committed',['Transient','<script>']);throw new RuntimeException('Invalid tag accepted');}
    catch(DomainException $expected){}
    try {$table->commitTransaction();throw new RuntimeException('Savepoint lost outer ownership guard');}
    catch(LogicException $expected){}
});
ensureBaseline(count($classification->forItem('fixture','committed','tag'))===1,'Outer commit after nested rollback failed');
$classification->assign('fixture','committed','tag',[]);
ensureBaseline($classification->forItem('fixture','committed','tag')===[],'Clear associations failed');
// An index merely having the expected name is not sufficient for identity safety.
$table->executeExactPrepared('ALTER TABLE tbl_classification_links DROP INDEX classification_id_unique, ADD UNIQUE KEY classification_id_unique(id,item_id)');
$invalidStore=new classificationstore();injectCallerTable($invalidStore,'tbl_classification_vocabularies');
try {$invalidStore->locked($v,fn()=>true);throw new LogicException('Composite identity index accepted');}
catch(RuntimeException $expected){}
$table->executeExactPrepared('ALTER TABLE tbl_classification_links DROP INDEX classification_id_unique, ADD UNIQUE KEY classification_id_unique(id)');
$jobs=[];
for($i=0;$i<2;$i++) {
    $pipes=[];
    $process=proc_open([PHP_BINARY,'-d','auto_prepend_file=',__DIR__.'/classification_race_worker.php',$table->dbLayer,(string)$i], [1=>['pipe','w'],2=>['pipe','w']],$pipes);
    ensureBaseline(is_resource($process),'Classification worker start failed');
    $jobs[]=[$process,$pipes];
}
foreach($jobs as [$process,$pipes]) {
    $output=stream_get_contents($pipes[1]);$errors=stream_get_contents($pipes[2]);
    fclose($pipes[1]);fclose($pipes[2]);
    ensureBaseline(proc_close($process)===0 && $output==='worker-ok' && $errors==='','Classification worker failed');
}
$raceVocabulary=classificationservice::vocabulary('personal','race-classification','tag');
ensureBaseline(count($classificationStore->terms($raceVocabulary['id']))===1,'Concurrent classification duplicated a term');
ensureBaseline(count($classificationStore->itemTerms($raceVocabulary['id'],'fixture','race-item-0'))===1
    && count($classificationStore->itemTerms($raceVocabulary['id'],'fixture','race-item-1'))===1,'Concurrent classification lost an item association');
foreach(['links','terms','vocabularies'] as $suffix)$table->executeExactPrepared('DROP TABLE tbl_classification_'.$suffix);
echo "PASS: classification bindings, nested savepoint rollback, outer ownership, hierarchy, visibility, two-worker contention and index guard\n";
