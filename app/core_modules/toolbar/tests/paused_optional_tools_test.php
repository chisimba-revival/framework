<?php
if (PHP_SAPI !== 'cli') exit(64);
error_reporting(E_ALL);
set_error_handler(static function($n,$s){throw new RuntimeException($s);});
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 public function getObject($name,$module=''){throw new RuntimeException('Paused integration loaded a service');}
}
require dirname(__DIR__).'/classes/tools_class_inc.php';
$t=new tools();
$t->moduleCheck=new class {function checkIfRegistered($name){return true;}};
$t->securityContext=new class {function isAuthenticated(){return true;}};
if($t->addBookmark()!==false || $t->addStatusbar()!==false || $t->params!==[])throw new RuntimeException('Paused toolbar emitted controls or scripts');
echo "PASS paused toolbar hooks remain inert with stale registration and an authenticated user\n";
