<?php
/** Reversible local persistence test for optional navigation labels. */
if(PHP_SAPI!=='cli'||getenv('CHISIMBA_NAV_TEST')!=='local')exit(64);
chdir($argv[1]);$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';$e=new engine();$db=$e->getObject('dbmenu','toolbar');$id='labeltest'.bin2hex(random_bytes(8));
try{$db->transaction(function()use($db,$id){
 if(!$db->saveLinks(['id'=>$id,'category'=>'site_099|||link|mod_toolbar_name|','module'=>'toolbar','adminonly'=>0,'dependscontext'=>0,'contextplugin'=>0,'menu_label'=>'News & stories']))throw new RuntimeException('Insert failed');
 if($db->getLinkById($id)['menu_label']!=='News & stories')throw new RuntimeException('Label lost');
 $db->saveLinks(['category'=>'site_098|||link|mod_toolbar_name|'],$id);
 if($db->getLinkById($id)['menu_label']!=='News & stories')throw new RuntimeException('Unrelated edit lost label');
 $db->saveLinks(['menu_label'=>null],$id);
 if($db->getLinkById($id)['menu_label']!==null)throw new RuntimeException('Default not restored');
 throw new RuntimeException('fixture rollback');
});}catch(RuntimeException $ex){if($ex->getMessage()!=='fixture rollback')throw $ex;}
if($db->getLinkById($id)!==null)throw new RuntimeException('Rollback failed');
echo "PASS: label insert, reload, unrelated edit, clear and rollback.\n";
