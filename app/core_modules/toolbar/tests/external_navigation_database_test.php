<?php
/** Local PDO integration; every fixture write rolls back. @author Derek Keats */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_NAV_TEST') !== 'local') exit(64);
chdir($argv[1] ?? dirname(__DIR__, 3));
$_SERVER['REQUEST_METHOD']='CLI'; $_SERVER['HTTP_HOST']='chisimba.test:8445';
$_SERVER['SCRIPT_NAME']='/ch/index.php'; $_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true;
require_once 'classes/core/engine_class_inc.php';
$engine=new engine();
$store=$engine->getObject('externalnavigation','toolbar');
$categories=$engine->getObject('navigationcategories','toolbar');
$nav=$engine->getObject('navigationservice','toolbar');
function verify($condition,$message){if(!$condition)throw new RuntimeException($message);}
function rejects(callable $operation){try{$operation();}catch(InvalidArgumentException $e){return;}throw new RuntimeException('Invalid mutation accepted');}
$before=$store->links();$beforeCategories=$categories->categories();
$label='Navigation fixture '.bin2hex(random_bytes(5));
try {
    $store->transaction(function () use($store,$categories,$nav,$label) {
        $key=$categories->saveCategory(['label'=>$label,'position'=>'80']);
        $category=array_values(array_filter($categories->categories(),fn($r)=>$r['group_key']===$key))[0];
        rejects(fn()=>$categories->saveCategory(['label'=>strtoupper($label),'position'=>'1']));
        $input=['label'=>$label,'url'=>'https://example.org/path?q=one&next=two','group_key'=>$key,'position'=>'10','audience'=>'everyone'];
        $store->saveLink($input);
        $row=array_values(array_filter($store->links(),fn($r)=>$r['label']===$label))[0];
        verify($row['url']===$input['url'] && $row['group_key']===$key,'Address and category retained');
        $savedCategory=$categories->category($category['id']);
        $store->saveLink(array_replace($input,['position'=>'1']),$row['id']);
        verify($categories->category($category['id'])===$savedCategory,'Low-order child cannot move category');
        $direct=['id'=>'direct-fixture','module'=>'','group'=>'','groupLabel'=>'','order'=>50];
        $child=['id'=>$row['id'],'module'=>'','group'=>$key,'groupLabel'=>'stale title','order'=>1];
        verify(array_column($categories->arrange([$child,$direct]),'id')===['direct-fixture',$row['id']],'Category stays after top-level link despite child order 1');
        $store->saveLink(array_replace($input,['position'=>'','label'=>$label.' appended']));
        $appended=array_values(array_filter($store->links(),fn($r)=>$r['label']===$label.' appended'))[0];
        verify((int)$appended['position']===2,'Blank order appends inside category');
        rejects(fn()=>$categories->removeCategory($category['id']));
        $categories->saveCategory(['label'=>$label.' renamed','position'=>'80'],$category['id']);
        verify($categories->arrange([$child])[0]['groupLabel']===$label.' renamed','Heading name resolved from category only');
        foreach(['javascript:alert(1)','//example.org','data:text/html,test','https://user:password@example.org','https://example.org/%0d%0aX','https://example.org/\\bad'] as $url) rejects(fn()=>$store->saveLink(array_replace($input,['url'=>$url]),$row['id']));
        foreach(['position'=>'1000','audience'=>'unknown','label'=>'','group_key'=>'missing','new_group'=>'Inline category'] as $field=>$value) rejects(fn()=>$store->saveLink(array_replace($input,[$field=>$value]),$row['id']));
        try {
            $store->transactionWithSavepoint(function()use($store,$input,$row){
                $store->saveLink(array_replace($input,['label'=>'Temporary edit']),$row['id']);
                throw new LogicException('injected-failure');
            });
        }catch(LogicException $e){verify($e->getMessage()==='injected-failure','Expected failure');}
        verify($store->link($row['id'])['label']===$label,'Nested failure rolls back edit');
        $store->saveLink(array_replace($input,['placement'=>'learning','position'=>'']),$row['id']);
        $learning=$store->link($row['id']);
        verify($learning['placement']==='learning'&&$learning['audience']==='members'&&$learning['group_key']==='','Learning heading/audience fixed');
        verify($store->learningLinks(false)===[],'Anonymous Learning links denied');
        verify(in_array($row['id'],array_column($store->learningLinks(true),'id'),true),'Authenticated Learning links returned');
        $store->removeLink($appended['id']);$categories->removeCategory($category['id']);
        verify($categories->category($category['id'])===null,'Empty category removable');
        rejects(fn()=>$store->saveLink($input,$row['id']));
        $store->saveLink(array_replace($input,['group_key'=>'','position'=>'20']),$row['id']);
        verify($store->link($row['id'])['group_key']==='','Top-level empty string retained');
        $store->removeLink($row['id']);verify($store->link($row['id'])===null,'Fixture deletion');

        // Migration retains legacy ties, including internal/external links, and never re-seeds order.
        $legacyKey='migration_'.bin2hex(random_bytes(5));
        $links=[['id'=>'a','module'=>'webinar','external'=>false,'group'=>'','groupLabel'=>'','order'=>30],
            ['id'=>'b','module'=>'toolbar','external'=>true,'group'=>$legacyKey,'groupLabel'=>$label,'order'=>30],
            ['id'=>'c','module'=>'webinar','external'=>false,'group'=>$legacyKey,'groupLabel'=>$label,'order'=>40]];
        $categories->seed($links);
        verify(array_column($categories->arrange($links),'id')===['b','c','a'],'Migration preserves root ties and child order');
        $migrated=$categories->categories();$links[1]['order']=0;$links[1]['groupLabel']='Changed child';$categories->seed($links);
        verify($categories->categories()===$migrated,'Repeated registration cannot reset category name or order');
        throw new LogicException('rollback-fixtures');
    });
}catch(LogicException $e){verify($e->getMessage()==='rollback-fixtures','Expected outer rollback');}
verify($store->links()===$before && $categories->categories()===$beforeCategories,'All fixtures rolled back');
echo "PASS: independent category order/name, append, migration ties/idempotence, category validation, deletion protection, URL/access rules and nested/outer PDO rollback.\n";
