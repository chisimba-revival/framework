<?php
/** Real dbTable repositories on synthetic InnoDB fixtures under both drivers. */
require_once $app . '/core_modules/security/classes/nativeauth/dbtablemfarepository.php';
require_once $app . '/core_modules/security/classes/nativeauth/dbtablepersistentloginrepository.php';
require_once $app . '/core_modules/abuseprotection/classes/dbtableabuseeventrepository.php';
$table->executePrepared('CREATE TABLE tbl_auth_mfa_enrolments (id VARCHAR(64) PRIMARY KEY,user_id VARCHAR(64),factor_type VARCHAR(16),encrypted_secret TEXT,secret_nonce TEXT,enrolled_at DATETIME,verified_at DATETIME,last_accepted_step BIGINT,disabled_at DATETIME) ENGINE=InnoDB');
$table->executePrepared('CREATE TABLE tbl_auth_mfa_recovery_codes (id VARCHAR(64) PRIMARY KEY,user_id VARCHAR(64),code_hash VARCHAR(255),created_at DATETIME,used_at DATETIME, INDEX(user_id)) ENGINE=InnoDB');
$table->executePrepared('CREATE TABLE tbl_auth_persistent_logins (id VARCHAR(64) PRIMARY KEY,user_id VARCHAR(64),selector VARCHAR(64) UNIQUE,verifier_hash VARCHAR(255),issued_at DATETIME,expires_at DATETIME,last_used_at DATETIME,revoked_at DATETIME,replaced_by_id VARCHAR(64)) ENGINE=InnoDB');
$table->executePrepared('CREATE TABLE tbl_abuse_events (id VARCHAR(64) PRIMARY KEY,action_key VARCHAR(64),subject_hash VARCHAR(64),outcome VARCHAR(32),occurred_at DATETIME,expires_at DATETIME) ENGINE=InnoDB');
$mfa = new DbTableMfaRepository($table);
$now = time();
ensureBaseline($mfa->storePendingTotp(['id'=>'mfa','user_id'=>'u','encrypted_secret'=>'cipher','secret_nonce'=>'nonce','enrolled_at'=>$now]), 'MFA insert');
ensureBaseline($mfa->findActiveTotpForUser('u') === null && $mfa->findPendingTotpById('mfa', 'other') === null, 'MFA pending/ownership');
ensureBaseline($mfa->verifyPendingTotp('mfa', $now) && !$mfa->verifyPendingTotp('mfa', $now), 'MFA verify once');
ensureBaseline($mfa->findActiveTotpForUser('u')->ciphertext === 'cipher', 'MFA active read');
ensureBaseline($mfa->acceptTotpStep('mfa', 10, $now) && !$mfa->acceptTotpStep('mfa', 10, $now) && !$mfa->acceptTotpStep('mfa', 9, $now), 'TOTP replay rejected');
$codes = [['id'=>'c1','code_hash'=>password_hash('code-one', PASSWORD_DEFAULT)]];
ensureBaseline($mfa->replaceRecoveryCodes('u', $codes, $now), 'Recovery storage');
try {
    $mfa->replaceRecoveryCodes('u', [['id'=>'new','code_hash'=>'hash'], ['id'=>'new','code_hash'=>'duplicate']], $now);
    throw new LogicException('Duplicate recovery IDs accepted');
} catch (RuntimeException $expected) {}
ensureBaseline($table->selectPrepared('SELECT id FROM tbl_auth_mfa_recovery_codes') === [['id'=>'c1']], 'Failed replacement destroyed old codes');
ensureBaseline(!$mfa->consumeRecoveryCode('u', 'wrong', $now), 'Wrong recovery accepted');
ensureBaseline($mfa->consumeRecoveryCode('u', 'code-one', $now) && !$mfa->consumeRecoveryCode('u', 'code-one', $now), 'Recovery must be single use');
ensureBaseline($mfa->disableTotpForUser('u', $now) === 1 && !$mfa->acceptTotpStep('mfa', 11, $now), 'Disabled MFA accepted');
$tokens = new DbTablePersistentLoginRepository($table);
$record = ['id'=>'old','user_id'=>'u','selector'=>'old-selector','verifier_hash'=>'fixture','issued_at'=>$now,'expires_at'=>$now+3600];
ensureBaseline($tokens->store($record), 'Remembered token store');
try {
    $tokens->rotate('old', $record, $now);
    throw new LogicException('Duplicate rotation accepted');
} catch (RuntimeException $expected) {}
ensureBaseline($tokens->findActiveBySelector('old-selector', $now) !== null, 'Failed rotation consumed old token');
// Force failure after the replacement insert, proving that both halves roll back.
$table->executePrepared("CREATE TRIGGER fail_rotation BEFORE UPDATE ON tbl_auth_persistent_logins FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'fixture rotation failure'");
try {
    $tokens->rotate('old', array_replace($record, ['id'=>'partial','selector'=>'partial']), $now);
    throw new LogicException('Failed rotation update accepted');
} catch (RuntimeException $expected) {}
$table->executePrepared('DROP TRIGGER fail_rotation');
ensureBaseline($tokens->findActiveBySelector('old-selector', $now) !== null
    && $tokens->findActiveBySelector('partial', $now) === null, 'Partial replacement survived rollback');
