<?php
/** Real ShortURL repository against disposable InnoDB temporary tables. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_DB_BASELINE') !== '1') exit(64);
require_once '/modules/shorturl/classes/dbmap_class_inc.php';
class ShorturlFailureFixture extends dbmap {
 public bool $failDelete=false;
 public function delete($key,$value,$table='') {
  if($this->failDelete)return false;
  return parent::delete($key,$value,$table);
 }
}
$table->executePrepared('CREATE TEMPORARY TABLE tbl_shorturl_map (id VARCHAR(32) PRIMARY KEY,match_url VARCHAR(255),target_url VARCHAR(255),ordering INT,is_dynamic INT,key_id VARCHAR(255),datestamp DATETIME) ENGINE=InnoDB');
$table->executePrepared('CREATE TEMPORARY TABLE tbl_shorturl_key (id VARCHAR(32) PRIMARY KEY,map_id VARCHAR(32),key_nr INT,tbl_name VARCHAR(255),tbl_field VARCHAR(255)) ENGINE=InnoDB');
try {
 $repo=new ShorturlFailureFixture();injectCallerTable($repo,'tbl_shorturl_map');
 $input=['id'=>'','txtMatchUrl'=>"bird's-path",'txtTargetUrl'=>'https://example.test/birds','ordering'=>'0'];
 $saved=$repo->saveMapping($input);$id=$saved['id'];
 ensureBaseline($repo->getTarget("bird's-path")==='https://example.test/birds','Bound redirect read supports quoted paths');
 ensureBaseline($repo->getTarget("BIRD'S-PATH")===null,'Redirect paths remain case-sensitive');
 ensureBaseline($repo->getTarget("' OR 1=1 --")===null && $repo->findMapping("' OR 1=1 --")===null,'Injected reads cannot broaden selection');
 try{$repo->saveMapping($input);throw new LogicException('Duplicate accepted');}catch(DomainException $e){ensureBaseline($e->getMessage()==='duplicate','Duplicate rejected');}
 $input['id']=$id;$input['revision']=$saved['revision'];$input['txtTargetUrl']='https://example.test/new';
 $changed=$repo->saveMapping($input);
 try{$repo->saveMapping($input);throw new LogicException('Stale edit accepted');}catch(DomainException $e){ensureBaseline($e->getMessage()==='stale','Stale edit rejected');}
 try{$repo->deleteMapping($id,$saved['revision']);throw new LogicException('Stale delete accepted');}catch(DomainException $e){}
 foreach(['javascript:alert(1)',"https://example.test/\r\nX:evil",'https://user:pass@example.test/'] as $url){
  try{$repo->saveMapping(array_replace($input,['revision'=>$changed['revision'],'txtTargetUrl'=>$url]));throw new LogicException('Unsafe redirect accepted');}catch(DomainException $e){}
 }
 $table->executePrepared('INSERT INTO tbl_shorturl_key VALUES (?,?,?,?,?)',['key-one',$id,1,'legacy','field']);
 $repo->failDelete=true;
 try{$repo->deleteMapping($id,$changed['revision']);throw new LogicException('Failed delete accepted');}catch(RuntimeException $e){}
 ensureBaseline($repo->findMapping($id)!==null && count($table->selectPrepared('SELECT id FROM tbl_shorturl_key WHERE map_id = ?',[$id]))===1,'Failed mapping deletion rolls back child deletion');
 $repo->failDelete=false;$repo->deleteMapping($id,$changed['revision']);
 ensureBaseline($repo->findMapping($id)===null && $table->selectPrepared('SELECT id FROM tbl_shorturl_key WHERE map_id = ?',[$id])===[],'Mapping and owned keys removed');
 $table->executePrepared('INSERT INTO tbl_shorturl_map VALUES (?,?,?,?,?,?,?)',['dynamic','old','https://example.test/',0,1,'','2026-01-01 00:00:00']);
 $row=$repo->findMapping('dynamic');
 ensureBaseline($repo->getTarget('old')===null,'Dynamic mapping never executes');
 try{$repo->saveMapping(array_replace($input,['id'=>'dynamic','revision'=>$repo->revision($row)]));throw new LogicException('Dynamic row modified');}catch(DomainException $e){ensureBaseline($e->getMessage()==='dynamic','Dynamic mapping preserved');}
 ensureBaseline($repo->findMapping('dynamic')===$row,'Dynamic row unchanged');
 echo "PASS ShortURL: bound reads, safe redirects, duplicate/stale protection, transactional rollback and legacy preservation\n";
} finally {
 $table->executePrepared('DROP TEMPORARY TABLE IF EXISTS tbl_shorturl_key');
 $table->executePrepared('DROP TEMPORARY TABLE IF EXISTS tbl_shorturl_map');
}
