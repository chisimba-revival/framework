<?php
class ChisimbaObject {}
require dirname(__DIR__,2).'/app/core_modules/filemanager/classes/filedelivery_class_inc.php';
set_error_handler(function($n,$s){throw new RuntimeException($s);});
$tests = [
 [10,'GET',null,200,0,10,true], [10,'HEAD',null,200,0,10,false],
 [10,'POST',null,405,0,0,false], [10,'GET','bytes=2-4',206,2,3,true],
 [10,'GET','bytes=5-',206,5,5,true], [10,'GET','bytes=-3',206,7,3,true],
 [10,'GET','bytes=-20',206,0,10,true], [10,'GET','bytes=8-99',206,8,2,true],
 [10,'GET','bytes=10-',416,0,0,false], [10,'GET','bytes=5-2',416,0,0,false],
 [10,'GET','bytes=-0',416,0,0,false], [0,'GET','bytes=0-',416,0,0,false],
 [0,'GET',null,200,0,0,true], [10,'HEAD','bytes=1-2',200,0,10,false],
 [10,'GET','bytes=1-2,4-5',200,0,10,true], [10,'GET','garbage',200,0,10,true]
];
foreach($tests as [$size,$method,$range,$status,$offset,$length,$body]) {
 $p=filedelivery::plan($size,$method,$range);
 if([$p['status'],$p['offset'],$p['length'],$p['body']]!==[$status,$offset,$length,$body])throw new Exception(json_encode($p));
}
$root=sys_get_temp_dir().'/delivery-'.bin2hex(random_bytes(5));mkdir($root);file_put_contents($root.'/a','bytes');symlink('/etc/hosts',$root.'/escape');
try {
 if(filedelivery::resolve($root,'a')!==$root.'/a')throw new Exception('valid file');
 foreach(['../a','/etc/hosts','escape',"a\0",'missing'] as $path)if(filedelivery::resolve($root,$path)!==false)throw new Exception('unsafe path');
} finally {unlink($root.'/a');unlink($root.'/escape');rmdir($root);}
echo "22 delivery assertions passed\n";

foreach (['filemanager_thumbnails/id.png'=>'id','filemanager_thumbnails/large/id.jpg'=>'id','filemanager_thumbnails/standard_id.jpg'=>'id','filemanager_thumbnails/id.htm'=>'id','filemanager_forcemax/id.jpg'=>'id','filemanager_thumbnails/../id.jpg'=>false,'filemanager_thumbnails/id.php'=>false,'context/id.jpg'=>false] as $path=>$expected) {
 if(filedelivery::derivativeId($path)!==$expected)throw new Exception('Derivative mapping failed');
}
echo "8 derivative mapping assertions passed\n";
