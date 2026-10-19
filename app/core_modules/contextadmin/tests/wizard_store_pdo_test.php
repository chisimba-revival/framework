<?php
if(PHP_SAPI!=='cli'||getenv('CHISIMBA_WIZARD_TEST')!=='1')exit("Set CHISIMBA_WIZARD_TEST=1 in a disposable clone.\n");
if(!str_contains(file_get_contents('config/dbdetails_inc.php'),'@editor-db/editor_fixture'))exit(64);
$GLOBALS['kewl_entry_point_run']=true;
$_SERVER+=['REQUEST_METHOD'=>'CLI','HTTP_HOST'=>'127.0.0.1:8088','SCRIPT_NAME'=>'/ch/index.php','QUERY_STRING'=>''];
require 'classes/core/engine_class_inc.php';$engine=new engine();
set_error_handler(static function($n,$s){if(error_reporting()&$n)throw new RuntimeException($s);return false;});
$engine->getObject('coursewizardstore','contextadmin');
class WizardFixtureStore extends coursewizardstore {
 public $fail=false, $failOutcomes=false;
 public function getObject($name,$moduleName='') {if($name==='contextsettingsaccess')return new class {function canEdit($code){return true;}};return parent::getObject($name,$moduleName);}
 public function executePrepared(#[\SensitiveParameter] $sql, #[\SensitiveParameter] array $parameters=[]): int {
  $result=parent::executePrepared($sql,$parameters);if(($this->fail&&str_starts_with($sql,'DELETE FROM tbl_contextmodules'))||($this->failOutcomes&&str_starts_with($sql,'UPDATE tbl_context_learneroutcomes')))throw new RuntimeException('injected');return $result;
 }
}
$repo=new WizardFixtureStore($engine,'contextadmin');$courses=$engine->getObject('dbcontext','context');$outcomes=$engine->getObject('dbcontext_learneroutcomes','context');$plugins=$engine->getObject('dbcontextmodules','context');
$code='admissionfree';$original=$courses->getContext($code);
function check($ok,$why){if(!$ok)throw new RuntimeException($why);}
function rejected($callback,$message){try{$callback();throw new LogicException('accepted '.$message);}catch(RuntimeException $e){check($e->getMessage()===$message,'unexpected failure: '.$e->getMessage());}}
try{$repo->transaction(function()use($repo,$courses,$outcomes,$plugins,$code){
 $repo->executePrepared('DELETE FROM tbl_context_learneroutcomes WHERE contextcode = ?',[$code]);
 $repo->executePrepared('DELETE FROM tbl_contextmodules WHERE contextcode = ?',[$code]);
 $repo->executePrepared('UPDATE tbl_context SET goals = ?, showcomment = ?, alerts = ? WHERE contextcode = ?', ['<p>Legacy goal</p>','0','1',$code]);
 $before=$courses->getContext($code);$rows=$outcomes->getContextOutcomes($code);check($rows===[]&&$courses->getContext($code)===$before,'reader must not write');
 $outcomes->migrateLegacyGoals();$rows=$outcomes->getContextOutcomes($code);check(count($rows)===1,'legacy goal migrated');
 $after=$courses->getContext($code);check($after['showcomment']===$before['showcomment']&&$after['alerts']===$before['alerts'],'unrelated settings retained');
 $outcomes->migrateLegacyGoals();check($outcomes->getContextOutcomes($code)===$rows,'repeat migration preserves IDs');
 $id=$rows[0]['id'];$state=$repo->snapshot($code,'outcomes');
 $changes=['existing'=>[$id=>'<p>Edited</p>'],'remove'=>[],'new'=>['<p>New</p>']];
 $repo->failOutcomes=true;rejected(fn()=>$repo->save($code,'outcomes',$state['revision'],$changes),'injected');$repo->failOutcomes=false;check($repo->snapshot($code,'outcomes')===$state,'outcome update rollback');
 $repo->save($code,'outcomes',$state['revision'],$changes);$saved=$repo->snapshot($code,'outcomes');check(count($saved['rows'])===2,'add and edit atomic');
 rejected(fn()=>$repo->save($code,'outcomes',$state['revision'],$changes),'stale');check($repo->snapshot($code,'outcomes')===$saved,'stale unchanged');
 $changes=['existing'=>array_column($saved['rows'],'learningoutcome','id'),'remove'=>['foreign'],'new'=>[]];
 rejected(fn()=>$repo->save($code,'outcomes',$saved['revision'],$changes),'invalid');
 $repo->executePrepared('INSERT INTO tbl_context_learneroutcomes_dependencies (id,contextcode,learningoutcomeid,dependent_module,dependent_itemid,createdby) VALUES (?,?,?,?,?,?)',[bin2hex(random_bytes(16)),$code,$id,'fixture','item','fixture']);
 $changes['remove']=[$id];rejected(fn()=>$repo->save($code,'outcomes',$saved['revision'],$changes),'linked');check($repo->snapshot($code,'outcomes')===$saved,'linked deletion rollback');
 $plugins->addModule($code,'keep');$plugins->addModule($code,'unoffered');$p=$repo->snapshot($code,'plugins');
 rejected(fn()=>$repo->save($code,'plugins',$p['revision'],['forged'],['keep','new']),'invalid');
 $repo->fail=true;rejected(fn()=>$repo->save($code,'plugins',$p['revision'],['new'],['keep','new']),'injected');$repo->fail=false;
 check($repo->snapshot($code,'plugins')===$p,'delete rolled back after injected failure');
 $repo->save($code,'plugins',$p['revision'],['new'],['keep','new']);$p2=$repo->snapshot($code,'plugins');
 check(array_column($p2['rows'],'moduleid')===['new','unoffered'],'replace offered only, retain unoffered');
 rejected(fn()=>$repo->save($code,'plugins',$p['revision'],[],['keep','new']),'stale');
 throw new LogicException('rollback-fixture');
});}catch(LogicException $e){if($e->getMessage()!=='rollback-fixture')throw $e;}
check($courses->getContext($code)===$original,'outer transaction owns final rollback');
echo "PASS PDO read-only outcomes, repeatable goals migration, retained settings/IDs, scoped edits, stale/linked denial, plugin allowlist, injected rollback and nested ownership\n";
