<?php
/** Recovered production fix: stale registry rows are never selectable. */
$GLOBALS['kewl_entry_point_run']=true;
class dbtable {public function getAll($filter){return $filter;}}
require dirname(__DIR__).'/classes/dbmoduleblocks_class_inc.php';
$blocks=new dbmoduleblocks();
foreach([[null,null,null],['wide','site|user','author'],['normal','context','root']] as $args){
 $filter=$blocks->getBlocks(...$args);
 if(!str_contains($filter,'moduleid IN (SELECT module_id FROM tbl_modules)'))throw new RuntimeException('Uninstalled block offered');
}
echo "PASS: all block chooser variants exclude uninstalled modules\n";
