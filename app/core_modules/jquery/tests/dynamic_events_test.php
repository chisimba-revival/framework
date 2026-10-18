<?php
if (PHP_SAPI !== 'cli') exit(64);
error_reporting(E_ALL);
set_error_handler(static function($level,$message){throw new RuntimeException($message);});
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 public array $headers=[];
 public function appendArrayVar($key,$value){$this->headers[]=$value;}
 public function getObject($name,$module){if($name==='jquery')return new jquery();throw new RuntimeException('Unexpected dependency');}
}
require __DIR__.'/../classes/jquery_class_inc.php';
require __DIR__.'/../classes/jqboxy_class_inc.php';
require __DIR__.'/../classes/jqgrid_class_inc.php';
function check($ok,$why){if(!$ok)throw new RuntimeException($why);}
$box=new jqboxy();$box->setTitle('A "quoted" </script> title');$box->setHtml('<strong>Safe fixture</strong>');
$box->bindElement('open-box');
$box->bindElement('later-box');
ob_start();$box->bindElementInline('inline-box');$inline=ob_get_clean();
check(str_starts_with($inline,'<script>'),'Inline binding must emit its handler');
check(!str_contains(implode('',$box->headers),'"quoted" </script>'),'Title must be script-safe');
$grid=new jqgrid();$grid->gridId='fixture-grid';$grid->attachRefreshEvent('refresh-grid');$grid->attachDeleteEvent('delete-row','row"</script>');
$j=new jquery();$focus=$j->dynamicEventScript('focus-field','focus','record("focus");');
try{$j->dynamicEventScript('x','click);bad','');throw new RuntimeException('Accepted invalid event');}catch(InvalidArgumentException $e){}
check(!method_exists($j,'loadLiveQueryPlugin'),'Retired loader remains');
if(in_array('--fixture',$argv,true)){
 echo '<!doctype html><html><head><meta charset="utf-8"><title>Native dynamic event fixture</title><script>function record(value){document.getElementById("results").textContent+=value+"\\n";} function Boxy(html,options){record("box:"+options.title);} function jQuery(element){return {trigger:function(type){record("grid:"+element.id+":"+type);},delGridRow:function(row){record("delete:"+row);}};}</script>';
 echo implode('',$box->headers),implode('',$grid->headers),$focus;
 echo '</head><body><button id="open-box"><span>Open existing dialog</span></button><button id="inline-box">Open inline dialog</button><button id="insert">Insert dynamic dialog button</button><div id="dynamic"></div><button id="refresh-grid">Refresh fixture grid</button><button id="delete-row">Simulate row callback</button><input id="focus-field" aria-label="Focus fixture"><div id="fixture-grid"></div><div id="chisimba_grid_01"></div><pre id="results"></pre>',$inline;
 echo '<script>document.getElementById("insert").addEventListener("click",function(){document.getElementById("dynamic").innerHTML="<button id=\"later-box\"><span>Open dynamic dialog</span></button>";});</script></body></html>';
}else echo "PASS actual wrapper output, inline binding, script-safe values, invalid event rejection and retired loader\n";
