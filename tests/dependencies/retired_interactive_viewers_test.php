<?php
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    public function getObject($name,$module=null){
        if($name==='nativeplayer')return new nativeplayer();
        if($name==='language')return new class {function languageText($key,$module){return $key;}};
        throw new RuntimeException('Unexpected retired service '.$name);
    }
}
$app=dirname(__DIR__,2).'/app';
foreach(['core_modules/files/classes/flashfreemind_class_inc.php','core_modules/files/resources/flashfreemind','core_modules/files/resources/obj3d'] as $path){
 if(file_exists($app.'/'.$path))throw new RuntimeException('Retired viewer returned');
}
require $app.'/core_modules/files/classes/nativeplayer_class_inc.php';
require $app.'/core_modules/filemanager/classes/fileembed_class_inc.php';
require $app.'/core_modules/filemanager/classes/filepreview_class_inc.php';
require $app.'/core_modules/filters/classes/parse4map_class_inc.php';
$embed=new fileembed();$url='/index.php?module=filemanager&action=file&id=private&token=a%2Bb';
foreach(['showObj3d','showVRML','showFreemind'] as $method){
 $out=$embed->$method($url);
 if(!str_contains($out,htmlspecialchars($url,ENT_QUOTES,'UTF-8'))||preg_match('/<object|<applet|<embed|<script/',$out))throw new RuntimeException('Unsafe or lost original file');
}
$filter=new parse4map();$filter->init();
foreach(['[MAP]'.$url.'[/MAP]','[MAP]<a href="'.$url.'">Old map</a>[/MAP]'] as $input){
 if(!str_contains($filter->parse($input),htmlspecialchars($url,ENT_QUOTES,'UTF-8')))throw new RuntimeException('Lost map URL');
}
if($filter->parse('[MAP]javascript:alert(1)[/MAP]')!=='')throw new RuntimeException('Unsafe MAP URL');
$preview=new filepreview();$preview->objFileEmbed=$embed;$preview->file=['datatype'=>'obj','path'=>'/private-storage/not-a-public-url','linkname'=>$url];
foreach(['show3dObject','showAudio','showFreemind'] as $method){
 $out=$preview->$method();
 if(str_contains($out,'private-storage')||!str_contains($out,htmlspecialchars($url,ENT_QUOTES,'UTF-8')))throw new RuntimeException('Preview bypassed authorised file route');
}
echo "PASS: retired viewers absent; MAP/3D/audio preserve authorised routes and originals without plugin markup\n";
