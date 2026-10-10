<?php
/** Actual installer configuration step in a disposable root; no database creation. */
if (PHP_SAPI !== 'cli') { exit(2); }
$app=realpath($argv[1] ?? dirname(__DIR__,2).'/app');
define('INSTALL_DIR',$app.'/installer');
require INSTALL_DIR.'/steps/createconfigs.inc';
set_error_handler(static function($severity,$message) { throw new ErrorException($message,0,$severity); });
$base=sys_get_temp_dir().'/chisimba-installer-config-'.bin2hex(random_bytes(8)); mkdir($base,0700);
$count=0;
function check($ok,$label) { global $count; if (!$ok) { throw new RuntimeException($label); } $count++; }
try {
    $_POST=array(); $_SESSION=array_fill_keys(array('proxy','defaultskin','postLogin','SysType','module_path','module_URI','pear_path','contentPath','relContentPath','serverName','main_server','serverLocation','log_enable','debug_enable','root_email','tech_email','log_path'),'fixture');
    $_SESSION+=array('sys_root'=>$base,'sys_owner'=>'Café 日本語 & "Owner"','sys_name'=>'Fixture site','site_url'=>'/ch/','site_server_url'=>'https://example.invalid:8445','dsn'=>"mysqli://fixture:p'ass\\word@localhost/fixture",'install_type'=>'update','session_type'=>'update','create_db'=>false);
    $step=new CreateConfigsAction();
    check($step->isRequired(),'new root requires configuration');
    check($step->processAction() && $step->success && !$step->errors,'actual configuration step succeeds');
    check(!$step->isRequired(),'published configuration recognised');
    $path=$base.'/config/config.xml';
    $doc=new ChisimbaConfigurationDocument($path,'Settings');
    $values=$doc->root->toArray()['root']['Settings'];
    check($values['KEWL_SITE_ROOT']==='https://example.invalid:8445/ch/','canonical handoff URL');
    check($values['KEWL_INSTITUTION_NAME']===$_SESSION['sys_owner'],'installer Unicode roundtrip');
    check((fileperms($path)&0777)===0600 && (fileperms($base.'/config/dbdetails_inc.php')&0777)===0600,'private new files');
    require $base.'/config/dbdetails_inc.php';
    check(KEWL_DB_DSN===$_SESSION['dsn'],'credential literal is safely quoted');
    $_SESSION['sys_name']='Updated fixture';
    check((new CreateConfigsAction())->processAction(),'configuration update succeeds');
    check((new ChisimbaConfigurationDocument($path,'Settings'))->root->toArray()['root']['Settings']['KEWL_SITENAME']==='Updated fixture','updated config handoff');
    $before=file_get_contents($path); unlink($path); symlink($base.'/config/dbdetails_inc.php',$path);
    $credential=file_get_contents($base.'/config/dbdetails_inc.php');
    $failed=new CreateConfigsAction(); $_POST['ignore_errors']='1';
    check(!$failed->processAction() && !$failed->success && $failed->errors,'unsafe config cannot be ignored');
    check(is_link($path) && file_get_contents($path)===$credential,'failure preserves linked target');
    unlink($path); file_put_contents($path,$before);
    unset($_SESSION['dsn']); $failed=new CreateConfigsAction();
    check(!$failed->processAction() && file_get_contents($path)===$before,'missing DSN cannot report success');
    check(!class_exists('Config',false) && !class_exists('Config_Container',false),'installer no PEAR Config');
    echo "PASS: $count actual installer configuration/handoff checks (no database installation)\n";
} finally {
    restore_error_handler();
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach($it as $entry) { if($entry->isDir()&&!$entry->isLink()) rmdir($entry->getPathname()); else unlink($entry->getPathname()); } rmdir($base);
}
