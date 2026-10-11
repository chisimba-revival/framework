<?php
/** Test native dbTable contracts on disposable synthetic data, never a site.
 * @author Derek Keats
 */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_DB_BASELINE') !== '1') exit(64);
$app = '/framework/app';
set_include_path($app . PATH_SEPARATOR . $app . '/lib/pear' . PATH_SEPARATOR . get_include_path());
require_once $app . '/classes/core/chisimbadatabaseconnection.php';
$db = ChisimbaDatabaseConnection::fromLegacyDsn(['phptype' => 'mysqli', 'hostspec' => 'baseline-db:3306',
    'username' => 'root', 'password' => getenv('CHISIMBA_DB_PASSWORD'), 'database' => 'contract_fixture']);
$GLOBALS['kewl_entry_point_run'] = true;
// Only object construction/configuration is bypassed. All database operations use
// the real source dbTable and native PDO boundary, with caches disabled by default.
class ChisimbaObject {}
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
$value = "Quote ' slash \\ Unicode é 中文";
$id = $table->insert(['id' => 'first', 'body' => $value, 'amount' => '12.30', 'quantity' => 0]);
ensureBaseline($id === 'first' && $table->getLastInsertId() === 'first', 'Application ID contract failed');
$table->insert(['id' => 'second', 'body' => null, 'amount' => '0.00', 'quantity' => 2]);
$table->insert(['id' => 'third', 'body' => '', 'amount' => null, 'quantity' => null]);
ensureBaseline((int)$table->query("SELECT COUNT(*) AS n FROM ".$table->_tableName." WHERE id='third' AND body IS NULL")[0]['n']===1, 'Legacy empty-string writes must store SQL NULL');
$observed = [
    'row' => $table->getRow('id', 'first'),
    'nullRow' => $table->getRow('id', 'second'),
    'emptyStringRow' => $table->getRow('id', 'third'),
    'missingRow' => $table->getRow('id', 'missing'),
    'emptySet' => $table->getArray('SELECT * FROM pdo_contract_rows WHERE 1=0'),
    'aliasCase' => $table->query('SELECT id AS MIXEDCASE FROM pdo_contract_rows WHERE id=\'first\''),
    'limited' => $table->getArrayWithLimit('SELECT id FROM pdo_contract_rows ORDER BY puid', 1, 1),
    'afterLimit' => $table->query('SELECT id FROM pdo_contract_rows ORDER BY puid'),
    'recordCount' => $table->getRecordCount(),
    'whitespace' => $table->query("SELECT '' AS empty, '   ' AS spaces, ' x ' AS padded"),
    'unchangedAffected' => $table->query("UPDATE pdo_contract_rows SET quantity=0 WHERE id='first'"),
    'changedAffected' => $table->query("UPDATE pdo_contract_rows SET quantity=1 WHERE id='first'"),
    'missingAffected' => $table->query("UPDATE pdo_contract_rows SET quantity=1 WHERE id='missing'"),
];
ensureBaseline($observed['row']['body'] === $value, 'Quoted Unicode roundtrip failed');
ensureBaseline($observed['emptySet'] === [], 'Empty set contract failed');
ensureBaseline($observed['limited'] === [['id' => 'second']], 'Limit/offset contract failed');
ensureBaseline(count($observed['afterLimit']) === 3, 'Limit leaked into following query');
$table->beginTransaction();
$table->insert(['id' => 'rolled-back', 'body' => 'temporary']);
$table->rollbackTransaction();
ensureBaseline($table->query("SELECT id FROM pdo_contract_rows WHERE id='rolled-back'") === [], 'Rollback failed');
$table->beginTransaction();
$table->insert(['id' => 'committed', 'body' => 'durable']);
$table->commitTransaction();
ensureBaseline($table->getRow('id', 'committed')['body'] === 'durable', 'Commit failed');
$observed['transactionChecks'] = 'commit and rollback passed';
$observed['updateReturnType'] = get_debug_type($table->update('id', 'first', ['body' => 'updated']));
$observed['deleteReturnType'] = get_debug_type($table->delete('id', 'first'));
$generated = $table->insert(['body' => 'generated']);
ensureBaseline(is_string($generated) && str_starts_with($generated, 'fixture_'), 'Generated ID failed');
$observed['generatedIdShape'] = 'fixture_<random>_<timestamp>';

$expected = json_decode(file_get_contents(__DIR__ . '/mdb2-baseline.json'), true, 512, JSON_THROW_ON_ERROR);
ensureBaseline($observed['updateReturnType'] === 'bool' && $observed['deleteReturnType'] === 'bool', 'Documented mutation success booleans required');
unset($observed['updateReturnType'], $observed['deleteReturnType'], $expected['updateReturnType'], $expected['deleteReturnType']);
ensureBaseline($observed === $expected, 'Native dbTable differs from the captured MDB2 value contract');
ensureBaseline(!class_exists('PEAR', false) && !class_exists('MDB2', false), 'Native path loaded PEAR/MDB2');
require __DIR__ . '/pdo_boundary_checks.php';
echo "PASS: native dbTable matches MDB2 values; checked native binding, errors, ownership and exact-value contracts; no PEAR/MDB2 loaded\n";

require __DIR__ . '/prepared_repository_checks.php';

require __DIR__ . '/permissions_toolbar_checks.php';
require __DIR__ . '/classification_checks.php';
require __DIR__ . '/schema_checks.php';
require __DIR__ . '/module_repository_checks.php';
require __DIR__ . '/catalogue_upgrade_checks.php';
require __DIR__ . '/maintenance_checks.php';
require __DIR__ . '/content_expiry_checks.php';
require __DIR__ . '/security_repository_checks.php';
