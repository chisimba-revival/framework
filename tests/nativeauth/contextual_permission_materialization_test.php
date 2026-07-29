<?php
$root = dirname(__DIR__, 2);
$service = file_get_contents(
    $root . '/app/core_modules/security/classes/'
    . 'permissionservice_class_inc.php'
);
$groups = file_get_contents(
    $root . '/app/core_modules/contextgroups/classes/'
    . 'managegroups_class_inc.php'
);
$schema = file_get_contents(
    $root . '/app/core_modules/security/sql/'
    . 'tbl_perms_contextrolegrants.sql'
);

function contextualAssert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
        exit(1);
    }
}

contextualAssert(
    strpos($service, 'ensureContextRoleGrantTemplate') !== false,
    'contextual grant template contract is absent'
);
contextualAssert(
    strpos($service, 'materializeContextRoleGrants') !== false,
    'context materialization contract is absent'
);
contextualAssert(
    strpos($service, "contextCode . '^' . \$roleName") !== false,
    'concrete context-role group name is not used'
);
contextualAssert(
    strpos($service, 'ensureGroupGrant($groupIds[0], $rightId)') !== false,
    'materialization does not use canonical group grants'
);
contextualAssert(
    strpos($groups, 'materializeContextRoleGrants($contextcode)') !== false,
    'context creation does not materialize canonical grants'
);
contextualAssert(
    strpos($schema, "\$tablename = 'tbl_perms_contextrolegrants'") !== false,
    'canonical template schema is absent'
);
contextualAssert(
    strpos($service, 'newAcl') === false
        && strpos($service, 'addAclGroup') === false,
    'legacy ACL operations entered PermissionService'
);

fwrite(STDOUT, "PASS: canonical contextual grant materialization contract\n");
?>
