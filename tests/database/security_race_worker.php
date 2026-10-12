<?php
/** Child process, used only inside the isolated fixture network. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_DB_BASELINE') !== '1') exit(64);
$app = '/framework/app';
set_include_path($app . PATH_SEPARATOR . get_include_path());
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject {}
require_once $app . '/classes/core/dbtable_class_inc.php';
$dsn = ['phptype'=>'mysqli','hostspec'=>'baseline-db','username'=>'root','password'=>getenv('CHISIMBA_DB_PASSWORD'),'database'=>'contract_fixture'];
if ($argv[1] !== 'PDO') throw new InvalidArgumentException('Only PDO is supported');
$db = ChisimbaDatabaseConnection::fromLegacyDsn($dsn);
$table = new dbTable();
(new ReflectionProperty(dbTable::class, '_db'))->setValue($table, $db);
require_once $app . '/core_modules/security/classes/nativeauth/dbtablemfarepository.php';
require_once $app . '/core_modules/security/classes/nativeauth/dbtablepersistentloginrepository.php';
if ($argv[2] === 'recovery') {
    $value = (new DbTableMfaRepository($table))->consumeRecoveryCode('race-user', 'race-code', time());
} else {
    $id = 'race-replacement-' . $argv[3];
    $value = (new DbTablePersistentLoginRepository($table))->rotate('race-old', [
        'id'=>$id,'user_id'=>'race-user','selector'=>$id,'verifier_hash'=>'fixture',
        'issued_at'=>time(),'expires_at'=>time()+3600,
    ], time());
}
echo $value ? '1' : '0';
