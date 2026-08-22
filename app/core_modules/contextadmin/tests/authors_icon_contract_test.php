<?php
/**
 * Ensure every icon used by the author-management template is supported by
 * the curated UI icon service and has a corresponding SVG asset.
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
preg_match_all("/'([^']+)'\\s*=>\\s*true/", $iconService, $allowedMatches);

$used = array_values(array_unique($usedMatches[1]));
$allowed = array_fill_keys($allowedMatches[1], true);
$errors = array();

foreach ($used as $icon) {
    if (!isset($allowed[$icon])) {
        $errors[] = "Icon is not allowlisted: {$icon}";
    }
    $asset = $coreModules . '/ui/resources/icons/lucide/' . $icon . '.svg';
    if (!is_file($asset)) {
        $errors[] = "Icon asset is missing: {$icon}";
    }
}

$expected = array('plus', 'user-cog', 'minus');
if ($used !== $expected) {
    $errors[] = 'Unexpected author action icons: ' . implode(', ', $used);
}

if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

echo "Author action icon contract passed.\n";
