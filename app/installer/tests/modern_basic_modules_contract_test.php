<?php
/** Verify that every fresh installation includes the maintained UI foundation. */
$systemTypes = simplexml_load_file(
    dirname(__DIR__) . '/dbhandlers/systemtypes.xml'
);

if ($systemTypes === false) {
    fwrite(STDERR, "FAIL: systemtypes.xml is not valid XML\n");
    exit(1);
}

$categories = $systemTypes->xpath(
    "//modulelist/category[categoryname='Basic System Only']"
);
$modules = array();

if (isset($categories[0])) {
    foreach ($categories[0]->module as $module) {
        $modules[] = trim((string) $module);
    }
}

$checks = array(
    'basic module category exists' => isset($categories[0]),
    'shared UI primitives install by default' => in_array('ui', $modules, true),
    'canvas management installs by default' => in_array('canvas', $modules, true),
    'UI is installed before toolbar consumes it' => array_search('ui', $modules, true)
        < array_search('toolbar', $modules, true),
    'UI is installed before canvas consumes it' => array_search('ui', $modules, true)
        < array_search('canvas', $modules, true),
    'canvas declares its UI dependency' => str_contains(
        file_get_contents(dirname(__DIR__, 2) . '/core_modules/canvas/register.conf'),
        'DEPENDS: ui'
    ),
    'toolbar declares its UI dependency' => str_contains(
        file_get_contents(dirname(__DIR__, 2) . '/core_modules/toolbar/register.conf'),
        'DEPENDS: ui'
    ),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
