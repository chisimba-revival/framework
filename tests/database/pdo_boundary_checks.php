<?php
/** Additional native boundary assertions; loaded only by the isolated fixture. */
$dsn = ['phptype' => 'mysqli', 'hostspec' => 'baseline-db', 'username' => 'root',
    'password' => getenv('CHISIMBA_DB_PASSWORD'), 'database' => 'contract_fixture', 'charset' => 'utf8mb4'];
$native = ChisimbaDatabaseConnection::fromLegacyDsn($dsn);
$other = ChisimbaDatabaseConnection::fromLegacyDsn($dsn);
$native->execute('CREATE TABLE native_values (id INT PRIMARY KEY, body TEXT NULL, flag INT) ENGINE=InnoDB CHARSET=utf8mb4');
$value = "  Exact ' quote \\ newline\n emoji 🌍  ";
ensureBaseline($native->execute('INSERT INTO native_values VALUES (?, ?, ?)', [1, $value, true]) === 1, 'Native insert count');
$native->execute('INSERT INTO native_values VALUES (:id, :body, :flag)', ['id' => 2, 'body' => '', 'flag' => false]);
$native->execute('INSERT INTO native_values VALUES (?, ?, ?)', [3, null, null]);
ensureBaseline($native->fetchOne('SELECT body AS MiXeD FROM native_values WHERE id=?', [1]) === ['MiXeD' => $value], 'Native values/case must be exact');
ensureBaseline($native->fetchOne('SELECT body FROM native_values WHERE id=?', [2]) === ['body' => ''], 'Native empty string must survive');
ensureBaseline($native->fetchOne('SELECT body FROM native_values WHERE id=?', [3]) === ['body' => null], 'Native null must survive');
ensureBaseline($native->fetchOne('SELECT id FROM native_values WHERE id=?', [99]) === null, 'Missing native row');
ensureBaseline($native->execute('UPDATE native_values SET flag=1 WHERE id=1') === 0, 'No-op affected count');
ensureBaseline($native->execute('UPDATE native_values SET flag=0 WHERE id=1 AND flag=1') === 1, 'CAS affected count');
ensureBaseline($native->execute('UPDATE native_values SET flag=0 WHERE id=1 AND flag=1') === 0, 'Stale CAS affected count');

// Errors must not disclose SQL, parameter values, usernames or nested PDO errors.
try {
    $native->execute('INSERT INTO native_values VALUES (?, ?, ?)', [1, 'private-fixture-secret', 0]);
    throw new LogicException('Duplicate insert was accepted');
} catch (ChisimbaDatabaseException $error) {
    ensureBaseline($error->sqlState === '23000' && $error->getPrevious() === null
        && !str_contains((string)$error, 'private-fixture-secret'), 'Native errors must be redacted');
}
ensureBaseline($table->query('SELECT * FROM nonexistent_fixture_table') === false, 'dbTable read failure contract');
ensureBaseline($table->_execute('INSERT INTO pdo_contract_rows (id,body) VALUES (?,?)', ['bound', "not ' prequoted"]) === 1, 'dbTable _execute must bind parameters');
ensureBaseline($table->getRow('id', 'bound')['body'] === "not ' prequoted", 'dbTable parameters double quoted');
$table->USE_PREPARED_STATEMENTS = true;
ensureBaseline($table->update('id', 'bound', ['body' => "second ' value"]), 'Prepared dbTable update failed');
ensureBaseline($table->getRow('id', 'bound')['body'] === "second ' value", 'Prepared dbTable update changed value');
ensureBaseline($table->update('id', 'missing', ['body' => 'nothing']) === true, 'Zero-row update must be successful');
ensureBaseline($table->delete('id', "bound' OR 1=1 --") === true && $table->valueExists('id', 'bound'), 'Bound deletion escaped scope');
ensureBaseline($table->valueExists('id', "bound' OR 1=1 --") === false, 'Bound existence query escaped scope');
ensureBaseline(in_array('pdo_contract_rows', $table->listDbTables(), true), 'Table list failed');
ensureBaseline($table->query('SELECT ' . $table->getSubstring('id', 2, 3) . " AS part FROM pdo_contract_rows WHERE id='bound'") === [['part' => 'oun']], 'Substring expression failed');
ensureBaseline(count($table->getAll()) > 0 && count($table->fetchAll()) > 0, 'Shared list helpers failed');
ensureBaseline($table->getArrayWithLimit('SELECT id FROM pdo_contract_rows;', 0, 0) === [], 'Zero limit failed');
try { $table->getArrayWithLimit('SELECT id FROM pdo_contract_rows', -1, 1); throw new LogicException('Negative offset accepted'); }
catch (InvalidArgumentException $expected) {}
try { $native->quoteIdentifier('native_values; DROP TABLE native_values'); throw new LogicException('Unsafe identifier accepted'); }
catch (InvalidArgumentException $expected) {}

