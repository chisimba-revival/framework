<?php
error_reporting(E_ALL);
set_error_handler(static function($n,$s){throw new RuntimeException($s);});
class filemanagerobject {}
$loader=new class {function loadClass($a,$b){} function load(){require dirname(__DIR__).'/classes/selectfile_class_inc.php';}};
$loader->load();
$picker=new selectfile();
$clear=$picker->showClearInputJavaScript();
$source=file_get_contents(dirname(__DIR__).'/classes/previewfolder_class_inc.php');
preg_match_all("~\\\$str \\.= '(<script type=\"text/javascript\">.*?</script>)';~s",$source,$matches);
$scripts=[];
foreach($matches[1] as $script)if(str_contains($script,'turnOnFiles'))$scripts[]=str_replace(["\\'","\\\\"],["'","\\"],$script);
if(count($scripts)!==2 || str_contains($clear.implode('',$scripts),'jQuery'))throw new RuntimeException('Expected native controls');
echo json_encode(['clear'=>$clear,'views'=>$scripts]);
