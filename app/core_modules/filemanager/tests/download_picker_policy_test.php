<?php
/** Download selection reuses existing document/media validation without allowing executable extensions. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject{}
require dirname(__DIR__).'/classes/fileapi_class_inc.php';
$api=new fileapi;$policy=(new ReflectionMethod($api,'filePolicy'))->invoke($api,'download');
foreach(['pdf','zip','mp3','mp4','jpg','docx'] as $ext)if(!in_array($ext,$policy['extensions'],true))throw new RuntimeException('Missing download type '.$ext);
foreach(['php','html','js','exe','svg'] as $ext)if(in_array($ext,$policy['extensions'],true))throw new RuntimeException('Executable download type '.$ext);
echo "PASS: shared downloadable-file policy reuses safe document, media, image and ZIP types.\n";
