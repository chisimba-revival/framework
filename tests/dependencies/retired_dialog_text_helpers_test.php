<?php
if (PHP_SAPI !== 'cli') exit(64);
$root=dirname(__DIR__,2);
foreach(json_decode(file_get_contents(__DIR__.'/retired-dialog-text-helper-paths.json'),true,512,JSON_THROW_ON_ERROR) as $path) {
 if(file_exists($root.'/'.$path))throw new RuntimeException('Retired helper returned: '.$path);
}
foreach(['app/core_modules/jquery/classes/jqboxy_class_inc.php','app/core_modules/jquery/classes/jqgrid_class_inc.php','app/core_modules/jquery/classes/jqtabs_class_inc.php','app/core_modules/jquerycore/resources/core/1.7.2/jquery.min.js','app/core_modules/jquerycore/resources/ui/javascript/1.8.18/jquery-ui.min.js'] as $path) {
 if(!is_file($root.'/'.$path))throw new RuntimeException('Still-required dependency missing: '.$path);
}
echo "PASS retired helper assets absent; CMS wrappers and shared core/UI preserved\n";
