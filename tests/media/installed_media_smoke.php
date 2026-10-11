<?php
/** Usage: CHISIMBA_INSTALLER_FIXTURE=1 php installed_media_smoke.php /installed/ch /fixtures */
if (PHP_SAPI!=='cli' || getenv('CHISIMBA_INSTALLER_FIXTURE')!=='1') exit(64);
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
require __DIR__.'/../installer/installed_runtime_smoke.php';
$analyzer=$engine->getObject('analyzemediafile','filemanager');
$xml=$engine->getObject('xmlserial','utilities');
foreach(['tone.wav','tone.mp3','tone.flac','tone.ogg','image.png','image.jpg','image.mp4'] as $file) {
 [$info,$mime]=$analyzer->analyzeFile($argv[2].'/'.$file);
 if(!is_array($xml->readXML($info['getid3info'],false)))throw new RuntimeException('Invalid metadata '.$file);
 if($file==='tone.ogg' && ($mime!=='audio/ogg' || (int)$info['samplerate']!==8000))throw new RuntimeException('Ogg metadata');
 echo 'PASS installed media '.$file.PHP_EOL;
}
if(class_exists('PEAR',false)||class_exists('XML_Serializer',false))throw new RuntimeException('Legacy XML/PEAR loaded');
