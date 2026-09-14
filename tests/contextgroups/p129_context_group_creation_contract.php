<?php
$source = file_get_contents(
    __DIR__.'/../../app/core_modules/contextgroups/classes/managegroups_class_inc.php'
);
$service = file_get_contents(
    __DIR__.'/../../app/core_modules/groupadmin/classes/groupservice_class_inc.php'
);
$model = file_get_contents(
    __DIR__.'/../../app/core_modules/groupadmin/classes/groupadminmodel_class_inc.php'
);
function p129Assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
    echo "PASS: $message\n";
}
p129Assert(strpos($source, "getObject('groupservice', 'groupadmin')") !== false,
    'Context groups use canonical GroupService');
p129Assert(strpos($source, '->ensureGroups($definitions)') !== false,
    'Context groups use canonical group allocation');
p129Assert(strpos($source, '->ensureSubgroup(') !== false,
    'Context hierarchy uses canonical GroupService');
p129Assert(strpos($source, '->_objGroupAdmin->addGroup(') === false,
    'Context creation no longer uses legacy LiveUser group creation');
p129Assert(strpos($source, '->_objGroupAdmin->addSubGroups(') === false,
    'Context creation no longer uses legacy subgroup creation');
p129Assert(strpos($source, 'materializeContextRoleGrants($contextcode)') !== false,
    'Canonical contextual grants remain fail-closed');
p129Assert(strpos($service, 'public function ensureSubgroup(') !== false,
    'GroupService owns the hierarchy contract');
p129Assert(strpos($service, 'INSERT INTO tbl_perms_group_subgroups') !== false
    && strpos($service, 'return is_array($rows) && count($rows) === 1;') !== false,
    'GroupService owns hierarchy storage and verifies the result');
p129Assert(strpos($model, 'getCanonicalGroupService()->ensureSubgroup($groupId, $subgroupId)') !== false,
    'Legacy hierarchy adapter delegates to GroupService');
p129Assert(strpos($model, 'public function assignCanonicalSubGroup(') !== false,
    'groupadminmodel exposes only the hierarchy storage adapter');
echo "ALL P129 CONTEXT-GROUP CREATION CONTRACT TESTS PASSED\n";
