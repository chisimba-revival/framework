<?php
define('MDB2_PREPARE_RESULT', 1);
define('MDB2_PREPARE_MANIP', 2);
define('MDB2_FETCHMODE_ASSOC', 2);

$base = dirname(__FILE__) . '/../../app/core_modules/security/classes/nativeauth/';
require_once $base . 'mdb2nativedatabaseadapter.php';
require_once $base . 'nativeuserrepository.php';
require_once $base . 'nativepasswordverifier.php';
require_once $base . 'localpasswordprovider.php';
require_once $base . 'authenticationproviderregistry.php';
require_once $base . 'nativeauthenticationservice.php';
require_once $base . 'legacyauthsessionbridge.php';

function check($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

class WiringResult
{
    private $rows;
    public function __construct(array $rows) { $this->rows = $rows; }
    public function fetchRow($mode) {
        return empty($this->rows) ? false : array_shift($this->rows);
    }
    public function fetchAll($mode) { return $this->rows; }
    public function free() {}
}

class WiringStatement
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

class WiringConnection
{
    private $user;
    public function __construct(array $user) { $this->user = $user; }
    public function prepare($sql, $types = null, $mode = null) {
        return new WiringStatement($this, $sql);
    }
    public function executePrepared($sql, $parameters) {
        if (stripos($sql, 'UPDATE ') === 0) {
            return 1;
        }
        $matchesUsername = isset($parameters[0])
            && $parameters[0] === $this->user['username'];
        $matchesId = isset($parameters[0])
            && $parameters[0] === $this->user['userid'];

        if (!$matchesUsername && !$matchesId) {
            return new WiringResult(array());
        }

        return new WiringResult(array(array(
            'id' => 'row-1',
            'userid' => $this->user['userid'],
            'username' => $this->user['username'],
            'pass' => $this->user['pass'],
            'isactive' => '1',
            'puid' => '1',
            'emailaddress' => $this->user['emailaddress'],
            'firstname' => $this->user['firstname'],
            'surname' => $this->user['surname'],
            'accesslevel' => '1',
            'howcreated' => 'local',
            'logins' => '0',
            'last_login' => null,
        )));
    }
}

$connection = new WiringConnection(array(
    'userid' => 'u-1',
    'username' => 'derek',
    'pass' => password_hash('correct-password', PASSWORD_DEFAULT),
    'emailaddress' => 'derek@example.test',
    'firstname' => 'Derek',
    'surname' => 'Keats',
));

$repository = new NativeUserRepository(
    new Mdb2NativeDatabaseAdapter($connection),
    false
);
$provider = new LocalPasswordProvider(
    $repository,
    new NativePasswordVerifier()
);
$service = new NativeAuthenticationService(
    new AuthenticationProviderRegistry(array($provider)),
    new LegacyAuthSessionBridge()
);

$success = $service->authenticate('local', 'derek', 'correct-password');
check($success->isSuccess(), 'native stack accepts a modern password_hash credential');
check($success->getUserId() === 'u-1', 'canonical user ID survives full wiring');
check(
    $success->getMetadata()['password_hash_scheme'] === 'password_hash',
    'modern password hash scheme is detected'
);
check(
    $success->getMetadata()['password_rehash_required'] === false,
    'current password hash needs no rehash'
);

$failure = $service->authenticate('local', 'derek', 'wrong-password');
check(!$failure->isSuccess(), 'native stack rejects an invalid password');

check(
    !$service->establishAuthenticatedSession($success),
    'compatibility bridge prevents native session establishment during rollout'
);

echo "ALL FEATURE-FLAGGED NATIVE LOGIN WIRING TESTS PASSED\n";
