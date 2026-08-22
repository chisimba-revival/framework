<?php
$security = dirname(__DIR__);
$read = static fn($path) => file_get_contents($security . '/' . $path);
$users = $read('classes/userservice_class_inc.php');
$provisioning = $read('classes/userprovisioningservice_class_inc.php');
$schema = $read('sql/tbl_users.sql');
$checks = array(
    'registration provenance rollback' => str_contains(
        $users,
        "'registration-service'"
    ),
    'non-admin provisioned membership' => str_contains(
        $provisioning,
        'ensureMembership('
    ),
    'permission identity membership' => str_contains(
        $provisioning,
        "\$permissionUserId\n            );"
    ),
    'non-admin compensation' => str_contains(
        $provisioning,
        'removeMembership('
    ),
    'password hash capacity' => str_contains(
        $schema,
        "'pass' => array(\n        'type' => 'text',\n        'length' => 255"
    ),
    'verified hash provisioning' => str_contains(
        $provisioning,
        'createLocalUserWithPasswordHash('
    ) && str_contains($provisioning, "!== 'password_hash'"),
);
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
}
echo 'OK: ' . count($checks) . " registration provisioning checks\n";
?>
