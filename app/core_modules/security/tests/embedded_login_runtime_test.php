<?php
/** Run from an installed Chisimba root; renders only, never submits credentials. */
if(PHP_SAPI!=='cli'||getenv('CHISIMBA_LOGIN_RENDER_TEST')!=='1')exit("Set CHISIMBA_LOGIN_RENDER_TEST=1 in a local/rehearsal runtime.\n");
$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';require 'classes/core/engine_class_inc.php';$engine=new engine();
set_error_handler(static function($n,$s){if(error_reporting()&$n)throw new ErrorException($s,0,$n);return false;});
$html=$engine->getObject('logininterface','security')->renderLoginBox(null,['username'=>'qa-render-only','return_to'=>'/ch/']);
foreach(['name="username"','name="password"','name="native_auth_begin"','name="abuse_signature"','qa-render-only'] as $part)if(!str_contains($html,$part))throw new RuntimeException('Embedded login missing '.$part);
if(str_contains($html,'twitter')||str_contains($html,'openid'))throw new RuntimeException('Retired provider rendered');
echo "PASS embedded login renders native fields, CSRF and abuse evidence without retired providers\n";
