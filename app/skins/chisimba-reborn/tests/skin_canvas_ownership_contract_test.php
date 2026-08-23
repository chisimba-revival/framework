<?php
/** Verify that modern UI structure has one owner and canvases brand it. */
$skins = dirname(__DIR__, 2);
$skinCss = file_get_contents($skins . '/chisimba-reborn/stylesheet.css');
$chisimbaCanvas = file_get_contents(
    $skins . '/chisimba-reborn/canvases/chisimba/stylesheet.css'
);
$kengaCanvas = file_get_contents(
    $skins . '/chisimba-reborn/canvases/kenga-learn/stylesheet.css'
);
$legacyCss = file_get_contents($skins . '/kenga-learn/stylesheet.css');
$legacyTemplate = file_get_contents(
    $skins . '/kenga-learn/templates/page/page_template.php'
);

$checks = array(
    'skin owns component system' => substr_count($skinCss, '{') > 500,
    'Chisimba canvas is branding-only' => substr_count($chisimbaCanvas, '{') === 0,
    'KengaLearn canvas is a small token overlay' => substr_count($kengaCanvas, '{') === 2,
    'KengaLearn canvas overrides identity tokens' => str_contains(
        $kengaCanvas,
        '--chisimba-primary: #67c871;'
    ) && str_contains($kengaCanvas, '--chisimba-brand-logo:'),
    'legacy stylesheet delegates' => substr_count($legacyCss, '{') === 0
        && str_contains($legacyCss, '../chisimba-reborn/stylesheet.css'),
    'legacy template selects shared canvas' => str_contains(
        $legacyTemplate,
        "\$canvas = 'kenga-learn';"
    ) && str_contains(
        $legacyTemplate,
        'skins/chisimba-reborn/templates/page/page_template.php'
    ),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
