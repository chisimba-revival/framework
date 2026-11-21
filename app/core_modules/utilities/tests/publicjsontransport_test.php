<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {}
require dirname(__DIR__).'/classes/publicjsontransport_class_inc.php';
$client=new publicjsontransport();
foreach(['http://api.example.org/','https://user:secret@api.example.org/','https://api.example.org:444/','https://evil.example.org/','https://api.example.org/#fragment'] as $url){
    $rejected=false;try{$client->get($url,['api.example.org'],'Fixture');}catch(InvalidArgumentException $e){$rejected=true;}
    if(!$rejected)throw new RuntimeException('Unsafe request accepted');
}
echo "PASS public JSON URL policy\n";
