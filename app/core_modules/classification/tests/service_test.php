<?php
/** Behavioural service contract with rollback-capable in-memory persistence. @author Derek Keats */
if (PHP_SAPI!=='cli') exit(1);
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject { public static $objects=[]; public function getObject($class,$module=null) { return self::$objects[$class]; } }
require dirname(__DIR__).'/classes/classificationservice_class_inc.php';
class TestStore
{
    public $terms=[]; public $links=[];
    public function locked($v,$fn) { $old=[$this->terms,$this->links];try{return $fn();}catch(Throwable $e){[$this->terms,$this->links]=$old;throw $e;} }
    public function terms($v) { return array_values(array_filter($this->terms,fn($t)=>$t['vocabulary_id']===$v)); }
    public function saveTerm($t) { $this->terms[$t['id']]=$t; }
    public function removeTerm($id) { foreach($this->terms as $t)if($t['parent_id']===$id)throw new DomainException('in_use');foreach($this->links as $l)if($l['term_id']===$id)throw new DomainException('in_use');unset($this->terms[$id]); }
    public function replaceLinks($v,$m,$i,$ids) { $this->links=array_filter($this->links,fn($l)=>!($l['vocabulary_id']===$v&&$l['module_id']===$m&&$l['item_id']===$i));foreach($ids as $id)$this->links[]= ['id'=>hash('md5',$v.$m.$i.$id),'vocabulary_id'=>$v,'module_id'=>$m,'item_id'=>$i,'term_id'=>$id]; }
    public function itemTerms($v,$m,$i) { $result=[];foreach($this->links as $l)if($l['vocabulary_id']===$v&&$l['module_id']===$m&&$l['item_id']===$i)$result[]=$this->terms[$l['term_id']];return $result; }
    public function candidates($v,$t,$after,$limit) { $rows=array_values(array_filter($this->links,fn($l)=>$l['vocabulary_id']===$v&&$l['term_id']===$t&&strcmp($l['id'],$after)>0));usort($rows,fn($a,$b)=>strcmp($a['id'],$b['id']));return array_slice($rows,0,$limit); }
}
class TestUser { public $admin=true; public $logged=true;public function isLoggedIn(){return $this->logged;}public function isAdmin(){return $this->admin;}public function userId(){return 'author';}public function isCourseAdmin($id){return false;} }
class TestProvider { public $records=[];public function classificationAccess($id){return $this->records[$id]??null;} }
function check($condition,$name) { if(!$condition)throw new RuntimeException('FAIL '.$name); }
function denied($fn,$name) { try{$fn();}catch(DomainException|InvalidArgumentException $e){return;}throw new RuntimeException('FAIL '.$name); }
$store=new TestStore();$user=new TestUser();
ChisimbaObject::$objects=['classificationstore'=>$store,'user'=>$user,'permissionservice'=>new class {function areaIdForName(){return null;}},'dbcontext'=>new class {function getContextDetails($id){return ['title'=>'Test'];}}];
$service=new classificationservice();$service->init();$provider=new TestProvider();$service->registerProvider('sample',$provider);
$provider->records=['public'=>['scope_type'=>'site','scope_id'=>'site','read'=>true,'edit'=>true], 'draft'=>['scope_type'=>'site','scope_id'=>'site','read'=>false,'edit'=>true], 'private'=>['scope_type'=>'personal','scope_id'=>'author','read'=>false,'edit'=>true]];
$root=$service->saveTerm('site','site','category','Nature');$child=$service->saveTerm('site','site','category','Birds','',''.$root['id']);
denied(fn()=>$service->saveTerm('site','site','category','Nature','',$child['id'],$root['id']),'cycle');
$renamed=$service->saveTerm('site','site','category','Natural world','','',$root['id']);check($renamed['slug']==='nature'&&$renamed['id']===$root['id'],'stable ID and slug');
denied(fn()=>$service->saveTerm('site','site','category','Different','nature'),'slug collision');
denied(fn()=>$service->saveTerm('site','site','category','birds'),'case duplicate');
denied(fn()=>$service->saveTerm('site','site','tag','Bad parent','',$root['id']),'tag hierarchy');
$service->assign('sample','public','category',[$child['id']]);$service->assign('sample','draft','category',[$child['id']]);
check(count($service->browse('site','site','category',$child['id']))===1,'draft filtered');
check(count($service->forItem('sample','public','category'))===1,'assigned category');
denied(fn()=>$service->forItem('sample','draft','category'),'draft terms hidden');
check(count($service->forItem('sample','draft','category',true))===1,'author preview');
denied(fn()=>$service->assign('sample','private','category',[$child['id']]),'cross scope');
$service->tag('sample','public',['Bird','BIRD','Birdsong']);check(count($service->forItem('sample','public','tag'))===2,'tag deduplication');
$before=count($store->terms);denied(fn()=>$service->tag('sample','public',['Transient','<bad>']),'bad term');check(count($store->terms)===$before,'atomic failed tag batch');
denied(fn()=>$service->deleteTerm('site','site','category',$root['id']),'parent deletion');
denied(fn()=>$service->deleteTerm('site','site','category',$child['id']),'assigned deletion');
$user->admin=false;denied(fn()=>$service->saveTerm('site','site','category','Forbidden'),'editor not category manager');
$service->tag('sample','public',['A new tag']);check(count($service->forItem('sample','public','tag'))===1,'editor tag creation');
$provider->records['public']['edit']=false;denied(fn()=>$service->tag('sample','public',['Denied']),'readonly denied');
$provider->records['public']['read']=false;check($service->browse('site','site','category',$child['id'])===[],'permission revocation immediate');
$service2=new classificationservice();$service2->init();check($service2->browse('site','site','category',$child['id'])===[],'missing provider fail closed');
$user->logged=false;denied(fn()=>$service->managementTerms('site','site','category'),'anonymous vocabulary hidden');
echo "PASS: hierarchy, stable identity, duplicates, atomic batches, scopes, permissions and private browsing\n";
