<?php
error_reporting(E_ALL);
set_error_handler(static function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
$app=$argv[1]??dirname(__DIR__,2).'/app';set_include_path($app.'/lib/pear'.PATH_SEPARATOR.get_include_path());
require_once 'Translation2.php';require_once 'Translation2/Decorator/UTF8.php';
class EncodingSource {
 public $storage=null,$currentPageID=null,$lang=null,$value;
 public function get(...$args){return $this->value;}
 public function getPage(...$args){return $this->value;}
}
$source=new EncodingSource();$decorator=new Translation2_Decorator_UTF8($source);
$cases=[['ASCII','ASCII'],['café',"caf\xe9"],['中文','??'],['😀','?'],["\xed\xa0\x80",'?'],["\xe0\x80\x80",'?'],["\xf4\x90\x80\x80",'?'],["\xe2\x82",'?'],["\xe2(\xa1",'?(?'],["\xffA",'?A'],['',''],['0','0'],[null,null],[hex2bin('c5c7f5'),'??'],[hex2bin('c2ff'),'?']];
// Cover every Latin-1 code point, not just a handful of accented characters.
for($i=0;$i<256;$i++)$cases[]=[mb_convert_encoding(chr($i),'UTF-8','ISO-8859-1'),chr($i)];
foreach($cases as [$input,$expected]){
 $source->value=$input;if($decorator->get('test')!==$expected)throw new RuntimeException('Single encoding mismatch: '.bin2hex((string)$input));
 $source->value=['test'=>$input];if($decorator->getPage()['test']!==$expected)throw new RuntimeException('Page encoding mismatch');
}
$error=new PEAR_Error('test');$source->value=$error;
if($decorator->get('test')!==$error || $decorator->getPage()!==$error)throw new RuntimeException('Error identity lost');
// Differential oracle while the deprecated builtin still exists. Only its known
// deprecation is handled; the implementation under test always runs strictly.
if(function_exists('utf8_decode')){
 $samples=[];for($pair=0;$pair<65536;$pair++)$samples[]=pack('n',$pair);
 mt_srand(14092026);
 for($i=0;$i<5000;$i++){
  $input='';for($j=0;$j<mt_rand(1,20);$j++)$input.=chr(mt_rand(0,255));
  $samples[]=$input;
 }
 foreach($samples as $input){
  set_error_handler(static function($n,$m){if($n===E_DEPRECATED && str_contains($m,'utf8_decode'))return true;throw new RuntimeException($m);});
  try{$expected=utf8_decode($input);}finally{restore_error_handler();}
  $source->value=$input;if($decorator->get('test')!==$expected)throw new RuntimeException('Oracle mismatch: '.bin2hex($input));
 }
}
echo "PASS: Latin-1 byte fidelity, non-Latin substitution, malformed UTF-8, empty values, pages, errors and 70,536 differential cases\n";
