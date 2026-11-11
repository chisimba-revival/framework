<?php
/** Test native dbTable contracts on disposable synthetic data, never a site.
 * @author Derek Keats
 */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_DB_BASELINE') !== '1') exit(64);
$app = '/framework/app';
set_include_path($app . PATH_SEPARATOR . get_include_path());
require_once $app . '/classes/core/chisimbadatabaseconnection.php';
$db = ChisimbaDatabaseConnection::fromLegacyDsn(['phptype' => 'mysqli', 'hostspec' => 'baseline-db:3306',
    'username' => 'root', 'password' => getenv('CHISIMBA_DB_PASSWORD'), 'database' => 'contract_fixture']);
$GLOBALS['kewl_entry_point_run'] = true;
// Only object construction/configuration is bypassed. All database operations use
// the real source dbTable and native PDO boundary, with caches disabled by default.
class ChisimbaObject {public function getObject($name,$module=''){return $GLOBALS['pathObjects'][$name];}}
require_once $app . '/classes/core/dbtable_class_inc.php';
$table = new dbTable();
(new ReflectionProperty(dbTable::class, '_db'))->setValue($table, $db);
$table->dbLayer = 'PDO';
$table->dbType = 'mysqli';
$table->_tableName = 'pdo_contract_rows';
$table->_serverName = 'fixture';
function ensureBaseline(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
ensureBaseline($table->query('CREATE TABLE pdo_contract_rows (puid INT AUTO_INCREMENT PRIMARY KEY, id VARCHAR(64) UNIQUE, body TEXT NULL, amount DECIMAL(10,2), quantity INT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3') !== false, 'fixture schema failed');
function injectCallerTable(dbTable $caller, $name): void {
 global $db,$table;(new ReflectionProperty(dbTable::class,'_db'))->setValue($caller,$db);
 $caller->dbLayer='PDO';$caller->dbType='mysqli';$caller->_tableName=$name;$caller->_serverName='fixture';
}
require __DIR__.'/shorturl_checks.php';
require __DIR__.'/contentpaths_checks.php';
