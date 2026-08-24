<?php
/** Verify that the active course-sidebar destination is not a redundant link. */
$contextRoot = dirname(__DIR__);
$coreRoot = dirname($contextRoot);
$contextSidebar = file_get_contents(
    $contextRoot . '/classes/contextsidebar_class_inc.php'
);
$sidebar = file_get_contents(
    $coreRoot . '/navigation/classes/sidebar_class_inc.php'
);

$checks = array(
    'context sidebar marks the active destination explicitly' =>
        str_contains($contextSidebar, "\$node['current']")
        && str_contains($contextSidebar, "\$node['nodeid'] === \$activeId"),
    'navigation renders the current destination without an anchor' =>
        str_contains($sidebar, "if (!empty(\$node['current']))")
        && str_contains($sidebar, 'aria-current="page"')
        && str_contains($sidebar, 'chisimba-current-page'),
);

foreach ($checks as $label => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$label}\n");
        exit(1);
    }
}

echo "PASS: course-sidebar current-page contract verified.\n";
?>
