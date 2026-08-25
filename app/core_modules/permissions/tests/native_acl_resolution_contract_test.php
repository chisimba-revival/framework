<?php

$aclPath = dirname(__DIR__) . '/classes/permissions_acl_class_inc.php';
$modelPath = dirname(__DIR__) . '/classes/permissions_model_class_inc.php';
$registrationPath = dirname(__DIR__) . '/register.conf';
$acl = file_get_contents($aclPath);
$model = file_get_contents($modelPath);
$registration = file_get_contents($registrationPath);

$checks = array(
    'disabled ACL reader is removed' => strpos($acl, 'function getUserAcls( $userId )' . "\n    {\n\n        return array();") === false,
    'logical identity is resolved canonically' => strpos($acl, 'permissionUserIdForUser($logicalUserId)') !== false,
    'canonical membership table is used' => strpos($acl, 'tbl_perms_groupusers') !== false,
    'canonical subgroup table is used' => strpos($acl, 'tbl_perms_group_subgroups') !== false,
    'ancestor traversal is bounded' => strpos($acl, '$depth < 32') !== false,
    'ACL identifiers are distinct' => strpos($acl, 'SELECT DISTINCT acl_id') !== false,
    'permissions model passes logical user ID' => strpos($model, 'getUserAcls( $userId )') !== false,
    'permissions model no longer passes storage PK' => strpos($model, 'getUserAcls( $userPkId )') === false,
    'fresh ACL checks bypass stale login snapshots' => strpos($model, 'function checkAclByNameFresh') !== false
        && strpos($model, '$this->getUserAcls($userId)') !== false,
    'identity dependency is declared' => strpos($registration, 'DEPENDS: security') !== false,
);

$failed = array_keys(array_filter($checks, function ($passed) { return !$passed; }));
if (!empty($failed)) {
    fwrite(STDERR, "Failed ACL contract checks:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo count($checks) . " native ACL resolution contract checks passed.\n";
