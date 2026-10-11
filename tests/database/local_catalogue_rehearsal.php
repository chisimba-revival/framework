<?php
/** Explicit local-only scoped catalogue update and data-preservation check. */
if(PHP_SAPI!=='cli'||getenv('CHISIMBA_LOCAL_CATALOGUE')!=='1')exit(64);
chdir('/var/www/html/ch');
$config=simplexml_load_file('config/config.xml');
if(parse_url((string)$config->KEWL_SITE_ROOT,PHP_URL_HOST)!=='chisimba.test')throw new RuntimeException('Local runtime required');
$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';$engine=new engine();
$store=$engine->getObject('dbkanbanboards','kanban');
$protected=['tbl_kanban_boards','tbl_kanban_tasks','tbl_kanban_subtasks','tbl_kanban_access','tbl_knowledgemap_maps','tbl_knowledgemap_nodes','tbl_knowledgemap_relationships','tbl_knowledgemap_access','tbl_questionworkshop_sets','tbl_mcqgenerator_exams','tbl_users'];
$before=[];foreach($protected as $name)$before[$name]=$store->selectExactPrepared('CHECKSUM TABLE '.$name);
$file=$engine->getObject('modulefile','modulecatalogue');$admin=$engine->getObject('modulesadmin','modulecatalogue');
foreach(['kanban','knowledgemap','questiongenerator','toolbar','shop'] as $module){
 $data=$file->readRegisterFile($file->findRegisterFile($module));
 if(!$admin->installModule($data,true))throw new RuntimeException('Catalogue update failed: '.$module);
 if(in_array($module,['questiongenerator','shop'],true))$engine->getPatchObject($module)->postinstall();
 echo 'PASS: scoped catalogue update '.$module."\n";
}
foreach($protected as $name)if($before[$name]!==$store->selectExactPrepared('CHECKSUM TABLE '.$name))throw new RuntimeException('Protected data changed: '.$name);
echo "PASS: all protected board/map/exam/question/account table checksums unchanged\n";
