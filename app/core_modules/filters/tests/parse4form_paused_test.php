<?php
if (PHP_SAPI !== 'cli') exit(64);
error_reporting(E_ALL);
set_error_handler(static function($n,$s){throw new RuntimeException($s);});
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 public function getObject($name,$module){throw new RuntimeException('Paused filter loaded a service');}
 public function getParam($name,$default=null){throw new RuntimeException('Paused filter inspected request data');}
}
require __DIR__.'/../classes/parse4form_class_inc.php';
$filter=new parse4form(); $filter->init(); $filter->setUpPage();
$_GET['form_msg']='<script>bad()</script>'; $_SERVER['QUERY_STRING']="bad='>";
foreach(['Plain text','Before [FORM]old-id[/FORM] after','[FORM: id=old]','[FORMS]old[/FORMS]','[FORMBUILDER]keep[/FORMBUILDER]','<p>Ordinary HTML</p>'] as $text) {
 ob_start(); $actual=$filter->parse($text); $output=ob_get_clean();
 if($actual!==$text || $output!=='')throw new RuntimeException('Paused filter altered or emitted content');
}
echo "PASS paused Forms: no services or request input, no output, original and unrelated content preserved\n";
