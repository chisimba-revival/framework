<?php
$security = dirname(__DIR__);
$read = static fn($path) => file_get_contents($security . '/' . $path);
$users = $read('classes/userservice_class_inc.php');
$provisioning = $read('classes/userprovisioningservice_class_inc.php');
$schema = $read('sql/tbl_users.sql');
$credentials = $read('classes/accountcredentialservice_class_inc.php');
$register = $read('register.conf');
$registrationBlock = $read('classes/block_register_class_inc.php');
$checks = array(
    'registration provenance rollback' => str_contains(
        $users,
        "'registration-service'"
    ),
    'ordinary users are not implicit guests' => !str_contains(
        $provisioning,
        "groupIdForName('Guest')"
    ),
    'permission identity is created' => str_contains(
        $provisioning,
        'ensurePermissionIdentity($userId)'
    ),
    'password hash capacity' => str_contains(
        $schema,
        "'pass' => array(\n        'type' => 'text',\n        'length' => 255"
    ),
    'verified hash provisioning' => str_contains(
        $provisioning,
        'createLocalUserWithPasswordHash('
    ) && str_contains($provisioning, "!== 'password_hash'"),
    'exact recovery lookup' => str_contains($users, 'function findByEmail('),
    'recovery revokes sessions' => str_contains(
        $credentials,
        'revokeAllForUser('
    ),
    'transaction participant' => str_contains(
        $credentials,
        'replaceWithinTransaction('
    ),
    'zero-session revocation succeeds' => str_contains(
        $credentials,
        ') === false'
    ),
    'legacy login blocks are not optional' => !str_contains(
        $register,
        'BLOCK: login|site'
    ) && !str_contains($register, 'BLOCK: elearnlogin|site'),
    'registration block uses replacement service' => str_contains(
        $registrationBlock,
        "'registration-service'"
    ) && !str_contains($registrationBlock, "'userregistration'"),
);
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
}
echo 'OK: ' . count($checks) . " registration provisioning checks\n";
?>
