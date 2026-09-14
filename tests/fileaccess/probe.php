<?php
/** Controller-boundary probes: actual controllers, isolated fixture dependencies. */
$GLOBALS['kewl_entry_point_run']=true;
class controller {
 public $objUser,$objConfig,$objAssignmentSubmit,$objAssignment,$params=[],$services=[];
 function getParam($name,$default=null){return $this->params[$name]??$default;}
 function getObject($name,$module=null){return $this->services[$name];}
 function nextAction($action,$params=[]){return $params['error']??'redirect';}
}
$root=dirname(__DIR__,3);
require $root.'/framework/app/core_modules/filemanager/controller.php';
require $root.'/modules/assignment/controller.php';
$diagnostics=[];
set_error_handler(function($n,$s)use(&$diagnostics){$diagnostics[]=$s;return true;});
class FixtureUser {
 public $id,$admin;
 function __construct($id,$admin=false){$this->id=$id;$this->admin=$admin;}
 function isLoggedIn(){return $this->id!==null;}
 function userId(){return $this->id;}
 function isCourseAdmin($context){return $this->admin;}
}
$results=[];
foreach(['anonymous'=>[null,false],'outsider'=>['outsider',false],'other_student'=>['peer',false],'submitter'=>['student',false],'instructor'=>['teacher',true],'administrator'=>['admin',true]] as $role=>$identity){
 $f=(new ReflectionClass('filemanager'))->newInstanceWithoutConstructor();$f->objUser=new FixtureUser(...$identity);$f->contextCode='course-a';$f->params=['id'=>'fixture','filename'=>'test.txt'];
 $f->objFiles=new class {function getFileInfo($id){return ['id'=>'fixture','filefolder'=>'context/course-a','path'=>'context/course-a/test.txt','filename'=>'test.txt','access'=>'private_all'];}};
 $f->objFolders=new class {function getFolderId($path){return 'folder';}function getFolder($id){return ['folderpath'=>'context/course-a','access'=>'private_all'];}};
 $f->services['filereadpolicy']=new class($role) {function __construct(public $role){} function mayRead($f){return !in_array($this->role,['anonymous','outsider']);}};
 $f->services['folderaccess']=new class {function isFileAccessPrivate($row){return true;}function downloadFile($path,$name){return 'BYTES_REACHED';}};
 $m=new ReflectionMethod('filemanager','__file');$got=$m->invoke($f);$expected=!in_array($role,['anonymous','outsider']);
 $results[]=['boundary'=>'restricted_course_file','role'=>$role,'allowed'=>$got==='BYTES_REACHED','expected'=>$expected];
 $f->services['filereadpolicy']=new class {function mayRead($f){return true;}};
 $f->services['folderaccess']=new class {function isFileAccessPrivate($row){return false;}function isFileVisibilityPrivate($row){return false;}};
 $f->objConfig=new class {function getcontentPath(){throw new RuntimeException('PUBLIC_FILE_REACHED');}};
 $publicAllowed=false;try{$m->invoke($f);}catch(RuntimeException $e){if($e->getMessage()!=='PUBLIC_FILE_REACHED')throw $e;$publicAllowed=true;}
 $results[]=['boundary'=>'public_file','role'=>$role,'allowed'=>$publicAllowed,'expected'=>true];
 $a=(new ReflectionClass('assignment'))->newInstanceWithoutConstructor();$a->objUser=new FixtureUser(...$identity);$a->contextCode='course-a';$a->params=['id'=>'submission','fileid'=>'file'];
 $a->objAssignment=new class {function getAssignment($id){return ['context'=>'course-a','filename_conversion'=>'0'];}};
 $a->objAssignmentSubmit=new class {function getSubmission($id){return ['id'=>'submission','assignmentid'=>'assignment','userid'=>'student','studentfileid'=>'file'];}function getAssignmentFilename($s,$f){throw new RuntimeException('FILE_RESOLUTION_REACHED');}};
 $allowed=false;try{$a->__downloadfile();}catch(RuntimeException $e){if($e->getMessage()!=='FILE_RESOLUTION_REACHED')throw $e;$allowed=true;}
 $results[]=['boundary'=>'assignment_controller','role'=>$role,'allowed'=>$allowed,'expected'=>in_array($role,['submitter','instructor','administrator'])];
 $a->params['fileid']='another-submission-file';
 $got=$a->__downloadfile();
 $results[]=['boundary'=>'assignment_wrong_attachment','role'=>$role,'allowed'=>$got!=='nopermission','expected'=>false];

}
restore_error_handler();
echo json_encode(['results'=>$results,'diagnostics'=>array_values(array_unique($diagnostics))],JSON_PRETTY_PRINT)."\n";

foreach($results as $row){if($row['allowed']!==$row['expected'])throw new RuntimeException(json_encode($row));}
if($diagnostics)throw new RuntimeException(implode("; ",$diagnostics));
