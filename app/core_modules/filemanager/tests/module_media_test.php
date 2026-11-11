<?php
$GLOBALS['kewl_entry_point_run']=true;
class dbTable {}
require __DIR__.'/../classes/modulefilestore_class_inc.php';
$png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aV1EAAAAASUVORK5CYII=');
if(modulefilestore::mediaType($png)['extension']!=='png')throw new RuntimeException('PNG rejected');
foreach(['<?php echo "bad";', '<svg xmlns="http://www.w3.org/2000/svg"></svg>',str_repeat('a',10485761),'ID3fake audio'] as $bytes){
 try{modulefilestore::mediaType($bytes);throw new RuntimeException('Unsafe file accepted');}catch(InvalidArgumentException $e){}
}
echo "PASS PNG, active document rejection, oversized media and fake audio rejection\n";
