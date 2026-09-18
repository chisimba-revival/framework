<?php
if(PHP_SAPI!=='cli')exit;
if($argc===1){
 foreach(['valid'=>200,'cross-site'=>403,'wrong-user'=>403,'array'=>403,'get'=>403,'anonymous'=>403] as $case=>$status){
  $result=json_decode(shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($case)),true);
  if(!$result||$result['status']!==$status||isset($result['token'])!==($case==='valid'))throw new RuntimeException($case);
  echo "PASS: logout renewal $case\n";
 }
 exit;
}
set_error_handler(function($level,$message){throw new RuntimeException($message);});
$GLOBALS['kewl_entry_point_run']=true;$case=$argv[1];
$_SERVER['REQUEST_METHOD']=$case==='get'?'GET':'POST';
$_SERVER['HTTP_X_CHISIMBA_FORM']='security';$_SERVER['HTTP_SEC_FETCH_SITE']=$case==='cross-site'?'cross-site':'same-origin';
class controller {
 public function getParam($key,$default=''){return $key==='actor'?($GLOBALS['case']==='array'?[]:($GLOBALS['case']==='wrong-user'?'other':'user')):$default;}
 public function setVar(...$args){}public function setLayoutTemplate(...$args){}
 public function getObject(...$args){return new class {public function build(){return ['sessions'=>new class {public function getUserId(){return $GLOBALS['case']==='anonymous'?null:'user';}},'csrf'=>new class {public function issueForSession($context){return 'new-token';}}];}};}
}
require __DIR__.'/../../app/core_modules/security/controller.php';
ob_start(function($out){$data=json_decode($out,true);$data['status']=http_response_code();return json_encode($data);});
(new security())->dispatch('formtoken');
