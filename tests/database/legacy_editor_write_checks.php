<?php
/** Runs inside the disposable table fixture in legacy_editor_read_checks.php. */
require_once $app.'/core_modules/utilities/classes/richtextsanitizer_class_inc.php';
class TextblockWriteFixture extends dbtextblock {
    public array $input=[];
    public function getParam($key,$default=null){return $this->input[$key] ?? $default;}
    public function getObject($name,$module=null){if($name==='richtextsanitizer')return new richtextsanitizer();throw new RuntimeException('Unexpected dependency');}
}
class SlateWriteFixture extends dbslatepages {
    public array $input=[];
    public function getParam($key,$default=null){return $this->input[$key] ?? $default;}
    public function uri($params,$module){return 'index.php?module=slate&page='.$params['page'];}
}
$writer=new TextblockWriteFixture();injectCallerTable($writer,'tbl_textblock');
$writer->input=['blockid'=>'text2','title'=>"Quoted ' title",'blocktext'=>'<p onclick="evil()">Kept</p><script>evil()</script>','show_title'=>'on'];
$saved=$writer->saveRecord('add','fixture');
$row=$writer->getBlockById($saved)[0];
ensureBaseline($row['blocktext']==='<p>Kept</p>' && $row['creatorid']==='fixture','Rich text cleaned and author supplied by server');
try {$writer->saveRecord('add','fixture');throw new LogicException('Duplicate accepted');}catch(DomainException $expected){}
$writer->input['id']=$saved;$writer->input['title']='Changed';$writer->saveRecord('edit','fixture');
ensureBaseline($writer->getBlockById($saved)[0]['title']==='Changed','Textblock edit persisted');
$writer->input['blockid']='text1';
try {$writer->saveRecord('edit','fixture');throw new LogicException('Duplicate edit accepted');}catch(DomainException $expected){}
ensureBaseline($writer->getBlockById($saved)[0]['blockid']==='text2','Rejected block edit preserved original');
$pages=new SlateWriteFixture();injectCallerTable($pages,'tbl_slate_pages');
$pages->input=['page'=>'10','title'=>'New page','description'=>'Description'];$page=$pages->addPage();
try {$pages->addPage();throw new LogicException('Duplicate page accepted');}catch(DomainException $expected){}
$pages->input['id']=$page;$pages->input['title']='Changed page';$pages->savePage();
ensureBaseline($pages->getPageById($page)[0]['title']==='Changed page','Slate save persisted');
$pages->input['page']='11';
try {$pages->savePage();throw new LogicException('Page identity changed');}catch(DomainException $expected){}
ensureBaseline($pages->getPageById($page)[0]['page']==='10','Page identity preserved');
$table->executePrepared('CREATE TEMPORARY TABLE tbl_slate_pageblocks (id VARCHAR(64),pageid VARCHAR(32),side VARCHAR(6),position INT)');
try {
 $hash=md5($pages->uri(['page'=>'10'],'slate'));
 $table->executePrepared('INSERT INTO tbl_slate_pageblocks (id,pageid) VALUES (?,?),(?,?)',['owned',$hash,'other','other-page']);
 ensureBaseline($pages->deletePage($page),'Slate delete succeeds');
 ensureBaseline($pages->getPageById($page)===[] && $table->selectPrepared('SELECT id FROM tbl_slate_pageblocks')===[['id'=>'other']],'Delete removes only this page placements');
 ensureBaseline(!$pages->deletePage($page),'Missing page delete handled');
 require_once $app.'/core_modules/canvas/classes/dbpageblocks_class_inc.php';
 class PageBlocksFixture extends dbpageblocks {
  public string $page='page-a'; public int $failAfter=0, $writes=0;
  public function getParam($name,$default=null){return $name==='pageId'?$this->page:$default;}
  public function update($key,$id,$values,$table=''){
   if ($this->failAfter && ++$this->writes === $this->failAfter) return false;
   return parent::update($key,$id,$values,$table);
  }
 }
 $blocks=new PageBlocksFixture();injectCallerTable($blocks,'tbl_slate_pageblocks');
 $table->executePrepared('INSERT INTO tbl_slate_pageblocks VALUES (?,?,?,?),(?,?,?,?),(?,?,?,?),(?,?,?,?)',['a','page-a','left',1,'b','page-a','left',2,'c','page-a','left',3,'foreign','page-b','left',1]);
 ensureBaseline($blocks->getPageBlocksList("page-a' OR 1=1 --",'left')===[],'Page blocks bind public lookup');
 ensureBaseline(!$blocks->moveBlockDown('foreign'),'Cannot move another page block');
 ensureBaseline($blocks->moveBlockDown('a'),'Page block moved');
 ensureBaseline(array_column($blocks->getPageBlocksList('page-a','left'),'id')===['b','a','c'],'Move selects nearest neighbour');
 $blocks->failAfter=2;
 try {$blocks->moveBlockDown('a');throw new LogicException('Partial move accepted');}catch(RuntimeException $expected){}
 ensureBaseline(array_column($blocks->getPageBlocksList('page-a','left'),'id')===['b','a','c'],'Failed second write rolls back first move');
 echo "PASS: page block binding, page isolation, neighbour order and partial-write rollback\n";

} finally {$table->executePrepared('DROP TEMPORARY TABLE tbl_slate_pageblocks');}
echo "PASS: real Textblock/Slate saves, sanitisation, duplicate rejection, stable page identity and scoped delete\n";
