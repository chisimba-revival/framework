<?php
/** These cases all terminate before an HTTP request can be made. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject { public function getObject($name,$module=null){return $GLOBALS['services'][$name];} }
require dirname(__DIR__).'/classes/transcriptionservice_class_inc.php';
$root=sys_get_temp_dir().'/transcribe-'.bin2hex(random_bytes(6));mkdir($root,0700);mkdir($root.'/private',0700);
file_put_contents($root.'/outside','fixture');file_put_contents($root.'/private/audio','fixture');symlink($root.'/outside',$root.'/private/link');
$config=new class {public $values=[];public function getValue($key,$module){return $this->values[$key]??'';}};
$config->values=['AI_STATE'=>'enabled','AI_TRANSCRIPTION_STATE'=>'disabled','AI_OPENAI_API_KEY'=>'fixture-not-a-real-key','AI_TRANSCRIPTION_MODEL'=>'unsupported','SECUREFODLER'=>$root.'/private'];
$GLOBALS['services']=['dbsysconfig'=>$config,'audioinspection'=>new class {public function inspect($path){return ['ok'=>true,'mime'=>'audio/wav','extension'=>'wav'];}}];
$s=new transcriptionservice();$s->init();
function expect($actual,$error){if(($actual['error']??'')!==$error)throw new RuntimeException($error);}
try{
 expect($s->transcribe($root.'/private/audio'),'transcription_unavailable');$config->values['AI_TRANSCRIPTION_STATE']='enabled';
 expect($s->transcribe($root.'/outside'),'private_audio_required');
 expect($s->transcribe($root.'/private/link'),'private_audio_required');
 expect($s->transcribe('https://example.invalid/recording.wav'),'private_audio_required');
 expect($s->transcribe($root.'/private/audio'),'transcription_model');
 echo "PASS disabled service, public/outside file, symlink escape, URL and unsupported-model rejection without network requests.\n";
}finally{unlink($root.'/private/link');unlink($root.'/private/audio');unlink($root.'/outside');rmdir($root.'/private');rmdir($root);}
