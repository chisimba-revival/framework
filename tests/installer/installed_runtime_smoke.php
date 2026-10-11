<?php
/** Read-only smoke for an explicitly opted-in disposable fresh installation. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_INSTALLER_FIXTURE') !== '1') exit(64);
$app = $argv[1] ?? '';
if (!is_file($app . '/config/config.xml')) throw new RuntimeException('Installed fixture required');
chdir($app);
$_SERVER += ['REQUEST_METHOD'=>'CLI','HTTP_HOST'=>'127.0.0.1:8086',
    'SCRIPT_NAME'=>'/ch/index.php','QUERY_STRING'=>''];
$GLOBALS['kewl_entry_point_run'] = true;
require 'classes/core/engine_class_inc.php';
$engine = new engine();
$db = $engine->getDbObj();
if (!($db instanceof ChisimbaDatabaseConnection) || class_exists('MDB2', false)) {
    throw new RuntimeException('Fresh installation is not using native PDO');
}
$table = dbTable::forFrameworkConnection($db);
$modules = $table->selectExactPrepared('SELECT module_id FROM tbl_modules');
$ids = array_column($modules, 'module_id');
foreach (['security','language','context','modulecatalogue','registration-service','communications'] as $id) {
    if (!in_array($id, $ids, true)) throw new RuntimeException('Missing initial module: ' . $id);
}
$indexes = (new ChisimbaDatabaseSchema($table))->indexes('tbl_context_parentnodes');
if (!in_array('tbl_context_parentnodes_FKIndex1_idx', array_column($indexes, 'key_name'), true)
    && !in_array('tbl_context_parentnodes_FKIndex1_idx', array_column($indexes, 'Key_name'), true)) {
    throw new RuntimeException('Declared context index missing');
}
$language = $engine->getObject('language', 'language');
$label = $language->languageText('mod_registration_service_forgot_password', 'registration-service');
if ($label !== 'Forgot password') throw new RuntimeException('Native language registration failed');
if (class_exists('Translation2', false) || class_exists('Config', false)) {
    throw new RuntimeException('Legacy configuration or translation loaded');
}
echo 'PASS fresh runtime: PDO, no MDB2/PEAR Config/Translation2, ' . count($ids)
    . " installed modules, recovery translation and context index\n";
