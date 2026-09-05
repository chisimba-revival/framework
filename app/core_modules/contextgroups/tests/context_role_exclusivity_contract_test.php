<?php
/** Verify that author and read-only roles cannot overlap in one context. */
$contextGroups = dirname(__DIR__);
$controller = file_get_contents($contextGroups . '/controller.php');
$coreModules = dirname($contextGroups);
$groupService = file_get_contents(
    $coreModules . '/groupadmin/classes/groupservice_class_inc.php'
);

$checks = array(
    'canonical membership writes reject a conflicting context role' =>
        str_contains($groupService, 'hasConflictingContextRole(')
        && str_contains($groupService, "'conflicting_context_role'"),
    'only lecturer and student context groups are mutually exclusive' =>
        str_contains(
            $groupService,
            "preg_match('/^(.*)\\^(Lecturers|Students)$/',"
        ),
    'the opposite role is checked in the same context namespace' =>
        str_contains($groupService, "? 'Students'")
        && str_contains($groupService, ": 'Lecturers'")
        && str_contains($groupService, '$parts[1] . \'^\' . $conflictingRole'),
    'role changes remove old membership before adding the new role' =>
        str_contains($controller, 'foreach (array(false, true) as $shouldBelong)')
        && str_contains($controller, '$desiredMembership !== $shouldBelong'),
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}

fwrite(STDOUT, "Context role exclusivity contract: PASS\n");
