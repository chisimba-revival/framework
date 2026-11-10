<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public $objects=[];public function getObject($name,$module=''){return $this->objects[$name];}public function getResourcePath($file,$module){return dirname(__DIR__,2).'/files/resources/'.$file;}}
require dirname(__DIR__).'/classes/thumbnails_class_inc.php';
require dirname(__DIR__).'/classes/filedelivery_class_inc.php';
(new class extends ChisimbaObject {function load(){require dirname(__DIR__,2).'/files/classes/imageresize_class_inc.php';}})->load();
$root=sys_get_temp_dir().'/picker-test-'.bin2hex(random_bytes(6));mkdir($root);mkdir($root.'/secure');mkdir($root.'/public');
try {
 $source=$root.'/public/photo.webp';$im=imagecreatetruecolor(800,600);imagewebp($im,$source);imagedestroy($im);
 $service=new thumbnails();$service->objConfig=new class($root) {function __construct(public $root){}function getcontentBasePath(){return $this->root.'/public';}};
 $service->objImageResize=new imageresize();
 $policy=new class {public $allow=true;function mayRead($file){return $this->allow;}};
 $service->objects=['filereadpolicy'=>$policy,'filedelivery'=>new filedelivery(),'dbsysconfig'=>new class($root){function __construct(public $root){}function getValue(...$args){return $this->root.'/secure';}}];
 $file=['id'=>'test-file','path'=>'photo.webp'];$relative=$service->pickerPreview($file);
 if(!$relative)throw new RuntimeException('No preview');$target=$root.'/secure/'.$relative;$size=getimagesize($target);
 if($size[0]!==267||$size[1]!==200||$size[2]!==IMAGETYPE_JPEG)throw new RuntimeException('Wrong dimensions/type');
 touch($target,1000);clearstatcache();$service->pickerPreview($file);clearstatcache();if(filemtime($target)!==1000)throw new RuntimeException('Cache not reused');
 touch($source,time()+10);clearstatcache();$service->pickerPreview($file);clearstatcache();if(filemtime($target)===1000)throw new RuntimeException('Cache not invalidated');
 $policy->allow=false;if($service->pickerPreview($file)!==false)throw new RuntimeException('Cached private preview leaked');$policy->allow=true;
 if($service->pickerPreview(['id'=>'../bad','path'=>'photo.webp'])!==false||$service->pickerPreview(['id'=>'bad','path'=>'../outside'])!==false)throw new RuntimeException('Traversal allowed');
 if(filedelivery::derivativeId($relative)!=='test-file'||filedelivery::derivativeId('filemanager_thumbnails/picker/../test-file.jpg')!==false)throw new RuntimeException('Unsafe derivative mapping');
 echo "PASS WebP server resize, aspect ratio, cache reuse/invalidation, permission revocation and path checks\n";
} finally { $items=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($items as $item){$item->isDir()?rmdir($item->getPathname()):unlink($item->getPathname());}rmdir($root); }
