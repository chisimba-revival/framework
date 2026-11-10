<?php
// Follow same-site redirects inside the isolated container, never into production.
$originalHost=preg_replace('/^www\./','',$argv[1]);$host=$argv[1];$path='/'.ltrim($argv[2],'/');
for($hop=0;$hop<6;$hop++){
    $context=stream_context_create(['http'=>['header'=>'Host: '.$host."\r\nX-Forwarded-Proto: https\r\n",'ignore_errors'=>true,'timeout'=>15,'follow_location'=>0]]);
    $body=file_get_contents('http://127.0.0.1'.$path,false,$context);if($body===false)exit(1);
    $headers=http_get_last_response_headers();preg_match('~HTTP/[^ ]+ ([0-9]+)~',$headers[0],$match);$status=$match[1];$location='';
    foreach($headers as $header)if(stripos($header,'Location:')===0)$location=trim(substr($header,9));
    if(!in_array($status,['301','302','303','307','308'],true)||$location==='')break;
    $parts=parse_url($location);$nextHost=$parts['host']??$host;
    if(preg_replace('/^www\./','',$nextHost)!==$originalHost)throw new RuntimeException('Cross-site redirect blocked in rehearsal');
    $nextPath=$parts['path']??parse_url($path,PHP_URL_PATH);
    if(!str_starts_with($nextPath,'/'))$nextPath=rtrim(dirname(parse_url($path,PHP_URL_PATH)),'/').'/'.$nextPath;
    if(isset($parts['query']))$nextPath.='?'.$parts['query'];
    if($nextHost===$host&&$nextPath===$path)break;
    $host=$nextHost;$path=$nextPath;
}
echo $body,"\nSTATUS:",$status;
