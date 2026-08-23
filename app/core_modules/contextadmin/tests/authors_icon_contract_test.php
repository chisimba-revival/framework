<?php
/**
 * Ensure every icon used by the author-management template is supported by
 * the bundled UI icon catalogue and has a corresponding SVG asset.
 */

$moduleRoot = dirname(__DIR__);
$coreModules = dirname($moduleRoot);
$template = file_get_contents($moduleRoot . '/templates/content/authors.php');
$iconService = file_get_contents($coreModules . '/ui/classes/iconservice_class_inc.php');

if ($template === false || $iconService === false) {
    fwrite(STDERR, "Unable to read author template or icon service.\n");
    exit(1);
}

preg_match_all("/->render\\('([^']+)'/", $template, $usedMatches);
$used = array_values(array_unique($usedMatches[1]));
$errors = array();

foreach ($used as $icon) {
    $asset = $coreModules . '/ui/resources/icons/lucide/' . $icon . '.svg';
    if (!is_file($asset)) {
        $errors[] = "Icon asset is missing: {$icon}";
    }
}

$expected = array('plus', 'arrow-right-left', 'user-minus');
if ($used !== $expected) {
    $errors[] = 'Unexpected author action icons: ' . implode(', ', $used);
}

if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

echo "Author action icon contract passed.\n";
