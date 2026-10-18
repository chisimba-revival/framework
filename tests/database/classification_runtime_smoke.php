<?php
/** Installed composition/read check only: no fixture writes or changed permissions. */
if(PHP_SAPI!=='cli'||getenv('CHISIMBA_CLASSIFICATION_SMOKE')!=='1')exit(64);
chdir($argv[1]);$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';$engine=new engine();
$store=$engine->getObject('classificationstore','classification');
$service=$engine->getObject('classificationservice','classification');
$id=bin2hex(random_bytes(16));
if($store->vocabulary($id)!==null||$store->terms($id)!==[]||$store->itemTerms($id,'fixture','absent')!==[])throw new RuntimeException('Classification absent-scope read failed');
echo "PASS: installed classification composition and absent-scope reads through dbTable\n";
