<?php
/** Behavioural API persistence checks on a disposable PDO database only.
 * @author Derek Keats
 */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_DB_BASELINE') !== '1') exit(64);
$app = '/framework/app';
set_include_path($app . PATH_SEPARATOR . get_include_path());
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject {
    public static array $services = [];
    public function getObject($name, $module = '') { return self::$services[$name]; }
}
require_once $app . '/classes/core/dbtable_class_inc.php';
require_once $app . '/core_modules/security/classes/tokenauthservice_class_inc.php';
require_once $app . '/core_modules/security/classes/signingkeyservice_class_inc.php';
require_once $app . '/core_modules/security/classes/userservice_class_inc.php';
require_once $app . '/core_modules/security/classes/identityservice_class_inc.php';
require_once $app . '/core_modules/security/classes/permissionservice_class_inc.php';
class controller extends ChisimbaObject {}
require_once $app . '/core_modules/restapi/classes/restcontroller_class_inc.php';
class ApiGuardFixture extends RestController {
    public function __construct($auth) { $this->auth = $auth; $this->requestMethod = 'GET'; $this->routeParams = ['resource'=>'files']; }
    public function authenticate() { $this->requireAuth(); }
    public function permitted($right) { return $this->can($right); }
    protected function primeSessionUser(array $record): void {} // No browser/session in this repository fixture.
}

require_once $app . '/core_modules/restapi/classes/restschema_class_inc.php';
$db = ChisimbaDatabaseConnection::fromLegacyDsn(['phptype'=>'mysqli','hostspec'=>'baseline-db:3306',
    'username'=>'root','password'=>getenv('CHISIMBA_DB_PASSWORD'),'database'=>'contract_fixture']);
