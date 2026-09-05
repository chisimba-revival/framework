<?php
$root=dirname(__DIR__);
$schema=file_get_contents($root.'/sql/tbl_context.sql');
$db=file_get_contents($root.'/classes/dbcontext_class_inc.php');
$admin=file_get_contents(dirname($root).'/contextadmin/controller.php');
$checks=array(
 'schema defaults off'=>strpos($schema,"'use_sections'")!==FALSE && strpos($schema,"'default' => 0")!==FALSE,
 'create remains positional-compatible'=>strpos($db,'$privateAdmissionMode=NULL, $useSections=FALSE')!==FALSE,
 'update remains positional-compatible'=>strpos($db,'$privateAdmissionMode=FALSE, $useSections=FALSE')!==FALSE,
 'create captures explicit opt in'=>strpos($admin,"'useSections' => \$this->getParam('use_sections') === '1'")!==FALSE,
 'edit captures explicit opt in'=>strpos($admin,"\$this->getParam('use_sections') === '1')")!==FALSE
);
foreach($checks as $name=>$ok){echo ($ok?'PASS':'FAIL').': '.$name.PHP_EOL;if(!$ok){exit(1);}}
?>
