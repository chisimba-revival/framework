<?php
/** Behavioural regression tests for the shared read policy. */
class ChisimbaObject {}
require dirname(__DIR__,3).'/framework/app/core_modules/filemanager/classes/filereadpolicy_class_inc.php';
set_error_handler(function($n,$s){throw new RuntimeException($s);});
$count=0;
function check($expected,$actual,$name){global $count;$count++;if($expected!==$actual)throw new RuntimeException($name);}
foreach(['anonymous','outsider','student','teacher','admin'] as $role){
 $uid=$role==='anonymous'?'':$role;$member=$role==='student';$teacher=$role==='teacher';$admin=$role==='admin';
 foreach(['Public','Private'] as $access){
  $course=['access'=>$access,'status'=>'Published'];$file=['id'=>'f','userid'=>'student','access'=>'public'];$folder=['access'=>'public'];
  $allowed=filereadpolicy::allows($file,$folder,$course,$uid,$admin,$member,$teacher);
  check($access==='Public'||$member||$teacher||$admin,$allowed,"$role $access course");
  $file['visibility']='hidden';check($member||$teacher||$admin,filereadpolicy::allows($file,$folder,$course,$uid,$admin,$member,$teacher),"$role hidden $access");
  unset($file['visibility']);$file['access']='private_all';check($member||$teacher||$admin,filereadpolicy::allows($file,$folder,$course,$uid,$admin,$member,$teacher),"$role private file $access");
  $file['access']='public';$folder['access']='private_all';check($member||$teacher||$admin,filereadpolicy::allows($file,$folder,$course,$uid,$admin,$member,$teacher),"$role private folder $access");
 }
 check($admin||$member||$teacher,filereadpolicy::allows(['access'=>'public'],['access'=>'public'],['access'=>'Public','status'=>'Unpublished'],$uid,$admin,$member,$teacher),"$role unpublished");
 check($uid!=='',filereadpolicy::allows(['access'=>'private_all'],[],null,$uid,$admin,false,false),"$role personal authenticated sharing");
}
echo "$count policy assertions passed\n";