$new = array_replace($record, ['id'=>'new','selector'=>'new-selector']);
ensureBaseline($tokens->rotate('old', $new, $now), 'Rotation failed');
ensureBaseline(!$tokens->rotate('old', array_replace($record, ['id'=>'reuse','selector'=>'reuse']), $now), 'Token replay accepted');
ensureBaseline($tokens->findActiveBySelector('old-selector', $now) === null && $tokens->findActiveBySelector('new-selector', $now) !== null, 'Rotation visibility');
ensureBaseline($tokens->revoke('new', $now) && !$tokens->revoke('new', $now), 'Token revoke once');
// Nested repository calls must not commit or roll back a transaction they did not start.
$table->beginTransaction();
try {
    $mfa->replaceRecoveryCodes('u', $codes, $now);
    throw new RuntimeException('Foreign transaction accepted');
} catch (LogicException $expected) {}
$table->rollbackTransaction();
$table->transaction(function () use ($table) {
    try { $table->commitTransaction(); throw new RuntimeException('Legacy commit stole callback'); }
    catch (LogicException $expected) {}
});
$abuse = new DbTableAbuseEventRepository($table);
$event = ['id'=>'event','action_key'=>'login','subject_hash'=>'fixture','outcome'=>'failure','occurred_at'=>$now,'expires_at'=>$now+60];
ensureBaseline($abuse->record($event) && $abuse->countFailures('login', 'fixture', $now-1) === 1, 'Abuse event persistence');
$budget = array_replace($event, ['id'=>'budget','action_key'=>'budget','since'=>$now-1,'limit'=>1]);
ensureBaseline($abuse->reserveBudgets('fixture-budget', [$budget]), 'Budget admission');
ensureBaseline(!$abuse->reserveBudgets('fixture-budget', [array_replace($budget, ['id'=>'second'])]), 'Budget overrun');
ensureBaseline($table->selectPrepared('SELECT IS_FREE_LOCK(CONCAT(LEFT(DATABASE(),12), ?)) AS free', ['fixture-budget'])[0]['free'] === '1', 'Budget lock leaked');
// Independent PHP connections contend for the same one-time credential.
$mfa->replaceRecoveryCodes('race-user', [['id'=>'race-code','code_hash'=>password_hash('race-code', PASSWORD_DEFAULT)]], $now);
$tokens->store(array_replace($record, ['id'=>'race-old','selector'=>'race-old','user_id'=>'race-user']));
foreach (['recovery', 'rotation'] as $operation) {
    $jobs = [];
    for ($i = 0; $i < 2; ++$i) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, '-d', 'auto_prepend_file=', __DIR__ . '/security_race_worker.php',
            $table->dbLayer, $operation, (string)$i], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes);
        ensureBaseline(is_resource($process), 'Race worker failed to start');
        fclose($pipes[0]);
        $jobs[] = [$process, $pipes];
    }
    $outcomes = [];
    foreach ($jobs as [$process, $pipes]) {
        $outcomes[] = trim(stream_get_contents($pipes[1]));
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        ensureBaseline(proc_close($process) === 0 && $errors === '', 'Race worker failed');
    }
    sort($outcomes);
    ensureBaseline($outcomes === ['0','1'], 'Exactly one competing credential use must win');
}
foreach (['tbl_auth_mfa_enrolments','tbl_auth_mfa_recovery_codes','tbl_auth_persistent_logins','tbl_abuse_events'] as $fixture) $table->executePrepared("DROP TABLE $fixture");
echo "PASS: MFA/recovery/token rotation rollback and replay; two-worker races; checked transactions; abuse budgets\n";
// Drop a real connection after its write but before COMMIT. The caller must fail,
// and an independent observer must see the pre-transaction value.
$table->executePrepared('CREATE TABLE security_commit_probe (id INT PRIMARY KEY, value INT) ENGINE=InnoDB');
$table->executePrepared('INSERT INTO security_commit_probe VALUES (1,0)');
$observer = ChisimbaDatabaseConnection::fromLegacyDsn(['phptype'=>'mysqli','hostspec'=>'baseline-db','username'=>'root','password'=>getenv('CHISIMBA_DB_PASSWORD'),'database'=>'contract_fixture']);
$connectionId = (int)$table->selectPrepared('SELECT CONNECTION_ID() AS id')[0]['id'];
try {
    $table->transaction(function () use ($table, $observer, $connectionId) {
        $table->executePrepared('UPDATE security_commit_probe SET value=1 WHERE id=1');
        $observer->execute('KILL CONNECTION ' . $connectionId);
    });
    throw new LogicException('Lost connection reported commit success');
} catch (RuntimeException $expected) {}
ensureBaseline((int)$observer->fetchOne('SELECT value FROM security_commit_probe WHERE id=1')['value'] === 0, 'Failed commit persisted partial write');
$observer->execute('DROP TABLE security_commit_probe');
echo "PASS: disconnected commit fails and preserves original data\n";
