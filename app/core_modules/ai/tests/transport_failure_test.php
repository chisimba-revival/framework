<?php
/** Run with curl_* functions disabled: deterministic transport failures, no sockets or credentials. */
// php -d disable_functions=curl_init,curl_setopt_array,curl_exec,curl_error,curl_errno,curl_getinfo
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {}
foreach(['CURLOPT_POST','CURLOPT_POSTFIELDS','CURLOPT_HTTPHEADER','CURLOPT_RETURNTRANSFER','CURLOPT_CONNECTTIMEOUT','CURLOPT_TIMEOUT','CURLINFO_RESPONSE_CODE'] as $i=>$name)if(!defined($name))define($name,$i+1);
if(!defined('CURLE_OPERATION_TIMEDOUT'))define('CURLE_OPERATION_TIMEDOUT',28);
function curl_init($url){return new stdClass();}function curl_setopt_array($handle,$options){}
function curl_exec($handle){return false;}function curl_error($handle){return 'Simulated transport detail';}
function curl_errno($handle){return $GLOBALS['fixture_errno'];}function curl_getinfo($handle,$option){return 0;}
require dirname(__DIR__).'/classes/openaiprovider_class_inc.php';
$p=new openaiprovider();$p->objConfig=new class{function getValue($key,$module){return ['AI_OPENAI_API_KEY'=>'fixture-only','AI_OPENAI_MODEL'=>'fixture-only','AI_REQUEST_TIMEOUT'=>300][$key]??'';}};
$request=['schemaName'=>'fixture','schema'=>['type'=>'object'],'instructions'=>'Fixture','input'=>'Fixture'];
foreach([28=>'openai_timeout',7=>'openai_transport_error'] as $errno=>$expected){$GLOBALS['fixture_errno']=$errno;$result=$p->execute($request);if($result['ok']!==false||$result['error']!==$expected)throw new RuntimeException('Wrong failure classification');}
echo "PASS timeout is distinguished from other transport errors without a network request.\n";
