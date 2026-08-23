<?php
/** Verify the named-canvas selection UI and internal fallback boundary. */
$module = dirname(__DIR__);
$app = dirname(dirname($module));
$getter = file_get_contents($module . '/classes/getcanv_class_inc.php');
$controller = file_get_contents($module . '/controller.php');
$skin = file_get_contents($app . '/skins/chisimba-reborn/stylesheet.css');
$chisimba = json_decode(file_get_contents(
    $app . '/skins/chisimba-reborn/canvases/chisimba/canvas.json'
), true);
$kenga = json_decode(file_get_contents(
    $app . '/skins/chisimba-reborn/canvases/kenga-learn/canvas.json'
), true);

$checks = array(
    'fallback is hidden' => str_contains($getter, "str_starts_with(\$canvas, '_')"),
    'cards use the shared skin component' => str_contains($getter, 'chisimba-canvas-card'),
    'selection is explicit' => str_contains($getter, 'Use this canvas')
        && str_contains($getter, 'method="post"'),
    'controller validates named canvas' => str_contains($controller, "private function __applysite()")
        && str_contains($controller, "preg_match('/^[a-z0-9][a-z0-9-]*$/', \$canvas)"),
    'skin owns card presentation' => str_contains($skin, '.chisimba-canvas-grid')
        && str_contains($skin, '.chisimba-canvas-card__preview'),
    'both canvases have brand previews' => isset($chisimba['brand']['primary'])
        && isset($chisimba['preview']['logo'])
        && isset($kenga['brand']['primary'])
        && isset($kenga['preview']['logo']),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
