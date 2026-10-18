<?php
$GLOBALS['kewl_entry_point_run']=true;
set_error_handler(static function($n,$s){throw new ErrorException($s,0,$n);});
class ChisimbaObject {}
require dirname(__DIR__).'/classes/featurebox_class_inc.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
$box=new featurebox();$box->init();
$open=$box->show('Expanded','<p>Kept content</p>','open');
$closed=$box->show('Collapsed','<p>Hidden content</p>','closed','none');
check(str_contains($open,' open>') && str_contains($open,'<summary'),'default opens a native disclosure');
check(!str_contains($closed,' open>') && str_contains($closed,'<details'),'none starts collapsed');
check(str_contains($open,'<h5') && str_contains($open,'Kept content'),'heading and content preserved');
foreach([$box->show('Title','Content','id','default',false),$box->show('Title','Content','id','default',true,false),$box->show('Title','Content',null)] as $plain)check(!str_contains($plain,'<details'),'untoggled and untitled variants remain plain');
$content=$box->show('Title','Content','id','none',true,true,'featurebox','','content');
check(str_contains($content,'<section')&&!str_contains($content,'<details'),'content presentation unchanged');
$escaped=$box->show('Title','Content','id" injected="bad','default',true,true,'box" bad="1','outer" bad="1');
check(!str_contains($escaped,' injected="')&&!str_contains($escaped,' bad="'),'attributes escaped');
check(!str_contains($open,'script')&&!str_contains($closed,'script'),'no JavaScript loader');
echo "PASS native expanded/collapsed disclosures, hidden title, content mode, attribute escaping and no script dependency\n";
