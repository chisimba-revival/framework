<?php
/** Run normally without PEAR; --compare also checks the retained vendor oracle. */
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject {}
require __DIR__.'/../../app/core_modules/utilities/classes/xmlserial_class_inc.php';
function verify($ok, $label) { if (!$ok) throw new RuntimeException($label); }
function rejects(callable $fn) { try {$fn();} catch (InvalidArgumentException $e) {return;} throw new RuntimeException('Unsafe input accepted'); }
$service = new xmlserial();
$fixtures = [
 [['width'=>320,'title'=>'Café & birds','empty'=>'','none'=>null,'flag'=>true,'no'=>false], ['width'=>'320','title'=>'Café & birds','empty'=>'','none'=>'','flag'=>'1','no'=>'0']],
 [['items'=>['one','two'],'bad key'=>'test'], ['items'=>['XML_Serializer_Tag'=>['one','two']],'XML_Serializer_Tag'=>'test']],
 [['a'=>['b'=>'c'],'zero'=>0], ['a'=>['b'=>'c'],'zero'=>'0']],
 [[], ''], ['text','text'], [false,''], [null,''],
];
foreach ($fixtures as [$input,$expected]) verify($service->readXML($service->writeXML($input),false)===$expected, 'Metadata round trip');
$readFixtures = [
 ['<r><a> one </a><a>two</a><empty/></r>', ['a'=>['one','two'],'empty'=>'']],
 ['<r a="x"> text <b>bold</b> tail</r>', ['b'=>'bold','_content'=>'text  tail']],
 ['<r><a _type="integer">12</a><b _type="boolean">0</b></r>', ['a'=>12,'b'=>false]],
 ['<r><a><b>x</b></a><a><b>y</b></a></r>', ['a'=>[['b'=>'x'],['b'=>'y']]]],
 ['<r><a _originalKey="5"><![CDATA[ café & birds ]]></a></r>', [5=>'café & birds']],
];
foreach ($readFixtures as [$xml,$expected]) verify($service->readXML($xml,false)===$expected, 'Saved XML shape');
foreach (['','<r>','<!DOCTYPE r [<!ENTITY x SYSTEM "file:///etc/passwd">]><r>&x;</r>','<r _type="object"/>','<r _class="DateTime"/>',str_repeat('<r>',66).str_repeat('</r>',66),str_repeat('x',8388609)] as $bad) rejects(fn()=>$service->readXML($bad,false));
rejects(fn()=>$service->readXML('https://example.invalid/data.xml'));
rejects(fn()=>$service->writeXML(new stdClass()));
rejects(fn()=>$service->writeXML("bad\x01text"));
rejects(fn()=>$service->writeXML("\xff"));
$file=tempnam(sys_get_temp_dir(),'xml-test-');
try {file_put_contents($file,'<r><a>local</a></r>');verify($service->readXML($file)===['a'=>'local'],'Local file');} finally {unlink($file);}
verify(!class_exists('PEAR',false) && !class_exists('XML_Unserializer',false),'Native path loaded PEAR');
if (in_array('--compare',$argv,true)) {
 set_include_path(__DIR__.'/../../app/lib/pear'.PATH_SEPARATOR.get_include_path());
 require 'XML/Serializer.php'; require 'XML/Unserializer.php';
 // The retained oracle emits PHP 8 deprecations; native assertions above stay strict.
 set_error_handler(static function($s,$m,$f,$l){
  if ($s===E_DEPRECATED && str_contains($f,'/app/lib/pear/')) return true;
  throw new ErrorException($m,0,$s,$f,$l);
 });
 foreach ($fixtures as [$input,$expected]) {
  $old=new XML_Serializer(); $old->serialize($input); $xml=$old->getSerializedData();
  verify($service->readXML($xml,false)===$expected,'Read existing PEAR output: '.json_encode([$input,$xml,$service->readXML($xml,false),$expected]));
  $reader=new XML_Unserializer(); $reader->unserialize($service->writeXML($input),false);
  verify($reader->getUnserializedData()===$expected,'PEAR reads native output');
 }
 foreach ($readFixtures as [$xml,$expected]) {$old=new XML_Unserializer();$old->unserialize($xml,false);verify($old->getUnserializedData()===$expected,'Oracle read contract');}
}
echo "PASS: native XML metadata, saved formats, local files, malformed/DTD/object/size/depth rejection and PEAR isolation\n";
