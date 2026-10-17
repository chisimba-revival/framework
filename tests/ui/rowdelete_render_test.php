<?php
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject {
    public array $scripts=[];
    public function appendArrayVar($name,$script) {$this->scripts[]=$script;}
    public function getJavaScriptFile($name,$module) {return $module.'/'.$name;}
    public function getObject($name,$module) {return new class {public function render($name){return '<svg></svg>';}};}
}
require __DIR__.'/../../app/core_modules/ui/classes/rowdelete_class_inc.php';
$view=new rowdelete();
$input=array_fill_keys(['url','id','revision','csrf','confirm','failure','success','label'],'"<script>alert(1)</script>&');
$html=$view->render($input);$view->render($input);
if(str_contains($html,'<script>')||!str_contains($html,'&quot;&lt;script&gt;')||count($view->scripts)!==1)throw new RuntimeException('Escaping or single-load contract failed');
if(!str_contains($html,'type="button"')||!str_contains($html,'aria-label=')||!str_contains($view->wrap($html),'role="status"'))throw new RuntimeException('Accessible deletion contract failed');
echo "PASS row deletion rendering escapes all attributes and loads one native client\n";
