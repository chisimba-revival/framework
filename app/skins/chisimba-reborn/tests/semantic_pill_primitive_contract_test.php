<?php
$skins = dirname(__DIR__, 2);
$reborn = file_get_contents($skins . '/chisimba-reborn/stylesheet.css');
$brandCanvas = file_get_contents(
    $skins . '/chisimba-reborn/canvases/kenga-learn/stylesheet.css'
);
$checks = array(
    'pill has one canonical owner' => str_contains($reborn, '.chisimba-pill {')
        && !str_contains($brandCanvas, '.chisimba-pill {'),
    'pill is compact and rounded' => str_contains($reborn, 'border-radius: 999px;')
        && str_contains($reborn, 'display: inline-flex;'),
    'success pill uses semantic token' => str_contains($reborn, '.chisimba-pill--success {')
        && str_contains($reborn, 'var(--chisimba-success)'),
);
foreach ($checks as $name => $ok) {
    if (!$ok) { fwrite(STDERR, "FAIL: $name\n"); exit(1); }
    echo "PASS: $name\n";
}
?>
