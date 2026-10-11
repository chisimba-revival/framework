<?php
/** Native message adaptation must not load PEAR or activate legacy callbacks. */
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
require __DIR__.'/../../app/classes/core/errorhandler_class_inc.php';
function checkHandler($ok,$message){if(!$ok)throw new RuntimeException($message);}
checkHandler(!class_exists('PEAR',false)&&!class_exists('PEAR_Error',false),'Native handler loaded PEAR');
foreach ([null,false,new stdClass()] as $value) checkHandler((new errorhandler('Fallback',42,$value))->handleError()==='Fallback','Fallback lost');
foreach ([new RuntimeException('Native failure'),new TypeError('Native failure')] as $value) checkHandler((new errorhandler('Fallback',42,$value))->handleError()==='Native failure','Throwable message lost');
checkHandler(!class_exists('PEAR',false),'Native adaptation loaded PEAR');
require __DIR__.'/../../app/lib/pear/PEAR.php';
$called=0;
$old=new PEAR_Error('Existing legacy failure',42,PEAR_ERROR_RETURN);
PEAR::setErrorHandling(PEAR_ERROR_CALLBACK,static function()use(&$called){$called++;});
checkHandler((new errorhandler('Fallback',42,$old))->handleError()==='Existing legacy failure','Existing MDB2 error not supported');
checkHandler($called===0,'Handler constructed a new PEAR error or invoked its callback');
echo "PASS: native errors/fallback without PEAR, existing legacy errors without callback side effects\n";
