<?php
/** Complete classification constraints and define, but do not grant, management. @author Derek Keats */
if (PHP_SAPI!=='cli') exit(1);
$app=$argv[1]??dirname(__DIR__,3);
if (!is_file($app.'/classes/core/engine_class_inc.php')) throw new RuntimeException('Pass the Chisimba application directory');
chdir($app);
$_SERVER['REQUEST_METHOD']='CLI'; $_SERVER['HTTP_HOST']='localhost';
$_SERVER['SCRIPT_NAME']='/index.php'; $_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true;
require_once 'classes/core/engine_class_inc.php';
$engine=new engine();
$store=$engine->getObject('classificationstore','classification');
// The legacy installer creates ordinary indexes even when a definition requests uniqueness.
// Complete and verify these essential constraints explicitly; never do DDL during content requests.
foreach (['vocabularies','terms','links'] as $suffix) {
    $table='tbl_classification_'.$suffix;
    $rows=$store->query('SHOW INDEX FROM '.$table);
    $found=false;
    foreach ($rows as $row) {
        $row=array_change_key_case($row, CASE_LOWER);
        if ($row['key_name']==='classification_id_unique' && !$row['non_unique']) $found=true;
    }
    if (!$found && $store->query('ALTER TABLE '.$table.' ADD UNIQUE KEY classification_id_unique (id)')===false) {
        throw new RuntimeException('Cannot establish classification identity constraint');
    }
}
$permissions=$engine->getObject('permissionservice','security');
$area=$permissions->ensureArea('chisimba','classification');
if (!$permissions->ensureRight($area,'manage')) throw new RuntimeException('Permission definition failed');
echo "Classification identity constraints and management permission configured; no grants changed.\n";
