<?php
/** Same dbTable/authentication adapter checks under both real drivers. */
require_once $app . '/core_modules/security/classes/nativeauth/dbtablenativedatabaseadapter.php';
$adapter = new DbTableNativeDatabaseAdapter($table);
$name = $table->_tableName;
$payload = "bound ' OR 1=1 -- é 中文";
ensureBaseline($adapter->execute("INSERT INTO $name (id, body, quantity) VALUES (?, ?, ?)", ['prepared', $payload, 7]) === 1, 'Prepared insert failed');
ensureBaseline($adapter->fetchOne("SELECT body FROM $name WHERE id = ?", ['prepared']) === ['body' => $payload], 'Prepared adapter roundtrip failed');
ensureBaseline($adapter->fetchOne("SELECT id FROM $name WHERE id = ?", ["prepared' OR 1=1 --"]) === null, 'Binding allowed injection');
ensureBaseline($adapter->execute("UPDATE $name SET quantity = ? WHERE id = ? AND quantity = ?", [8, 'prepared', 7]) === 1, 'CAS winner failed');
ensureBaseline($adapter->execute("UPDATE $name SET quantity = ? WHERE id = ? AND quantity = ?", [9, 'prepared', 7]) === 0, 'CAS reuse accepted');
ensureBaseline($adapter->fetchAll("SELECT '' AS EMPTY, ' x ' AS PADDED, NULL AS ABSENT") === [['empty' => null, 'padded' => ' x', 'absent' => null]], 'Prepared portability changed');
try {
    $adapter->execute("INSERT INTO $name (id, body) VALUES (?, ?)", ['prepared', 'private-secret']);
    throw new LogicException('Duplicate unexpectedly accepted');
} catch (RuntimeException $error) {
    ensureBaseline(!str_contains((string)$error, 'private-secret'), 'Prepared error leaked parameter');
}
ensureBaseline($adapter->execute("DELETE FROM $name WHERE id = ?", ['prepared']) === 1, 'Prepared cleanup failed');
$base = $app . '/core_modules/security/classes/nativeauth/';
require_once $base . 'nativeuserrepository.php';
require_once $base . 'nativepasswordverifier.php';
require_once $base . 'localpasswordprovider.php';
$table->executePrepared('CREATE TABLE tbl_users (id VARCHAR(64), userid VARCHAR(64), username VARCHAR(64), pass VARCHAR(255), isactive INT, puid INT, emailaddress VARCHAR(100), firstname VARCHAR(64), surname VARCHAR(64), accesslevel INT, howcreated VARCHAR(64), logins INT, last_login DATETIME) ENGINE=InnoDB');
$table->executePrepared('INSERT INTO tbl_users (id,userid,username,pass,isactive) VALUES (?,?,?,?,?)', ['row-fixture','fixture','fixture',password_hash('fixture-password', PASSWORD_DEFAULT),1]);
$provider = new LocalPasswordProvider(new NativeUserRepository($adapter), new NativePasswordVerifier());
ensureBaseline($provider->authenticate('fixture', 'fixture-password')->isSuccess(), 'Real database password authentication failed');
ensureBaseline(!$provider->authenticate('fixture', 'wrong')->isSuccess(), 'Wrong password authenticated');
ensureBaseline(!$provider->authenticate("fixture' OR 1=1 --", 'fixture-password')->isSuccess(), 'Injected identity authenticated');
$table->executePrepared('UPDATE tbl_users SET isactive = 0');
ensureBaseline(!$provider->authenticate('fixture', 'fixture-password')->isSuccess(), 'Inactive identity authenticated');
$table->executePrepared('DROP TABLE tbl_users');
