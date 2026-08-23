<?php
$module = dirname(__DIR__);
$controller = file_get_contents($module . '/controller.php');
$reader = file_get_contents($module . '/classes/groupadminreadservice_class_inc.php');
$template = file_get_contents($module . '/templates/content/native_readonly_tpl.php');
$css = file_get_contents($module . '/resources/css/native-admin.css');

$checks = array(
    'context visibility reaches read service' => str_contains($controller, "getParam('showcontexts', '0')"),
    'site-only default' => str_contains($reader, 'if (!$showContexts)'),
    'hierarchy ordering' => str_contains($reader, '$ordered[] = $context;')
        && str_contains($reader, '$ordered[] = $child;'),
    'system text context label' => str_contains($template, "'[-context-] groups'"),
    'context toggle' => str_contains($template, 'groupadmin-native__context-toggle'),
    'nested context roles' => str_contains($template, 'groupadmin-native__group-item--nested'),
    'long identity wrapping' => str_contains($css, 'overflow-wrap: anywhere;'),
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
}

echo 'OK: ' . count($checks) . " native group hierarchy checks\n";
?>
