<?php
$skins = dirname(__DIR__, 2);
$reborn = file_get_contents($skins . '/chisimba-reborn/stylesheet.css');
$kenga = file_get_contents($skins . '/kenga-learn/stylesheet.css');
$checks = array(
    'primitive available in both skins' => str_contains($reborn, '.chisimba-notice {')
        && str_contains($kenga, '.chisimba-notice {'),
    'notice base primitive' => str_contains($reborn, '.chisimba-notice {'),
    'semantic notice variants' => str_contains($reborn, '.chisimba-notice--success')
        && str_contains($reborn, '.chisimba-notice--error'),
    'transient notice dismisses' => str_contains($reborn, '.chisimba-notice--transient')
        && str_contains($reborn, '@keyframes chisimba-notice-dismiss'),
);
foreach ($checks as $name => $ok) {
    if (!$ok) { fwrite(STDERR, "FAIL: $name\n"); exit(1); }
    echo "PASS: $name\n";
}
