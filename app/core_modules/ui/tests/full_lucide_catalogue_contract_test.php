<?php
$root=dirname(__DIR__);$service=file_get_contents($root.'/classes/iconservice_class_inc.php');$icons=glob($root.'/resources/icons/lucide/*.svg')?:array();$fail=array();
if(count($icons)<2000){$fail[]='complete Lucide catalogue is not bundled';}
if(!is_file($root.'/resources/icons/lucide/award.svg')){$fail[]='award icon is missing';}
if(strpos($service,'private $allowedIcons')!==false){$fail[]='manual icon allowlist remains';}
if(strpos($service,"preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', \$name)")===false){$fail[]='safe icon-name validation missing';}
if(strpos($service,'is_file($path)')===false){$fail[]='local asset existence check missing';}
foreach($icons as $icon){$svg=file_get_contents($icon);if($svg===false||stripos($svg,'<svg')===false||preg_match('/<(?:script|foreignObject)\b|\son[a-z]+\s*=|(?:href|src)\s*=\s*["\']https?:/i',$svg)){$fail[]='unsafe or invalid SVG: '.basename($icon);break;}}
if($fail){fwrite(STDERR,implode("\n",$fail)."\n");exit(1);}echo 'PASS: complete pinned Lucide catalogue and safe local discovery verified.'.PHP_EOL;
?>
