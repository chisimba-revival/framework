<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public $services=[];function getObject($n,$m=null){return $this->services[$n];}}
class dbtable extends ChisimbaObject {function getRow($k,$v){return ['id'=>'submission','assignmentid'=>'a','studentfileid'=>'owned','lecturerfileid'=>'feedback'];}}
$root=dirname(__DIR__,3);
require $root.'/framework/app/core_modules/filemanager/classes/folderaccess_class_inc.php';
require $root.'/modules/assignment/classes/dbassignmentsubmit_class_inc.php';
set_error_handler(function($n,$s){throw new RuntimeException($s);});
$d=new folderaccess();$dir=sys_get_temp_dir().'/chisimba-guard-'.bin2hex(random_bytes(6));mkdir($dir);file_put_contents($dir.'/file.txt','fixture bytes');
(new ReflectionProperty(folderaccess::class,'secureFolder'))->setValue($d,$dir);
$d->services['dbfile']=new class {function getFileDetailsFromPath($p){return $p==='unregistered'?false:['filename'=>'file.txt','path'=>$p];}};
$d->services['filereadpolicy']=new class {public $allowed=false;function mayRead($r){return $this->allowed;}};
$count=0;
function expect($v,$got){global $count;$count++;if($v!==$got)throw new RuntimeException('Unexpected result: '.var_export($got,true));}
try {
 expect('filenotavailable_tpl.php',$d->downloadFile('file.txt','file.txt'));
 $d->services['filereadpolicy']->allowed=true;
 expect('filenotavailable_tpl.php',$d->downloadFile('unregistered','file.txt'));
 expect('filenotavailable_tpl.php',$d->downloadFile('file.txt','wrong.txt'));
 expect('filenotavailable_tpl.php',$d->downloadFile('../file.txt','file.txt'));
 symlink('/etc/hosts',$dir.'/outside');expect('filenotavailable_tpl.php',$d->downloadFile('outside','file.txt'));
 ob_start();$d->downloadFile('file.txt','file.txt');$bytes=ob_get_clean();expect('fixture bytes',$bytes);
 $submission=new dbassignmentsubmit();expect(false,$submission->getAssignmentFilename('submission','foreign'));expect(false,$submission->getAssignmentFilename('submission',''));
} finally {unlink($dir.'/file.txt');unlink($dir.'/outside');rmdir($dir);}
echo "$count download and attachment assertions passed\n";
