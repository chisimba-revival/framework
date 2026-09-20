<?php
class ChisimbaObject {}
require dirname(__DIR__,2).'/app/core_modules/filemanager/classes/filedelivery_class_inc.php';
$file=['filefolder'=>'users/example/images','category'=>'images','access'=>'public','visibility'=>'visible'];
$folder=['access'=>'public'];$n=0;
$check=function($f,$d,$m,$want)use(&$n){if(filedelivery::mayCachePublicImageRedirect($f,$d,$m)!==$want)throw new RuntimeException('Cache boundary failed: '.json_encode([$f,$d,$m]));$n++;};
$check($file,$folder,'GET',true);$check($file,$folder,'HEAD',true);
foreach(['POST','PUT','DELETE'] as $m)$check($file,$folder,$m,false);
foreach(['private_all','private_selected',''] as $access){$check(array_replace($file,['access'=>$access]),$folder,'GET',false);$check($file,['access'=>$access],'GET',false);}
foreach(['hidden',''] as $v)$check(array_replace($file,['visibility'=>$v]),$folder,'GET',false);
foreach(['context/public-course','assignment/submission',''] as $p)$check(array_replace($file,['filefolder'=>$p]),$folder,'GET',false);
$check(array_replace($file,['category'=>'documents']),$folder,'GET',false);
$check(null,$folder,'GET',false);$check($file,null,'GET',false);
$check(array_replace($file,['visibility'=>null]),['access'=>null],'GET',true);
echo "$n public-image cache boundary checks passed\n";
