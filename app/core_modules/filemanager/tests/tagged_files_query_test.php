<?php
error_reporting(E_ALL);
set_error_handler(static function($n,$s){throw new RuntimeException($s);});
class dbTable {
    public $query;
    public function selectPrepared($sql,$params=[]) {$this->query=[$sql,$params];return [['id'=>'fixture']];}
}
require dirname(__DIR__).'/classes/dbfiletags_class_inc.php';
$db=new dbfiletags();$tag="birds' OR 1=1 --";
$result=$db->getFilesWithTag('owner',$tag);
if($result!==[['id'=>'fixture']]||$db->query[1]!==[$tag,'owner']||str_contains($db->query[0],$tag)||!str_contains($db->query[0],'f.userid = ?'))throw new RuntimeException('Unsafe tagged-file query');
echo "PASS tagged-file values bound and owner scope retained\n";
