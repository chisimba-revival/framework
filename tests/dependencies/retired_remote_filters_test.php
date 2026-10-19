<?php
/** Retired filters cannot fetch, write caches, load services or lose stored text. */
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 public function getObject(...$args){throw new RuntimeException('Unexpected service');}
 public function newObject(...$args){throw new RuntimeException('Unexpected service');}
 public function getPearResource(...$args){throw new RuntimeException('Unexpected PEAR');}
}
foreach(['blog','blogpost','screenshot','tracwiki','gvid','myspacevid','sketchcast','slideshare','wpresent','rtt'] as $name){
 require dirname(__DIR__,2).'/app/core_modules/filters/classes/parse4'.$name.'_class_inc.php';
 $class='parse4'.$name;$filter=new $class();$filter->init();
 foreach(['','Plain text 日本語','[BLOG: server=example.invalid, postid=1]', '[BLOG: incomplete]', "[BLOG: server=x\');alert(1);//, postid=1]",'<p>Before [BLOGPOST: server=example.invalid, postid=1] after</p>',
 '[SCREENSHOT]https://example.invalid/?a=1&b=2[/SCREENSHOT]',
 "Before [TRACWIKI: page=WikiStart]\nAfter",'[RTT: room=example]', '[BLOGPOST: incomplete','[GVID]https://example.invalid/?docid=7[/GVID]', '[MYSPACEVID: 7]', '[SKETCHCAST: id=7]', '[SLIDESHARE: id=7,doc=example]', '[WPRESENT: type=byurl,url=/original.swf]'] as $text){
  if($filter->parse($text)!==$text)throw new RuntimeException('Stored content changed');
 }
}
if(class_exists('XML_RPC_Client',false))throw new RuntimeException('RPC loaded');
echo "PASS retired remote filters preserve ordinary/legacy/Unicode/malformed content without service loading\n";
