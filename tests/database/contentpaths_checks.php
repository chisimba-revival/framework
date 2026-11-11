<?php
/** Permanent content path writes share the owner's transaction and namespace. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_DB_BASELINE') !== '1') exit(64);
require_once '/modules/shorturl/classes/contentpathstore_class_inc.php';
class ContentPathFailureFixture extends contentpathstore {
 public bool $failInsert=false;
 public function insert($data,$table=''){return $this->failInsert?false:parent::insert($data,$table);}
}
$table->executePrepared('CREATE TEMPORARY TABLE tbl_shorturl_content (id VARCHAR(32) PRIMARY KEY, slug VARCHAR(190) NOT NULL UNIQUE, module_id VARCHAR(32), content_id VARCHAR(32), canonical INT NOT NULL) ENGINE=InnoDB');
$table->executePrepared('CREATE TEMPORARY TABLE tbl_shorturl_map (id VARCHAR(32) PRIMARY KEY,match_url VARCHAR(255),target_url VARCHAR(255),ordering INT,is_dynamic INT,key_id VARCHAR(255),datestamp DATETIME) ENGINE=InnoDB');
try {
 $paths=new ContentPathFailureFixture();injectCallerTable($paths,'tbl_shorturl_content');
 $paths->locked(fn()=> $paths->claim('simpleblog','one','bird-story'));
 $paths->locked(fn()=> $paths->claim('simpleblog','one','new-story'));
 ensureBaseline($paths->owner('simpleblog','one')['slug']==='new-story' && (int)$paths->path('bird-story')['canonical']===0,'Rename retains alias and one canonical path');
 try{$paths->locked(fn()=> $paths->claim('sitepages','two','bird-story'));throw new LogicException('Alias stolen');}catch(DomainException $e){}
 $paths->failInsert=true;
 try{$paths->locked(fn()=> $paths->claim('simpleblog','one','failing-story'));throw new LogicException('Failure accepted');}catch(RuntimeException $e){}
 $paths->failInsert=false;
 ensureBaseline($paths->owner('simpleblog','one')['slug']==='new-story','Failed insert restores canonical path');
 $table->beginTransaction();
 $table->insert(['id'=>'content-rollback','body'=>'synthetic content']);
 $paths->locked(fn()=> $paths->claim('simpleblog','one','outer-rollback'));
 $table->rollbackTransaction();
 ensureBaseline(!$table->getRow('id','content-rollback') && !$paths->path('outer-rollback') && $paths->owner('simpleblog','one')['slug']==='new-story','Content transaction rolls back path changes');
 $paths->locked(fn()=> $paths->retainPageAlias('page','printed-qr'));
 ensureBaseline(!$paths->owner('sitepages','page') && $paths->path('printed-qr')['content_id']==='page','Legacy page alias does not become canonical');
 $table->executePrepared('INSERT INTO tbl_shorturl_map (id,match_url) VALUES (?,?)',['qr','Printed-QR/']);
 ensureBaseline($paths->legacyPathExists('printed-qr'),'QR paths reserve case and trailing-slash variants');
 require_once '/modules/shorturl/classes/contentpaths_class_inc.php';
 require_once '/modules/sitepages/classes/dbsitepages_class_inc.php';
 $table->executePrepared('CREATE TEMPORARY TABLE tbl_sitepages (id VARCHAR(32) PRIMARY KEY,slug VARCHAR(190) UNIQUE,title VARCHAR(250),status VARCHAR(20),body_html TEXT,composition_json TEXT,creatorid VARCHAR(32),modifierid VARCHAR(32),datecreated DATETIME,datemodified DATETIME) ENGINE=InnoDB');
 $pages=new dbsitepages();injectCallerTable($pages,'tbl_sitepages');$service=new contentpaths();
 $GLOBALS['pathObjects']=['contentpaths'=>$service,'contentpathstore'=>$paths,'dbsitepages'=>$pages,
  'dbsysconfig'=>new class{function getValue($name,...$args){return $name==='content_paths_enabled'?'TRUE':'';}},
  'modules'=>new class{function checkIfRegistered($name){return $name==='sitepages';}}];
 $page=$pages->savePage(['title'=>'Page title','slug'=>'','body_html'=>'Original','status'=>'published'],'fixture');
 ensureBaseline($page['slug']==='page-title' && $paths->owner('sitepages',$page['id'])['slug']==='page-title','Page save generates its address atomically');
 $changed=$pages->savePage(['title'=>'Changed title','slug'=>'chosen-name','body_html'=>'Updated','status'=>'published'],'fixture',$page['id']);
 ensureBaseline($pages->findBySlug('page-title')['id']===$page['id'],'Old query-by-slug survives rename');
 try{$pages->savePage(['title'=>'Rejected','slug'=>'bird-story','body_html'=>'Must not persist','status'=>'published'],'fixture',$page['id']);throw new LogicException('Collision accepted');}catch(DomainException $e){}
 ensureBaseline($pages->find($page['id'])===$changed,'Collision rolls back page and canonical path together');
 $pages->insert(['id'=>'legacy-page','title'=>'QR page','slug'=>'printed-qr','body_html'=>'Legacy','status'=>'published']);
 // Remove only the earlier synthetic alias, then test real backfill retention.
 $paths->delete('slug','printed-qr');
 $before=$paths->selectPrepared('SELECT * FROM tbl_shorturl_content ORDER BY id');$beforePage=$pages->find('legacy-page');
 $plan=$service->backfill(false);
 ensureBaseline($before===$paths->selectPrepared('SELECT * FROM tbl_shorturl_content ORDER BY id'),'Backfill dry run rolls registry back');
 ensureBaseline($plan===$service->backfill(true) && $service->backfill(true)===[],'Backfill matches plan and is idempotent');
 ensureBaseline($pages->find('legacy-page')===$beforePage && $paths->owner('sitepages','legacy-page')['slug']==='qr-page','Backfill preserves page data and QR mapping');
 $changed=$pages->savePage(['title'=>'QR page renamed','slug'=>'new-qr-page','body_html'=>'Edited','status'=>'published'],'fixture','legacy-page');
 ensureBaseline($pages->findBySlug('printed-qr')['id']==='legacy-page','Legacy QR query alias retained after edit');
 $pages->archive('legacy-page','fixture');
 ensureBaseline(!$pages->findBySlug('printed-qr',true),'Alias does not expose archived page');
 echo "PASS content paths: unique ownership, aliases, savepoint failure, outer rollback, Pages saves, alias visibility, QR preservation and idempotent dry-run backfill\n";
} finally {
 $table->executePrepared('DROP TEMPORARY TABLE IF EXISTS tbl_sitepages');
 $table->executePrepared('DROP TEMPORARY TABLE IF EXISTS tbl_shorturl_content');
 $table->executePrepared('DROP TEMPORARY TABLE IF EXISTS tbl_shorturl_map');
}
