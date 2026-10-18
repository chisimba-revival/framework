<?php
/** Strict integration of the real wrappers and upstream parser, without a database. */
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 public static array $services=[];
 function getObject($name,$module='filemanager') {return self::$services[$module.'/'.$name];}
 function getResourceUri($name) {return __DIR__.'/../../app/core_modules/files/resources/'.$name;}
}
$app=__DIR__.'/../../app/core_modules/';
foreach (['files/classes/getid3analyzer','filemanager/classes/analyzemediafile','filemanager/classes/singlearray','utilities/classes/xmlserial'] as $file) require $app.$file.'_class_inc.php';
$parser=new getid3analyzer();$parser->init();
ChisimbaObject::$services=[
 'files/getid3analyzer'=>$parser,'filemanager/singlearray'=>new singlearray(),'utilities/xmlserial'=>new xmlserial(),
 'files/fileparts'=>new class {function getExtension($path){return pathinfo($path,PATHINFO_EXTENSION);}},
];
$media=new analyzemediafile();$media->init();
$root=$argv[1];
foreach(['tone.wav','tone.mp3','tone.flac','tone.ogg','image.png','image.jpg','image.mp4'] as $file) {
 $before=error_reporting();$raw=$parser->analyze($root.'/'.$file);
 if(!empty($raw['error']))throw new RuntimeException($file.': '.implode(';',$raw['error']));
 if(error_reporting()!==$before)throw new RuntimeException('Error reporting changed');
 [$info,$mime]=$media->analyzeFile($root.'/'.$file);
 if($file==='tone.ogg' && ($mime!=='audio/ogg' || (int)$info['samplerate']!==8000))throw new RuntimeException('Ogg MIME/sample rate '.json_encode([$mime,$info['samplerate']]));
 $xml=ChisimbaObject::$services['utilities/xmlserial']->readXML($info['getid3info'],false);
 if(!is_array($xml)||empty($info['format']))throw new RuntimeException('Missing metadata '.$file);
 if(str_starts_with($file,'image.') && ((int)$info['width']!==64 || (int)$info['height']!==48))throw new RuntimeException('Dimensions '.$file);
 if(in_array($file,['tone.mp3','tone.flac','tone.ogg']) && ($info['title']??'')!=='Café & birds')throw new RuntimeException('UTF-8 title '.$file.': '.json_encode($info['title']));
 echo 'PASS '.$file." metadata and native XML\n";
}
foreach(['empty.dat','bad.mp3','missing.mp3'] as $file) {
 $raw=$parser->analyze($root.'/'.$file);
 if(empty($raw['error']))throw new RuntimeException('Invalid media was accepted');
}
if(class_exists('PEAR',false))throw new RuntimeException('PEAR loaded');
echo "PASS invalid/missing media, unchanged diagnostics and no PEAR\n";
