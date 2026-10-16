<?php
if(PHP_SAPI!=='cli')exit(64);
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {}
$root=dirname(__DIR__,2);
require $root.'/app/core_modules/jquery/classes/jquery_class_inc.php';
foreach(['loadDDMenuPlugin','loadJFramePlugin'] as $method)if(method_exists('jquery',$method))throw new RuntimeException('Retired loader returned');
foreach(['loadSuperFishMenuPlugin','loadFgMenuPlugin','loadBoxPlugin','loadUITabbing'] as $method)if(!method_exists('jquery',$method))throw new RuntimeException('CMS dependency removed');
foreach(['tabs','accordion'] as $name)if(is_file($root.'/app/core_modules/jquerycore/classes/'.$name.'_class_inc.php'))throw new RuntimeException('Unused wrapper returned');
if(!is_file($root.'/app/core_modules/jquery/classes/jqtabs_class_inc.php'))throw new RuntimeException('Legacy CMS tabs removed');
echo "PASS unused loaders/wrappers absent and required CMS wrappers retained\n";
