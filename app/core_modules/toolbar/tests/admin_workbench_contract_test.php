<?php
$root = dirname(__DIR__, 2);
$toolbar = $root . '/toolbar';
$template = file_get_contents($toolbar . '/templates/content/admin_tpl.php');
$layout = file_get_contents($toolbar . '/templates/layout/admin_layout_tpl.php');
$controller = file_get_contents($toolbar . '/controller.php');
$css = file_get_contents(dirname($root, 1) . '/skins/chisimba-reborn/stylesheet.css');
$canvasCss = file_get_contents(dirname($root, 1)
    . '/skins/chisimba-reborn/canvases/_default/stylesheet.css');

$checks = array(
    'searchable workbench root' => str_contains($template, 'data-admin-workbench'),
    'search control' => str_contains($template, 'admin-workbench-query'),
    'search filters task metadata' => str_contains($template, 'data-admin-search'),
    'empty search state' => str_contains($template, 'data-admin-empty'),
    'common tasks come from page metadata' => str_contains($template, "\$modules['common']"),
    'current course tasks come from page metadata' => str_contains($template, "\$modules['current']"),
    'current course name uses context API' => str_contains($controller, 'getTitle()'),
    'leave course action is scoped' => str_contains($template, "'leavecontext'"),
    'open course action is explicit' => str_contains($template,
        'mod_toolbar_opencontext') && str_contains($template,
        'open course course page return course'),
    'legacy hard-coded admin sidebar removed' => !str_contains($layout, "newObject('adminmenu'"),
    'responsive workbench primitive' => str_contains($css, '.admin-workbench-layout'),
    'lower task areas reclaim full width' => str_contains($template,
        'admin-workbench-secondary') && str_contains($css,
        '.admin-workbench-secondary'),
    'narrow single-column layout' => str_contains($css, '@media (max-width: 52rem)'),
    'keyboard focus treatment' => str_contains($css, '.admin-workbench-task:focus-visible'),
    'outer canvas card is structural' => str_contains($canvasCss,
        "> .toolbar_main\n    > .admin-workbench"),
    'vacant canvas sidebar track removed' => str_contains($css,
        'grid-template-columns: minmax(0, 1fr) !important;')
        && str_contains($css, 'width: 100% !important;'),
);

foreach ($checks as $label => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$label}\n");
        exit(1);
    }
}

$registrations = array(
    'contextadmin' => array('PAGE: admin_courses', 'PAGE: admin_common', 'PAGE: admin_current'),
    'contextgroups' => array('PAGE: admin_current'),
    'contextpermissions' => array('PAGE: admin_current'),
    'useradmin' => array('PAGE: admin_people', 'PAGE: admin_common'),
    'groupadmin' => array('PAGE: admin_people', 'PAGE: admin_common'),
    'sysconfig' => array('PAGE: admin_system', 'PAGE: admin_common'),
    'logger' => array(
        'MODULE_ISADMIN: 1',
        'PAGE: admin_operations',
        'PAGE: admin_common',
    ),
    'modulecatalogue' => array('PAGE: admin_advanced', 'PAGE: admin_common'),
);

foreach ($registrations as $module => $needles) {
    $register = file_get_contents($root . '/' . $module . '/register.conf');
    foreach ($needles as $needle) {
        if (!str_contains($register, $needle)) {
            fwrite(STDERR, "FAIL: {$module} lacks {$needle}\n");
            exit(1);
        }
    }
}

echo "PASS: task-oriented Site Administration workbench contract verified.\n";
?>
