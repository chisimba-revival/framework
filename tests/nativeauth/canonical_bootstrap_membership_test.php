<?php
$root = dirname(dirname(__DIR__));
$group = file_get_contents(
    $root . '/app/core_modules/groupadmin/classes/groupservice_class_inc.php'
);
$user = file_get_contents(
    $root . '/app/core_modules/security/classes/userprovisioningservice_class_inc.php'
);
$initial = file_get_contents(
    $root . '/app/core_modules/security/classes/initialadminprovisioningservice_class_inc.php'
);

function v108Assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . "\n");
        exit(1);
    }
    echo "PASS: " . $message . "\n";
}

v108Assert(
    strpos($group, 'public function addBootstrapMember(') !== false
        && strpos($group, 'public function removeBootstrapMember(') !== false,
    'GroupService owns add and compensation bootstrap capabilities'
);
v108Assert(
    strpos($group, "'firstreg_run'") !== false
        && strpos($group, "'modulecatalogue'") !== false
        && strpos($group, "array('Guest', 'Site Admin')") !== false
        && strpos($group, "(string) \$userId !== '1'") !== false,
    'bootstrap capability is closed by install state, user and group allow-lists'
);
v108Assert(
    substr_count($group, '$this->assertAdministrator();') >= 2,
    'ordinary membership mutations retain administrator authorization'
);
v108Assert(
    strpos($user, "addBootstrapMember(\n                \$guestGroupId")
        !== false
        && strpos($initial, "addBootstrapMember(\n            \$groupId")
        !== false,
    'only canonical provisioning services use bootstrap membership'
);
v108Assert(
    strpos($user, 'removeBootstrapMember(') !== false,
    'failed bootstrap provisioning has canonical compensation'
);

$production = array($group, $user, $initial);
foreach ($production as $source) {
    v108Assert(
        !preg_match('/(?:INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+tbl_perms_groupusers/i',
            $source)
            || $source === $group,
        'membership SQL remains confined to GroupService'
    );
}
