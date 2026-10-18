<?php
error_reporting(E_ALL);
set_error_handler(static function($n,$s){throw new RuntimeException($s);});
class ChisimbaObject {
 public $headers=[];
 function loadClass($a,$b){}
 function appendArrayVar($a,$v){$this->headers[]=$v;}
 function getJavaScriptFile($a,$b=''){return $a;}
 function getObject($a,$b){return new class {function languageText($a,$b){return 'Close';}};}
}
class link {
 public $link,$extra='';
 function __construct(public $url){}
 function show(){return '<a href="'.htmlspecialchars($this->url,ENT_QUOTES,'UTF-8').'" '.$this->extra.'>'.$this->link.'</a>';}
}
require dirname(__DIR__).'/classes/alertbox_class_inc.php';
$a=new alertbox();$a->init();
foreach(['javascript:alert(1)','data:text/html,test',"java\nscript:alert(1)"] as $url)if($a->show('Blocked',$url)!=='Blocked')throw new RuntimeException('Unsafe scheme accepted');
foreach(['https://example.org/page','index.php?module=help&action=view','/path'] as $url){$html=$a->show('Page',$url);if(str_contains($html,'data-native-alert'))throw new RuntimeException('Remote page intercepted');}
if($a->headers!==[])throw new RuntimeException('Remote links loaded script');
$html=$a->show('Read details','#details');
if(!str_contains($html,'data-native-alert')||$a->headers!==['alertbox/native-alert.js'])throw new RuntimeException('Missing native preview');
if(in_array('--render',$argv,true))echo '<!doctype html><meta charset="utf-8"><title>Native alert fixture</title><link rel="stylesheet" href="skin.css"><h1>Native alert preview</h1>'.$html.' <a href="remote.html">Remote page</a><section id="details" hidden><h2>Preview details</h2><label>Retained draft <input value="Keep my work"></label><p>Escape or Close returns to the link.</p></section><script src="native-alert.js"></script>';
else echo "PASS URL rejection, ordinary remote navigation and native fragment rendering\n";
