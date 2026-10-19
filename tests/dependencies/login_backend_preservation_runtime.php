<?php
if(PHP_SAPI!=='cli')exit(64);
chdir('/var/www/html/ch');
if(!str_contains(file_get_contents('config/dbdetails_inc.php'),'@editor-db/editor_fixture'))throw new RuntimeException('Disposable clone required');
$GLOBALS['kewl_entry_point_run']=true;
$_SERVER += ['REQUEST_METHOD'=>'CLI','HTTP_HOST'=>'127.0.0.1:8088','SERVER_NAME'=>'127.0.0.1','SERVER_PORT'=>'8088','SCRIPT_NAME'=>'/ch/index.php','QUERY_STRING'=>''];
require 'classes/core/engine_class_inc.php';$engine=new engine();
set_error_handler(static function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
set_exception_handler(static function($e){fwrite(STDERR,get_class($e).': '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine()."\n");exit(1);});
$db=$engine->getObject('dbsysconfig','sysconfig');
require_once 'classes/core/chisimbadatabaseschema.php';
$schema=new ChisimbaDatabaseSchema($db);
if(!$db->selectPrepared('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',['tbl_login_nonce']))
 $schema->createTable('tbl_login_nonce',['id'=>['type'=>'text','length'=>32],'nonce'=>['type'=>'text','length'=>250]]);
if(!$db->selectPrepared('SELECT id FROM tbl_login_nonce WHERE id=?',['retirement-sentinel']))$db->executePrepared('INSERT INTO tbl_login_nonce (id,nonce) VALUES (?,?)',['retirement-sentinel','synthetic-preserve-value']);
$before=$db->selectPrepared('SELECT * FROM tbl_login_nonce ORDER BY id');
$files=$engine->getObject('modulefile','modulecatalogue');$catalogue=$engine->getObject('modules','modulecatalogue');$admin=$engine->getObject('modulesadmin','modulecatalogue');
$data=$files->readRegisterFile($files->findRegisterFile('login'));
foreach([0,1] as $attempt){
 $registered=$catalogue->checkIfRegistered('login');
 if(!$admin->installModule($data,$registered))throw new RuntimeException('Catalogue operation failed');
 if($db->selectPrepared('SELECT * FROM tbl_login_nonce ORDER BY id')!==$before)throw new RuntimeException('Legacy data changed');
 echo 'PASS '.($registered?'update':'fresh').' compatibility registration preserves existing nonce table and rows'."\n";
}
