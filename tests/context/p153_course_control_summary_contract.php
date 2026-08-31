<?php
/**
 * Contract for non-duplicated course-control summaries and complete members.
 *
 * @author Derek Keats
 */

$root = dirname(__DIR__, 2) . '/app/core_modules/';
$settings = file_get_contents(
    $root . 'context/classes/block_contextsettings_class_inc.php'
);
$plugins = file_get_contents(
    $root . 'context/classes/block_contextmodules_class_inc.php'
);
$members = file_get_contents(
    $root . 'contextgroups/classes/block_contextmembers_class_inc.php'
);
$controlPanel = file_get_contents(
    $root . 'context/templates/content/controlpanel_tpl.php'
);
$skin = file_get_contents(
    dirname(__DIR__, 2) . '/app/skins/chisimba-reborn/stylesheet.css'
);

if ($settings === false || $plugins === false || $members === false
    || $controlPanel === false || $skin === false) {
    fwrite(STDERR, "FAIL: unable to read course-control block sources\n");
    exit(1);
}

if (strpos($controlPanel, 'course-control-details__item--members') === false
    || strpos($skin, 'grid-template-columns: repeat(3, minmax(0, 1fr));') === false) {
    fwrite(STDERR, "FAIL: member directory does not use the full three-column area\n");
    exit(1);
}

$requiredMemberGroups = array("'Lecturers'", "'Students'", "'Guests'");
foreach ($requiredMemberGroups as $group) {
    if (strpos($members, $group) === false) {
        fwrite(STDERR, "FAIL: course-members summary omits a canonical role\n");
        exit(1);
    }
}

$forbidden = array(
    array($settings, "'action' => 'updatesettings'"),
    array($plugins, "'action' => 'manageplugins'"),
    array($members, 'course-control-action'),
);
foreach ($forbidden as $contract) {
    if (strpos($contract[0], $contract[1]) !== false) {
        fwrite(STDERR, "FAIL: duplicate lower-card management action remains\n");
        exit(1);
    }
}

echo "PASS: course-control summaries are display-only and list all member roles.\n";
