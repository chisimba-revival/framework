<?php
$GLOBALS['kewl_entry_point_run']=true;
set_error_handler(static function($n,$s){throw new ErrorException($s,0,$n);});
class ChisimbaObject {
 public function getObject($name,$module='') {
  if($name==='csslayout')return new class {
   private array $columns=[];
   function setNumColumns($n){} function setLeftColumnContent($s){$this->columns['left']=$s;}
   function setMiddleColumnContent($s){$this->columns['middle']=$s;} function setRightColumnContent($s){$this->columns['right']=$s;}
   function show(){return implode('', $this->columns);}
  };
  if($name==='bestguess')return new class{function guessPageId(){return 'page';}};
  if(in_array($name,['dbuserblocks','dbmodblocks']))return new class{
   function getUserBlocks($id,$side){return 'personal-'.$side;}
   function getModuleBlocks($side){return 'module-'.$side;}
  };
  throw new RuntimeException($name);
 }
 public function getJavaScriptFile($file,$module){return '<script src="'.$file.'"></script>';}
}
class htmlheading {public $type,$str;function show(){return '<h3>'.$this->str.'</h3>';}}
class button {public $cssId;function __construct(...$args){}function show(){return '<button id="'.$this->cssId.'">Add</button>';}}
require dirname(__DIR__).'/classes/buildcanvas_class_inc.php';
class BuilderFixture extends buildcanvas {
 function getSmallBlocks($position='right'){return '<select></select>';}
 function getWideBlocks(){return '<select></select>';}
 function getScriptValues(){return '<script>/* labels */</script>';}
}
$b=new BuilderFixture();$b->objUser=new class{function isLoggedIn(){return false;}};
$b->objLanguage=new class{function languageText(...$args){return 'Label';}};
$repo=new class{function getPageBlocks($key,$side){return 'page-'.$side;}};
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
foreach(['personal','module','page'] as $mode){
 $html=$b->show($mode,'page',$repo);
 foreach(['left','middle','right'] as $side)check(str_contains($html,$mode.'-'.$side),'existing content preserved');
 check(!str_contains($html,'script')&&!str_contains($html,'button')&&!str_contains($html,'select'),'unconfigured rendering read-only');
}
$html=$b->show('page','page',$repo,'<div id="block-editor-settings"></div>');
check(str_contains($html,'contextblocks.js')&&str_contains($html,'editmodeswitchbutton'),'configured page uses native editor');
check(!str_contains($html,'canvasblocks.js'),'legacy loader retired');
check(!is_file(dirname(__DIR__).'/resources/canvasblocks.js'),'legacy asset absent');
echo "PASS legacy rendering preserved without controls and configured page uses native editor\n";
