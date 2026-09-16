<?php
/** Synthetic audio validation, including headerless streaming WebM. No provider calls. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {}
require dirname(__DIR__).'/classes/audioinspection_class_inc.php';
$directory=sys_get_temp_dir().'/spoken-audio-'.bin2hex(random_bytes(6));mkdir($directory,0700);
function verify($ok,$label){if(!$ok)throw new RuntimeException($label);}
function generate(array $args){$pipes=[];$p=proc_open(array_merge(['/usr/bin/ffmpeg','-v','error','-y'],$args),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);verify(proc_close($p)===0,'Synthetic fixture generation: '.$error);return $out;}
try{
 $inspection=new audioinspection();
 foreach(['wav','mp3','m4a','ogg','flac'] as $extension){$path=$directory.'/short.'.$extension;generate(['-f','lavfi','-i','sine=frequency=440:duration=1',$path]);$r=$inspection->inspect($path);verify(!empty($r['ok']) && $r['duration']<2,$extension.' accepted');}
 $stream=generate(['-f','lavfi','-i','sine=frequency=440:duration=1','-c:a','libopus','-f','webm','pipe:1']);file_put_contents($directory.'/stream.webm',$stream);verify(!empty($inspection->inspect($directory.'/stream.webm')['ok']),'WebM without duration header');
 generate(['-f','lavfi','-i','sine=frequency=440:duration=181','-c:a','aac',$directory.'/long.m4a']);verify(($inspection->inspect($directory.'/long.m4a')['error']??'')==='audio_duration','Long audio rejected');
 generate(['-f','lavfi','-i','color=c=black:s=16x16:d=1','-f','lavfi','-i','sine=duration=1','-c:v','mpeg4','-c:a','aac','-shortest',$directory.'/video.mp4']);verify(($inspection->inspect($directory.'/video.mp4')['error']??'')==='audio_only','Video rejected');
 file_put_contents($directory.'/spoof.mp3','This is not an audio recording.');verify(($inspection->inspect($directory.'/spoof.mp3')['error']??'')==='audio_type','Spoofed extension rejected');
 $h=fopen($directory.'/big.wav','w');ftruncate($h,20971521);fclose($h);verify(($inspection->inspect($directory.'/big.wav')['error']??'')==='audio_size','Oversize rejected');
 file_put_contents($directory.'/empty','');verify(($inspection->inspect($directory.'/empty')['error']??'')==='audio_size','Empty rejected');
 echo "PASS WAV, MP3, M4A, OGG, FLAC, streaming WebM, duration, video, spoofed type, size and empty-file validation.\n";
}finally{foreach(glob($directory.'/*') as $file)unlink($file);rmdir($directory);}