function bound($class) {
    global $db;
    $object = new $class();
    (new ReflectionProperty(dbTable::class, '_db'))->setValue($object, $db);
    $object->dbType = 'mysqli'; $object->dbLayer = 'PDO'; $object->_serverName = 'fixture';
    return $object;
}
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); }
$table = bound(dbTable::class);
$schema = bound(restschema::class);
$worker = ($argv[1] ?? '') === '--worker';
if (!$worker) {
$schema->upgrade(); $schema->upgrade();
include $app . '/core_modules/security/sql/tbl_users.sql';
(new ChisimbaDatabaseSchema($table))->createTable($tablename, $fields, $options);
$table->executePrepared('CREATE TABLE tbl_perms_perm_users (perm_user_id INT PRIMARY KEY, auth_user_id VARCHAR(25), auth_container_name VARCHAR(100)) ENGINE=InnoDB');
$table->executePrepared('CREATE TABLE tbl_perms_groups (puid INT PRIMARY KEY, group_id INT, group_define_name VARCHAR(100)) ENGINE=InnoDB');
$table->executePrepared('CREATE TABLE tbl_perms_groupusers (perm_user_id INT, group_id INT) ENGINE=InnoDB');
$table->executePrepared("INSERT INTO tbl_users (puid,userid,username,isactive) VALUES (88, '123', 'fixture', 1)");
$table->executePrepared("INSERT INTO tbl_perms_perm_users VALUES (77, '123', 'database_local')");
$table->executePrepared("INSERT INTO tbl_perms_groups VALUES (99, 9, 'north^Guide')");
$table->executePrepared('INSERT INTO tbl_perms_groupusers VALUES (77,9)');
$table->executePrepared('CREATE TABLE tbl_perms_areas (area_id INT, application_id VARCHAR(100), area_define_name VARCHAR(100)) ENGINE=InnoDB');
$table->executePrepared('CREATE TABLE tbl_perms_rights (right_id INT, area_id INT, right_define_name VARCHAR(100)) ENGINE=InnoDB');
$table->executePrepared('CREATE TABLE tbl_perms_userrights (id VARCHAR(25), perm_user_id INT, right_id INT) ENGINE=InnoDB');
$table->executePrepared('CREATE TABLE tbl_perms_grouprights (id VARCHAR(25), group_id INT, right_id INT) ENGINE=InnoDB');
$table->executePrepared("INSERT INTO tbl_perms_areas VALUES (3,'chisimba','restapi')");
$table->executePrepared("INSERT INTO tbl_perms_rights VALUES (4,3,'files.view')");

}
ChisimbaObject::$services['userservice'] = bound(userservice::class);
ChisimbaObject::$services['identityservice'] = bound(identityservice::class);
$permissions = bound(permissionservice::class);
(new ReflectionProperty(permissionservice::class, 'objIdentityService'))->setValue($permissions, ChisimbaObject::$services['identityservice']);
ChisimbaObject::$services['permissionservice'] = $permissions;
$tokens = bound(TokenAuthService::class);
$key = $worker ? getenv('CHISIMBA_JWKS_KEY_PATH') : sys_get_temp_dir() . '/restapi-test-' . bin2hex(random_bytes(6)) . '/key.pem';
if (!$worker) mkdir(dirname($key), 0700, true);
putenv('CHISIMBA_JWKS_KEY_PATH=' . $key);
$keys = new SigningKeyService();
$publisher = new class {
    public bool $fail = false;
    public array $events = [];
    public function publish($id, $epoch) {
        if ($this->fail) throw new RuntimeException('Synthetic Redis failure');
        $this->events[] = [$id,$epoch];
    }
};
(new ReflectionProperty(TokenAuthService::class, 'signingKeys'))->setValue($tokens, $keys);
(new ReflectionProperty(TokenAuthService::class, 'denylist'))->setValue($tokens, $publisher);
if ($worker) {
    $request = json_decode(stream_get_contents(STDIN), true);
    echo $tokens->refreshAccessToken($request['token'], 'race') === null ? '0' : '1';
    exit;
}
try {
    putenv('CHISIMBA_JWKS_KEY_PATH=' . $app . '/exposed.pem');
    try { (new SigningKeyService())->keyPath(); throw new LogicException('Web-root private key accepted'); }
    catch (RuntimeException $expected) { check(str_contains($expected->getMessage(), 'outside'), 'Unexpected path error'); }
    putenv('CHISIMBA_JWKS_KEY_PATH=' . $key);
    $jobs = [];
    for ($i=0; $i<2; ++$i) {
        $code = 'require ' . var_export($app . '/core_modules/security/classes/signingkeyservice_class_inc.php', true) . '; echo (new SigningKeyService())->keyId();';
        $process = proc_open([PHP_BINARY, '-d','auto_prepend_file=', '-r',$code], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes);
        fclose($pipes[0]); $jobs[] = [$process,$pipes];
    }
    $ids = [];
    foreach ($jobs as [$process,$pipes]) {
        $ids[] = stream_get_contents($pipes[1]); $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        check(proc_close($process) === 0 && $errors === '', 'Signing-key worker failed');
    }
    check($ids[0] !== '' && $ids[0] === $ids[1], 'Concurrent first use generated different keys');
    check((fileperms($key) & 0777) === 0600, 'Private key is not owner-only');
    $pair = $tokens->issueTokens('123', ['device_id'=>'phone']);
    $claims = $tokens->validateJWT($pair['access_token'])[1];
    check($claims['sub'] === '123' && $claims['ctxs'] === ['north'] && $claims['roles'] === ['Guide'], 'Logical/permission identity mapping failed');
    check($claims['type'] === 'access' && $claims['ver'] === $claims['jti'] && in_array('field:write',$claims['scope']), 'Field client token contract changed');
    check($tokens->validateJWT($pair['refresh_token']) === null, 'Refresh token accepted as access');
    $service = explode('.', $tokens->issueServiceToken('fixture'));
    $payload = json_decode(base64_decode(strtr($service[1], '-_', '+/')),true);
    check($payload['scope'] === ['field:write'] && $payload['type'] === 'service', 'Service contract changed');
    $guard = new ApiGuardFixture($tokens);
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $pair['access_token'];
    $guard->authenticate();
    check(!$guard->permitted('files.view'), 'Zero-grant user authorised');
    $table->executePrepared("INSERT INTO tbl_perms_userrights VALUES ('grant',77,4)");
    check($guard->permitted('files.view'), 'Persisted grant not honoured');
    $table->executePrepared('DELETE FROM tbl_perms_userrights');
    check(!$guard->permitted('files.view'), 'Revoked permission still accepted');
    $table->executePrepared("UPDATE tbl_users SET isactive=0 WHERE userid='123'");
    try { $guard->authenticate(); throw new LogicException('Deactivated account accepted'); }
    catch (AuthenticationException $expected) {}
    $table->executePrepared("UPDATE tbl_users SET isactive=1 WHERE userid='123'");
    $rotated = $tokens->refreshAccessToken($pair['refresh_token'], 'phone');
    check(is_array($rotated), 'Refresh failed');
    check($tokens->refreshAccessToken($pair['refresh_token'], 'phone') === null, 'Replay accepted');
    check($tokens->refreshAccessToken($rotated['refresh_token'], 'phone') === null, 'Replay family remained live');
    $pair = $tokens->issueTokens('123', ['device_id'=>'phone']);
    $table->executePrepared("CREATE TRIGGER fail_refresh BEFORE INSERT ON tbl_rest_refresh_tokens FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic failure'");
    try { $tokens->refreshAccessToken($pair['refresh_token'], 'phone'); throw new LogicException('Failed insert accepted'); }
    catch (ChisimbaDatabaseException $expected) {}
    $table->executePrepared('DROP TRIGGER fail_refresh');
    check(is_array($tokens->refreshAccessToken($pair['refresh_token'], 'phone')), 'Failed rotation consumed original token');
    $pair = $tokens->issueTokens('123', ['device_id'=>'phone']);
    check($tokens->refreshAccessToken($pair['refresh_token'], 'other') === null, 'Wrong device accepted');
    $pair = $tokens->issueTokens('123', ['device_id'=>'race']);
    $jobs = [];
    for ($i=0; $i<2; ++$i) {
        $process = proc_open([PHP_BINARY, '-d', 'auto_prepend_file=', __FILE__, '--worker'], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes);
        check(is_resource($process), 'Could not start race worker');
        fwrite($pipes[0], json_encode(['token'=>$pair['refresh_token']])); fclose($pipes[0]);
        $jobs[] = [$process,$pipes];
    }
    $results = [];
    foreach ($jobs as [$process,$pipes]) {
        $results[] = stream_get_contents($pipes[1]); $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        check(proc_close($process) === 0 && $errors === '', 'Race worker failed: '.$errors);
    }
    sort($results);
    check($results === ['0','1'], 'Concurrent refresh minted more than one replacement');
    $apiKey = $tokens->createApiKey('123', 'fixture', ['files.view']);
    check($apiKey['id'] > 0 && $tokens->validateApiKeyDetailed($apiKey['key'])['scopes'] === ['files.view'], 'API key persistence failed');
    check(!$tokens->revokeApiKey('outsider', $apiKey['id']), 'Cross-user key revocation accepted');
    check($tokens->revokeApiKey('123', $apiKey['id']) && $tokens->validateApiKeyDetailed($apiKey['key']) === null, 'Key revocation failed');
    $table->executePrepared("UPDATE tbl_perms_groups SET group_define_name='north^Student' WHERE group_id=9");
    $publisher->fail = true;
    try { $tokens->syncEpochFor('123'); throw new LogicException('Publication failure ignored'); }
    catch (RuntimeException $expected) { check($expected->getMessage()==='Synthetic Redis failure','Unexpected error'); }
    check($tokens->epochFor('123') === 0, 'Failed publication committed its fingerprint');
    $publisher->fail = false;
    check($tokens->syncEpochFor('123') === 1 && $publisher->events === [['123',1]], 'Publication retry lost revocation');
    check($tokens->syncEpochFor('123') === 1, 'Unchanged grant raised epoch');
    $table->transaction(function () use ($tokens,$table) {
        $tokens->issueTokens('123', ['device_id'=>'nested']);
        check($table->hasActiveTransaction(), 'Nested call committed owner transaction');
    });
    check(!class_exists('MDB2',false) && !class_exists('PEAR',false), 'API loaded retired runtime');
    echo "PASS: REST API schema repeatability, identity, signed claims, key ownership, refresh/replay/rollback and revocation retry\n";
} finally {
    if (is_file($key)) unlink($key);
    if (is_dir(dirname($key))) { foreach (glob(dirname($key).'/*') as $f) unlink($f); rmdir(dirname($key)); }
}
