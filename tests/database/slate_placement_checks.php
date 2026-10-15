<?php
/** Real shared placement service, isolated Slate rows only. */
require_once '/modules/slate/classes/dbslateblocks_class_inc.php';
require_once $app.'/core_modules/blocks/classes/blockplacementorder_class_inc.php';
class SlateBlocksFixture extends dbslateblocks {
 public $pages; public int $failAfter=0,$writes=0;
 public function getObject($name,$module=null){
  if($name==='dbslatepages')return $this->pages;
  if($name==='blockplacementorder')return new blockplacementorder();
  if($name==='user')return new class {function userId(){return 'fixture';}};
  throw new RuntimeException('Unexpected dependency');
 }
 public function update($key,$id,$values,$table=''){
  if($this->failAfter && ++$this->writes===$this->failAfter)return false;
  return parent::update($key,$id,$values,$table);
 }
}
$table->executePrepared('ALTER TABLE tbl_slate_pageblocks ADD block VARCHAR(255), ADD module VARCHAR(50), ADD userid VARCHAR(32), ADD datelastupdated DATETIME');
$pages->input=['page'=>'30','title'=>'Placement fixture','description'=>'Synthetic'];$pid=$pages->addPage();$key=$pages->pageKey('30');
$placements=new SlateBlocksFixture();injectCallerTable($placements,'tbl_slate_pageblocks');$placements->pages=$pages;
$old=$placements->editingRevision($key);
$next=$placements->editPlacement($key,$old,'addblock','block|text1|textblock','left','');
ensureBaseline($next!==$old,'Placement add changes revision');
try{$placements->editPlacement($key,$old,'addblock','block|text2|textblock','left','');throw new LogicException('Stale add accepted');}catch(RuntimeException $expected){ensureBaseline($expected->getMessage()==='block_stale','Stale layout rejected');}
$next=$placements->editPlacement($key,$next,'addblock','block|text2|textblock','left','');
$rows=$placements->editingRows($key);$placements->failAfter=2;
try{$placements->editPlacement($key,$next,'moveblock',$rows[0]['id'],'','down');throw new LogicException('Partial layout accepted');}catch(RuntimeException $expected){}
ensureBaseline($placements->editingRevision($key)===$next,'Shared move failure rolls back');
$placements->failAfter=0;
$next=$placements->editPlacement($key,$next,'moveblock',$rows[0]['id'],'','down');
ensureBaseline($placements->editingRows($key)[0]['id']===$rows[1]['id'],'Shared move order persisted');
$next=$placements->editPlacement($key,$next,'removeblock',$rows[0]['id'],'','');
ensureBaseline(count($placements->editingRows($key))===1,'Shared removal scoped');
$staleDelete=$pages->deleteRevision($pages->getPageById($pid)[0]);
$oldHash=md5('legacy canonical URL');$unknown=md5('unrecoverable URL');
$table->executePrepared('INSERT INTO tbl_slate_pageblocks (id,pageid,side,position) VALUES (?,?,?,?),(?,?,?,?)',['legacy',$oldHash,'left',3,'unmapped',$unknown,'left',1]);
$pages->migratePageKeys([$oldHash=>$key]);$pages->migratePageKeys([$oldHash=>$key]);
ensureBaseline(count($placements->editingRows($key))===2,'Known legacy placement migrated repeatably');
ensureBaseline(count($table->selectPrepared('SELECT id FROM tbl_slate_pageblocks WHERE pageid=?',[$unknown]))===1,'Unresolved hash preserved');
$pages->input=['revision'=>$staleDelete];
try{$pages->deletePage($pid);throw new LogicException('Stale page deletion accepted');}catch(DomainException $expected){ensureBaseline($expected->getMessage()==='editor_stale','New placements block stale page deletion');}
$pages->input=['revision'=>$pages->deleteRevision($pages->getPageById($pid)[0])];$pages->deletePage($pid);
try{$placements->editPlacement($key,$next,'addblock','block|text1|textblock','left','');throw new LogicException('Deleted page accepted');}catch(DomainException $expected){}
ensureBaseline(!$table->selectPrepared('SELECT id FROM tbl_slate_pageblocks WHERE pageid=?',[$key]),'No orphan placements after page deletion');
echo "PASS: Slate shared ordering, stale layouts, rollback, known-hash migration/repeat, unknown-hash preservation and deleted-page rejection\n";
