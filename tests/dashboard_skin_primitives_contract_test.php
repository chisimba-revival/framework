<?php
/** Static checks for reusable dashboard skin primitives. */
$css = file_get_contents(dirname(__DIR__) . '/app/skins/chisimba-reborn/stylesheet.css');
$required = array(
    '.dashboard-panel',
    '.dashboard-panel__header',
    '.dashboard-eyebrow',
    '.dashboard-date-strip',
    '.dashboard-date-chip',
    '.dashboard-agenda',
    '.dashboard-agenda-item',
    '.dashboard-empty-state',
);
$missing = array_values(array_filter($required, static function ($selector) use ($css) {
    return strpos($css, $selector) === false;
}));
if ($missing !== array()) {
    fwrite(STDERR, 'Missing dashboard primitives: ' . implode(', ', $missing) . PHP_EOL);
    exit(1);
}
if (strpos($css, '@media (max-width: 620px)') === false) {
    fwrite(STDERR, "Missing compact dashboard layout.\n");
    exit(1);
}
echo 'Dashboard skin primitive contracts passed (' . count($required) . ').' . PHP_EOL;
