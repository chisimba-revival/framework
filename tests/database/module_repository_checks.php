<?php
/** Real module repositories on disposable schemas made by schema_checks.php. */
foreach (['kanban'=>['dbkanbanboards','dbkanbantasks','dbkanbansubtasks','dbkanbanaccess'], 'knowledgemap'=>['dbknowledgemaps','dbknowledgemapnodes','dbknowledgemaprelationships','dbknowledgemapaccess'], 'questiongenerator'=>['examstore','workshopstore']] as $module=>$classes) foreach($classes as $class) require_once '/modules/'.$module.'/classes/'.$class.'_class_inc.php';
$table->executeExactPrepared('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
$boards=new dbkanbanboards();injectCallerTable($boards,'tbl_kanban_boards');
$tasks=new dbkanbantasks();injectCallerTable($tasks,'tbl_kanban_tasks');
$subtasks=new dbkanbansubtasks();injectCallerTable($subtasks,'tbl_kanban_subtasks');
$access=new dbkanbanaccess();injectCallerTable($access,'tbl_kanban_access');
$board=$boards->createBoard(['scopetype'=>'personal','scopeid'=>"owner'quoted",'ownerid'=>"owner'quoted",'title'=>"Board's title"]);
ensureBaseline(count($boards->inScope('personal',"owner'quoted"))===1 && $boards->ownedBy("' OR 1=1 --")===[],'Kanban binding/scope');
$task=$tasks->createTask(['boardid'=>$board,'title'=>'Task','status'=>'todo','sortorder'=>4]);
$subtask=$subtasks->createSubtask($task,'Original',2);
ensureBaseline($subtasks->renameSubtask($subtask,'Original','Changed')==='saved','Subtask rename');
ensureBaseline($subtasks->renameSubtask($subtask,'Original','Stale')==='conflict','Subtask stale edit');
ensureBaseline($subtasks->forTask($task)[0]['title']==='Changed','Subtask persisted');
ensureBaseline($tasks->saveTask($task,['status'=>'done','sortorder'=>1]) && $tasks->forBoard($board)[0]['status']==='done','Task order/status');
ensureBaseline($access->replaceUserGrants($board,["invited'person"=>'edit'],'actor'),'Kanban sharing');
ensureBaseline(count($boards->sharedWith("invited'person",'personal'))===1 && $access->userPermission($board,'outsider')===false,'Kanban grant scope');
$table->executeExactPrepared("CREATE TRIGGER fixture_deny_grant BEFORE INSERT ON tbl_kanban_access FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture failure'");
ensureBaseline(!$access->replaceUserGrants($board,['replacement'=>'view'],'actor') && $access->userPermission($board,"invited'person")==='edit','Kanban sharing rollback');
$table->executeExactPrepared('DROP TRIGGER fixture_deny_grant');
ensureBaseline($access->replacePublicLink($board,'opaque','actor') && $access->publicLinkBoardId('opaque')===$board,'Public link creation');
ensureBaseline($access->replacePublicLink($board,null,'actor') && $access->publicLinkBoardId('opaque')===false,'Public link revocation');
$maps=new dbknowledgemaps();injectCallerTable($maps,'tbl_knowledgemap_maps');
$nodes=new dbknowledgemapnodes();injectCallerTable($nodes,'tbl_knowledgemap_nodes');
$mapAccess=new dbknowledgemapaccess();injectCallerTable($mapAccess,'tbl_knowledgemap_access');
$map=$maps->createMap(['scopetype'=>'personal','scopeid'=>"owner'quoted",'ownerid'=>"owner'quoted",'title'=>'Map 🦉']);
$node=['id'=>'node-one','type'=>'standard','title'=>'Original 🦉','description'=>'','presentation'=>[],'order'=>0];
$nodes->addNode($map,$node);
try {$maps->transaction(function() use($maps,$nodes,$map,$node){
 ensureBaseline((bool)$maps->lockAtRevision($map,1),'Map lock');
 $nodes->removeForMap($map);$nodes->addNode($map,array_merge($node,['title'=>'Temporary']));
 $maps->updateAtRevision($map,1,['title'=>'Temporary']);throw new DomainException('forced');
});}catch(DomainException $expected){}
ensureBaseline($nodes->forMap($map)[0]['title']==='Original 🦉' && (int)$maps->one($map)['revision']===1,'Map graph/revision rollback');
$maps->transaction(fn()=>$maps->updateAtRevision($map,1,['title'=>'Saved']));
try {$maps->updateAtRevision($map,1,['title'=>'Stale']);throw new Exception('Stale map accepted');}catch(DomainException $expected){}
ensureBaseline($mapAccess->replaceUserGrants($map,['invited'=>'view'],'actor') && count($maps->sharedWith('invited'))===1 && $maps->sharedWith("' OR 1=1 --")===[],'Map access binding');
class FixtureExamStore extends examstore {public function getObject($name,$module=null){return new class {public function nowStorage(){return '2026-10-11 12:00:00';}};}}
class FixtureWorkshopStore extends workshopstore {public function getObject($name,$module=null){return new class {public function nowStorage(){return '2026-10-11 12:00:00';}};}}
$exams=new FixtureExamStore();injectCallerTable($exams,'tbl_mcqgenerator_exams');
$sets=new FixtureWorkshopStore();injectCallerTable($sets,'tbl_questionworkshop_sets');
$exam=$exams->createExam("owner'quoted",'Exam 🦉',['private'=>'answer']);
$set=$sets->createSet("owner'quoted",'Chapter 2','Private source 🦉',[],5,$exam);
$row=$sets->one($set);ensureBaseline($row['source_text']==='Private source 🦉' && $sets->owned('outsider',1,$exam)===[],'Question set text/owner');
ensureBaseline($sets->claim($row) && !$sets->claim($row),'Duplicate generation claim');
$sets->finish($row,[['stem'=>'Synthetic question']],[],5,true);
$row=$sets->one($set);$sets->saveSet($row,'Chapter 3',[['stem'=>'Edited']],true);
try {$sets->saveSet($row,'Stale',[],false);throw new Exception('Stale set accepted');}catch(DomainException $expected){}
try {$exams->removeOwned($exams->one($exam));throw new Exception('Exam with chapter removed');}catch(DomainException $expected){}
$count=0;$create=function()use(&$count){$count++;return 'synthetic-course-test';};
ensureBaseline($sets->importOnce($set,'fixture-course',$create)===$sets->importOnce($set,'fixture-course',$create) && $count===1,'Course import duplicate');
$row=$sets->one($set);$sets->removeOwned($set,$row['ownerid'],$row['version']);$exams->removeOwned($exams->one($exam));
ensureBaseline($exams->one($exam)===null,'Exam deletion');
foreach ($definitions as $definition){include $definition;$schema->dropTable($tablename);}
echo "PASS: Kanban scopes, sharing rollback, task/subtask edits; map revision/graph rollback; exam/set ownership, stale edits and idempotent imports\n";
