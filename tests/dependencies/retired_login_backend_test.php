<?php
/** Run in a subprocess per retired route because dispatch intentionally exits. */
$GLOBALS['kewl_entry_point_run']=true;
$root=dirname(__DIR__,2);
if(isset($argv[1])) {
 class controller {
  function getParam($key,$default=null){return $key==='action'?$GLOBALS['argv'][1]:$default;}
  function getObject(...$args){throw new RuntimeException('Retired route loaded a service');}
 }
 require $root.'/app/core_modules/login/controller.php';
 $_SESSION=['sentinel'=>'unchanged'];$_SERVER['REQUEST_METHOD']=$argv[2];
 register_shutdown_function(function(){
  if(http_response_code()!==410||$_SESSION!==['sentinel'=>'unchanged']){fwrite(STDERR,"Retired route changed state\n");exit(1);}
 });
 (new login())->dispatch();exit(1);
}
foreach(['captcha','dbnonce','loginsecurity','nonce'] as $name)
 if(is_file($root.'/app/core_modules/login/classes/'.$name.'_class_inc.php'))throw new RuntimeException('Legacy backend returned');
if(is_file($root.'/app/core_modules/login/sql/tbl_login_nonce.sql')||str_contains(file_get_contents($root.'/app/core_modules/login/register.conf'),'TABLE:tbl_login_nonce'))throw new RuntimeException('Legacy schema registration returned');
if(!is_file($root.'/app/core_modules/utilities/classes/captcha_class_inc.php'))throw new RuntimeException('Unrelated CAPTCHA removed');
foreach(['generatecaptcha','verifycaptcha'] as $action)foreach(['GET','POST'] as $method){
 $process=proc_open([PHP_BINARY,__FILE__,$action,$method],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
 $out=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
 if(proc_close($process)!==0||$out!==''||$error!=='')throw new RuntimeException('Retired route failed '.$action.' '.$method.' '.$error);
}
echo "PASS retired routes return 410 without services/session mutation; legacy files absent; Utilities CAPTCHA retained\n";
