<?php
/** Ensure module updates use the shared icon service rather than legacy bitmaps. @author Derek Keats */
$template = file_get_contents(dirname(__DIR__) . '/templates/content/updates_tpl.php');
$block = file_get_contents(dirname(__DIR__) . '/classes/block_updates_class_inc.php');
$checks = array(
    'shared icon service is loaded' => str_contains($template, "getObject('iconservice', 'ui')"),
    'Lucide refresh icon identifies updates' => str_contains($template, "render('refresh-cw'"),
    'legacy module icon renderer is absent' => !str_contains($template, "getObject('geticon','htmlelements')"),
    'legacy module icon lookup is absent' => !str_contains($template, 'setModuleIcon('),
    'updates block resolves current registration icons' => str_contains($block, "getObject('moduleiconresolver', 'modulecatalogue')") && str_contains($block, '$moduleIcons->render($moduleId'),
    'updates block has no legacy bitmap lookup' => !str_contains($block, 'setModuleIcon(') && !str_contains($block, "getObject('geticon'"),
);
foreach ($checks as $label => $ok) {
    echo ($ok ? 'PASS: ' : 'FAIL: ') . $label . PHP_EOL;
    if (!$ok) exit(1);
}
