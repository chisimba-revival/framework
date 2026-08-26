<?php
/** Verify that modern UI structure has one owner and canvases brand it. */
$skins = dirname(__DIR__, 2);
$skinCss = file_get_contents($skins . '/chisimba-reborn/stylesheet.css');
$chisimbaCanvas = file_get_contents(
    $skins . '/chisimba-reborn/canvases/chisimba/stylesheet.css'
);
$classicCanvas = file_get_contents(
    $skins . '/chisimba-reborn/canvases/chisimba-classic/stylesheet.css'
);
$kengaCanvas = file_get_contents(
    $skins . '/chisimba-reborn/canvases/kenga-learn/stylesheet.css'
);

$checks = array(
    'skin owns component system' => substr_count($skinCss, '{') > 500,
    'Chisimba canvas is branding-only' => substr_count($chisimbaCanvas, '{') === 0,
    'Chisimba Classic is a small token overlay' => substr_count($classicCanvas, '{') === 2,
    'Chisimba Classic uses the recorded historic palette' => str_contains(
        $classicCanvas,
        '--chisimba-primary: #5a3f1e;'
    ) && str_contains($classicCanvas, '--chisimba-primary-hover: #be792f;')
        && str_contains($classicCanvas, '--chisimba-grey-blue: #4c4d4f;'),
    'KengaLearn canvas is a small token overlay' => substr_count($kengaCanvas, '{') === 2,
    'KengaLearn canvas overrides identity tokens' => str_contains(
        $kengaCanvas,
        '--chisimba-primary: #67c871;'
    ) && str_contains($kengaCanvas, '--chisimba-brand-logo:'),
    'legacy framework skins are absent' =>
        !is_dir($skins . '/canvas')
        && !is_dir($skins . '/canvas5')
        && !is_dir($skins . '/kenga-learn')
        && !is_dir($skins . '/metallic'),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
