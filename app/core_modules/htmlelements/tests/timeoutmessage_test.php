<?php
error_reporting(E_ALL);
set_error_handler(static function($n,$s){throw new RuntimeException($s);});
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    function getObject($name,$module) { return new class {function languageText($key){return 'Dismiss';}}; }
    function appendArrayVar($name,$value) { throw new RuntimeException('Unexpected global timer'); }
}
require dirname(__DIR__).'/classes/timeoutmessage_class_inc.php';
$output=[];
foreach ([0,10,-1] as $delay) {
 $m=new timeoutMessage();$m->init();$m->cssId='notice"</script>';$m->htmlTag='div';$m->setTimeout($delay);$m->message='<strong>Saved</strong>';
 if($delay===10)$m->setHideTypeToHidden();
 $html=$m->show();
 if(str_contains($html,'jQuery') || str_contains($html,'id="notice"') || !str_contains($html,'<strong>Saved</strong>'))throw new RuntimeException('Unsafe or broken rendering');
 $output[]=$html;
}
if(in_array('--render',$argv,true))echo '<!doctype html><meta charset="utf-8"><title>Native notices test</title><h1>Native notices</h1>'.implode('<hr>',$output);
else echo "PASS native timeout rendering, escaped attributes, preserved rich content and no global timer\n";
