<?php
/** Usage: php backfill.php /absolute/installed/app [--apply]. Defaults to rollback. */
if(PHP_SAPI!=='cli')exit(64);
$root=$argv[1]??'';$apply=($argv[2]??'')==='--apply';
if(!str_starts_with($root,'/')||!is_file($root.'/config/dbdetails_inc.php')||!is_file($root.'/classes/core/engine_class_inc.php')||count($argv)>3||(isset($argv[2])&&!$apply))throw new InvalidArgumentException('Usage: php backfill.php /absolute/installed/app [--apply]');
chdir($root);$GLOBALS['kewl_entry_point_run']=true;
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';$engine=new engine();
$paths=$engine->getObject('contentpaths','shorturl');
echo json_encode(['applied'=>$apply,'paths'=>$paths->backfill($apply)],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
