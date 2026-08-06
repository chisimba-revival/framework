<?php
/** Static regression contract for ordinary-group rename and Context safety. */
$root = dirname(dirname(__DIR__));
$group = file_get_contents($root . '/app/core_modules/groupadmin/classes/groupservice_class_inc.php');
$context = file_get_contents($root . '/app/core_modules/context/classes/dbcontext_class_inc.php');
$ops = file_get_contents($root . '/app/core_modules/groupadmin/classes/groupops_class_inc.php');
$coordinator = $root . '/app/core_modules/groupadmin/classes/groupcontextrenameservice_class_inc.php';
$checks = array(
    strpos($group, 'renameGroupHierarchy') !== false,
    strpos($group, 'beginTransaction') !== false,
    strpos($group, 'commitTransaction') !== false,
    strpos($group, 'rollbackTransaction') !== false,
    strpos($group, '$topLevelMatches !== 1') !== false,
    strpos($context, 'renameContextCode') === false,
    file_exists($coordinator) === false,
    strpos($ops, 'contextExists($oldgroupname)') !== false,
    strpos($ops, 'context_group_rename_forbidden') !== false,
    strpos($ops, 'objGroupService->renameGroupHierarchy') !== false,
    strpos($ops, 'objGroupContextRename') === false,
    strpos($ops, 'UPDATE tbl_context SET contextcode') === false,
    strpos($ops, 'UPDATE tbl_perms_groups SET group_define_name') === false,
    strpos($ops, 'function updateSubGroup') === false,
);
foreach ($checks as $number => $passed) {
    if (!$passed) {
        fwrite(STDERR, 'FAIL: group/Context safety check ' . ($number + 1) . PHP_EOL);
        exit(1);
    }
}
echo "PASS: canonical ordinary-group rename preserves stable Context identity.\n";
