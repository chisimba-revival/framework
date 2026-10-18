<?php
/** Fresh installer schema/seed and catalogue repeatability, disposable DB only. */
if (PHP_SAPI!=='cli'||getenv('CHISIMBA_DB_BASELINE')!=='1') exit(64);
$app='/framework/app';set_include_path($app.PATH_SEPARATOR.get_include_path());
$GLOBALS['kewl_entry_point_run']=true;
define('INSTALL_DIR',$app.'/installer');
$_SESSION=['dsn'=>['phptype'=>'mysqli','hostspec'=>'baseline-db','username'=>'root','password'=>getenv('CHISIMBA_DB_PASSWORD'),'database'=>'contract_fixture']];
require INSTALL_DIR.'/dbhandlers/dbhandler.inc';
$installer=new DbHandler();
if (!$installer->checkConnection('contract_fixture')) throw new RuntimeException('Installer connection failed');
$definitions=array_merge(glob($app.'/core_modules/*/sql/*.sql'),glob('/modules/kanban/sql/*.sql'),glob('/modules/knowledgemap/sql/*.sql'),glob('/modules/questiongenerator/sql/*.sql'));
$count=0;
foreach ($definitions as $definition) {
    $tablename=null;$fields=$options=$indexes=$tableIndexes=[];
    // Schema data files, not runtime upgrade code. Some legacy files need a table context.
    if (!str_contains(file_get_contents($definition),'$fields')) continue;
    include $definition;
    if (!$tablename||!$fields) continue;
    if (in_array($tablename,$installer->listDbTables(),true)) continue;
    $installer->createTables($tablename,$fields,$options);
    foreach($tableIndexes as $name=>$index) $installer->createTableIndex($tablename,$name,$index);
    if ($indexes) $installer->createTableIndex($tablename,'installer_fixture_index',$indexes);
    $count++;
}
$installer->createTables('installer_fixture',['id'=>['type'=>'text','length'=>32],'body'=>['type'=>'text','length'=>100]],[]);
$installer->insert(['id'=>'seed','body'=>"quote ' slash \\ Unicode é"],'installer_fixture');
// The catalogue skips existing tables. Repeating that path must keep seed rows.
foreach([1,2] as $pass) if (!in_array('installer_fixture',$installer->listDbTables(),true)) $installer->createTables('installer_fixture',['id'=>['type'=>'text','length'=>32]],[]);
$fixture=dbTable::forFrameworkConnection($installer->_globalObjDb);
if ($fixture->selectExactPrepared('SELECT body FROM installer_fixture')[0]['body']!=="quote ' slash \\ Unicode é") throw new RuntimeException('Installer seed changed');
if (isset($_SESSION['DbObj'])||isset($_SESSION['pearDbManager'])) throw new RuntimeException('Live connection leaked into session');
if (class_exists('MDB2',false)) throw new RuntimeException('Native installer loaded MDB2');
echo 'PASS: native installer created '.$count." registered definitions, bound seed data and preserved repeated registration; no MDB2 loaded\n";
