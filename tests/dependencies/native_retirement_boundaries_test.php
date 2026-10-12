<?php
/** Retired extension points must fail clearly without loading missing PEAR files. */
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
$app=dirname(__DIR__,2).'/app';
require $app.'/core_modules/utilities/resources/safehtml/HTMLSax3.php';
$parser=new XML_HTMLSax3();
$invalid=null;
try {$parser->set_object($invalid);throw new RuntimeException('Invalid handler accepted');}
catch (InvalidArgumentException $expected) {}
$parser->state_parser=(object)['parser_options'=>[]];
try {$parser->set_option('UNKNOWN');throw new RuntimeException('Invalid option accepted');}
catch (InvalidArgumentException $expected) {}
if(class_exists('PEAR',false))throw new RuntimeException('Legacy parser error path loaded PEAR');
echo "PASS: HTMLSax invalid handlers/options fail with native exceptions and no PEAR import\n";
