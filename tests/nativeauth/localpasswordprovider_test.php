<?php
/**
 * Modern-only local password provider regression test.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
define('MDB2_PREPARE_RESULT', 1);
define('MDB2_PREPARE_MANIP', 2);
define('MDB2_FETCHMODE_ASSOC', 2);

$base = dirname(__FILE__)
    . '/../../app/core_modules/security/classes/nativeauth/';
require_once $base . 'mdb2nativedatabaseadapter.php';
require_once $base . 'nativeuserrepository.php';
require_once $base . 'nativepasswordverifier.php';
require_once $base . 'localpasswordprovider.php';

function v99assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

class V99Result
{
    private $rows;
    public function __construct(array $rows) { $this->rows = $rows; }
    public function fetchRow($mode) {
        return empty($this->rows) ? false : array_shift($this->rows);
    }
    public function fetchAll($mode) { return $this->rows; }
    public function free() {}
}

class V99Statement
{
    private $connection;
    private $sql;
    public function __construct($connection, $sql) {
        $this->connection = $connection;
        $this->sql = $sql;
    }
    public function execute($parameters) {
        return $this->connection->executePrepared($this->sql, $parameters);
    }
    public function free() {}
}

class V99Connection
{
    private $users;
    public $updates = 0;
    public function __construct(array $users) { $this->users = $users; }
    public function prepare($sql, $types = null, $mode = null) {
        return new V99Statement($this, $sql);
    }
    public function executePrepared($sql, $parameters) {
        if (stripos($sql, 'UPDATE ') === 0) {
            $this->updates++;
            return 1;
        }
        foreach ($this->users as $user) {
            if (isset($parameters[0])
                && ($parameters[0] === $user['username']
                    || $parameters[0] === $user['userid'])) {
                return new V99Result(array($user));
            }
        }
        return new V99Result(array());
    }
}

function v99row($id, $username, $hash)
{
    return array(
        'id' => 'row-' . $id,
        'userid' => $id,
        'username' => $username,
        'pass' => $hash,
        'isactive' => '1',
        'puid' => '1',
        'emailaddress' => $username . '@example.test',
        'firstname' => 'Test',
        'surname' => 'User',
        'accesslevel' => '1',
        'howcreated' => 'local',
        'logins' => '0',
        'last_login' => null,
    );
}

$modern = password_hash('correct-password', PASSWORD_DEFAULT);
$legacy = 'b03ddf3ca2e714a6548e491607f89281ff6ab6db';
$connection = new V99Connection(array(
    v99row('u-modern', 'modern', $modern),
    v99row('u-legacy', 'legacy', $legacy),
));
$repository = new NativeUserRepository(
    new Mdb2NativeDatabaseAdapter($connection),
    false
);
$verifier = new NativePasswordVerifier();
$provider = new LocalPasswordProvider($repository, $verifier);

$success = $provider->authenticate('modern', 'correct-password');
v99assert($success->isSuccess(), 'modern password_hash credential authenticates');
v99assert($success->getUserId() === 'u-modern',
    'modern credential returns canonical user ID');
v99assert($success->getMetadata()['password_rehash_required'] === false,
    'current modern credential needs no rehash');

$wrong = $provider->authenticate('modern', 'wrong-password');
v99assert(!$wrong->isSuccess(), 'incorrect modern password is rejected');

$legacyResult = $provider->authenticate('legacy', 'correct-password');
v99assert(!$legacyResult->isSuccess(),
    'legacy SHA-1 credential is rejected without migration');
v99assert($verifier->identifyHashScheme($legacy) === 'unknown',
    'legacy SHA-1 credential is outside the supported scheme');

echo "ALL MODERN-ONLY LOCAL PASSWORD PROVIDER TESTS PASSED\n";
