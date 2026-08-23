<?php
$module = dirname(__DIR__);
$controller = file_get_contents($module . '/controller.php');
$reader = file_get_contents($module . '/classes/groupadminreadservice_class_inc.php');
$service = file_get_contents($module . '/classes/groupservice_class_inc.php');
$template = file_get_contents($module . '/templates/content/native_readonly_tpl.php');
$css = file_get_contents($module . '/resources/css/native-admin.css');
$skin = file_get_contents(dirname($module, 2) . '/skins/chisimba-reborn/stylesheet.css');

$checks = array(
    'context visibility reaches read service' => str_contains($controller, "getParam('showcontexts', '0')"),
    'management copy matches active controls' => str_contains(
        $template,
        "'mod_groupadmin_native_management'"
    ) && !str_contains($template, "'mod_groupadmin_native_readonly'"),
    'site-only default' => str_contains($reader, 'if (!$showContexts)'),
    'hierarchy ordering' => str_contains($reader, '$ordered[] = $context;')
        && str_contains($reader, '$ordered[] = $child;'),
    'flat canonical subgroup rows' => str_contains(
        $service,
        'foreach ($payload as $key => $subgroup)'
    ) && !str_contains($service, 'foreach ($payload[0] as $key => $subgroup)'),
    'system text context label' => str_contains($template, "'[-context-] groups'"),
    'context toggle' => str_contains($template, 'groupadmin-native__context-toggle'),
    'urls are not double encoded' => str_contains($template, '$escapeUrl = function')
        && str_contains($template, 'html_entity_decode(')
        && !str_contains($template, '$escape($buildUrl('),
    'nested context roles' => str_contains($template, 'groupadmin-native__group-item--nested'),
    'long identity wrapping' => str_contains($css, 'overflow-wrap: anywhere;'),
    'shared selectable primitive used' => str_contains(
        $template,
        'groupadmin-native__group-button chisimba-selectable'
    ) && str_contains($skin, '.chisimba-selectable:hover'),
    'hover keeps readable foreground' => str_contains(
        $css,
        'var(--chisimba-selectable-hover-color, inherit)'
    ),
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
}

echo 'OK: ' . count($checks) . " native group hierarchy checks\n";
?>
