<?php
/** Bounded server-side audio validation shared by transcription consumers. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class audioinspection extends ChisimbaObject
{
    public function inspect($path,$maxSeconds=180,$maxBytes=20971520)
    {
        if(!is_string($path)||!is_file($path)||filesize($path)<1||filesize($path)>$maxBytes)return ['ok'=>false,'error'=>'audio_size'];
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);
        $types=['audio/mpeg'=>'mp3','audio/mp4'=>'m4a','video/mp4'=>'m4a','audio/x-m4a'=>'m4a','audio/wav'=>'wav','audio/x-wav'=>'wav','audio/ogg'=>'ogg','application/ogg'=>'ogg','audio/webm'=>'webm','video/webm'=>'webm','audio/flac'=>'flac','audio/x-flac'=>'flac'];
        if(!isset($types[$mime]))return ['ok'=>false,'error'=>'audio_type'];
        if(!is_executable('/usr/bin/ffprobe'))return ['ok'=>false,'error'=>'audio_inspector_unavailable'];
        $pipes=[];$p=proc_open(['/usr/bin/ffprobe','-v','error','-protocol_whitelist','file,pipe','-show_entries','format=duration:stream=codec_type:packet=pts_time,duration_time','-of','json',$path],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($p))return ['ok'=>false,'error'=>'audio_inspector_unavailable'];
        fclose($pipes[0]);stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$output='';$deadline=microtime(true)+10;$timedOut=false;
        do{$output.=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);$status=proc_get_status($p);if(strlen($output)>2097152||microtime(true)>$deadline){$timedOut=true;proc_terminate($p,9);break;}if($status['running'])usleep(10000);}while($status['running']);
        $output.=stream_get_contents($pipes[1]);fclose($pipes[1]);fclose($pipes[2]);proc_close($p);
        $data=json_decode($output,true);$duration=(float)($data['format']['duration']??0);$streams=$data['streams']??[];
        $first=null; $last=null;
        foreach (($data['packets']??[]) as $packet) {
            if (!isset($packet['pts_time']) || !is_numeric($packet['pts_time'])) continue;
            $start=(float)$packet['pts_time']; $end=$start+max(0,(float)($packet['duration_time']??0));
            $first=$first===null?$start:min($first,$start); $last=$last===null?$end:max($last,$end);
        }
        if ($first!==null && $last!==null) $duration=max($duration,$last-$first);
        if($timedOut||strlen($output)>2097152||($status['exitcode']??-1)!==0||!$streams||$first===null||$duration<=0||$duration>$maxSeconds)return ['ok'=>false,'error'=>'audio_duration'];
        foreach($streams as $stream)if(($stream['codec_type']??'')!=='audio')return ['ok'=>false,'error'=>'audio_only'];
        return ['ok'=>true,'mime'=>$mime,'extension'=>$types[$mime],'duration'=>$duration,'bytes'=>filesize($path)];
    }
}