// A second dbTable sharing the connection cannot steal the first one's transaction.
$secondTable = new dbTable();
(new ReflectionProperty(dbTable::class, '_db'))->setValue($secondTable, $db);
$secondTable->dbLayer = 'PDO';
$table->beginTransaction();
try { $secondTable->beginTransaction(); throw new RuntimeException('Nested transaction accepted'); }
catch (LogicException $expected) {}
try { $secondTable->commitTransaction(); throw new RuntimeException('Foreign commit accepted'); }
catch (LogicException $expected) {}
try { $secondTable->rollbackTransaction(); throw new RuntimeException('Foreign rollback accepted'); }
catch (LogicException $expected) {}
ensureBaseline($db->inTransaction(), 'Foreign transaction operation disturbed owner');
$table->rollbackTransaction();
$native->beginTransaction();
$native->execute('UPDATE native_values SET flag=8 WHERE id=1');
ensureBaseline((int)$other->fetchOne('SELECT flag FROM native_values WHERE id=1')['flag'] === 0, 'Uncommitted write exposed');
$native->rollback();
ensureBaseline((int)$native->fetchOne('SELECT flag FROM native_values WHERE id=1')['flag'] === 0, 'Native rollback failed');
ensureBaseline((int)$native->fetchOne("SELECT GET_LOCK('native_boundary_lock', 0) AS acquired")['acquired'] === 1, 'Native lock failed');
ensureBaseline((int)$other->fetchOne("SELECT GET_LOCK('native_boundary_lock', 0) AS acquired")['acquired'] === 0, 'Native lock ownership failed');
ensureBaseline((int)$native->fetchOne("SELECT RELEASE_LOCK('native_boundary_lock') AS released")['released'] === 1, 'Native unlock failed');

ensureBaseline(ChisimbaDatabaseConnection::mysqlDsn(['phptype'=>'mysqli','hostspec'=>'db:3307','database'=>'app']) === 'mysql:host=db;port=3307;dbname=app;charset=utf8', 'Legacy port translation');
ensureBaseline(ChisimbaDatabaseConnection::mysqlDsn(['phptype'=>'mysql','hostspec'=>'[::1]:3308','database'=>'app']) === 'mysql:host=::1;port=3308;dbname=app;charset=utf8', 'IPv6 port translation');
ensureBaseline(ChisimbaDatabaseConnection::mysqlDsn(['phptype'=>'mysql','hostspec'=>'unix(/run/mysql.sock)','database'=>'app']) === 'mysql:unix_socket=/run/mysql.sock;dbname=app;charset=utf8', 'Socket translation');
foreach ([['phptype'=>'pgsql','database'=>'app'], ['phptype'=>'mysqli','database'=>'app;host=elsewhere'], ['phptype'=>'mysql','database'=>'app','port'=>'0']] as $invalid) {
    try { ChisimbaDatabaseConnection::mysqlDsn($invalid); throw new LogicException('Invalid DSN accepted'); }
    catch (InvalidArgumentException $expected) {}
}

// Connection failure is also redacted and never chains the driver's message.
try {
    ChisimbaDatabaseConnection::fromLegacyDsn(array_replace($dsn, ['username'=>'invalid_fixture_user', 'password'=>'private-fixture-secret']));
    throw new LogicException('Invalid database login accepted');
} catch (ChisimbaDatabaseException $error) {
    ensureBaseline($error->getPrevious() === null && !str_contains((string)$error, 'private-fixture-secret'), 'Connection error leaked credentials');
}
// Test direct literal SQL redaction even if PHP is configured to retain trace args.
ini_set('zend.exception_ignore_args', '0');
try {
    $native->query('private-fixture-secret invalid sql');
    throw new LogicException('Invalid SQL accepted');
} catch (ChisimbaDatabaseException $error) {
    ensureBaseline(!str_contains((string)$error, 'private-fixture-secret'), 'Exception trace leaked literal SQL');
}

$table->beginTransaction();
$db->commit(); // Deliberately simulate another layer ending the owner's transaction.
$secondTable->beginTransaction();
try { $table->commitTransaction(); throw new RuntimeException('Stale owner committed a new transaction'); }
catch (LogicException $expected) {}
ensureBaseline($db->inTransaction(), 'Stale ownership check disturbed the new transaction');
$secondTable->rollbackTransaction();

// Lose a fixture connection before commit: never report success or retain writes.
$victim = ChisimbaDatabaseConnection::fromLegacyDsn($dsn);
$victimId = (int)$victim->fetchOne('SELECT CONNECTION_ID() AS id')['id'];
$victim->beginTransaction();
$victim->execute('UPDATE native_values SET flag=9 WHERE id=1');
$other->execute('KILL CONNECTION ' . $victimId);
try { $victim->commit(); throw new LogicException('Disconnected commit reported success'); }
catch (ChisimbaDatabaseException $expected) {}
ensureBaseline((int)$other->fetchOne('SELECT flag FROM native_values WHERE id=1')['flag'] === 0, 'Disconnected transaction retained its writes');
