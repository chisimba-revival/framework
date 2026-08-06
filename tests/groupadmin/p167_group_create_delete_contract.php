<?php
/**
 * Static contract for canonical group creation and deletion.
 *
 * @author Derek Keats
 */
$service = file_get_contents(
    dirname(__DIR__, 2)
    . '/app/core_modules/groupadmin/classes/groupservice_class_inc.php'
);
if ($service === false) {
    fwrite(STDERR, "Unable to read GroupService.\n");
    exit(1);
}

$required = array(
    'public function createGroup(',
    'public function deleteGroup(',
    '$this->assertAdministrator();',
    '$this->objUser->beginTransaction();',
    '$this->objUser->commitTransaction();',
    '$this->objUser->rollbackTransaction();',
    "DELETE FROM tbl_perms_groupusers WHERE group_id = ",
    "DELETE FROM tbl_perms_group_subgroups",
    "DELETE FROM tbl_perms_grouprights WHERE group_id = ",
    "DELETE FROM tbl_perms_groups WHERE group_id = ",
    "'code' => 'group_has_children'",
    "'code' => 'protected_group'",
    'tbl_permissions_acl',
);
foreach ($required as $needle) {
    if (strpos($service, $needle) === false) {
        fwrite(STDERR, "Missing contract marker: {$needle}\n");
        exit(1);
    }
}
if (preg_match('/DELETE\\s+FROM\\s+tbl_permissions_acl/i', $service)) {
    fwrite(STDERR, "Ambiguous legacy ACL rows must not be deleted by p167.\n");
    exit(1);
}
echo "p167 canonical group create/delete contract: PASS\n";
