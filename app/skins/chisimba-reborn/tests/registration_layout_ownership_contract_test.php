<?php
$skinsRoot = dirname(__DIR__, 2);
$skins = array('chisimba-reborn');

foreach ($skins as $skin) {
    $base = file_get_contents($skinsRoot . '/' . $skin . '/stylesheet.css');
    $canvas = file_get_contents(
        $skinsRoot . '/' . $skin . '/canvases/_default/stylesheet.css'
    );
    $checks = array(
        'base owns no registration column geometry' => !str_contains(
            $base,
            'minmax(27rem, .52fr)'
        ),
        'canvas owns one registration column width' => substr_count(
            $canvas,
            'minmax(27rem, .52fr)'
        ) === 1,
        'canvas owns one registration top alignment' => substr_count(
            $canvas,
            ') > #Canvas_Content_Body_Region3 > .chisimba-guidance-card'
        ) === 1 && str_contains($canvas, 'margin-top: 0 !important'),
        'account card is the sole surface' => str_contains(
            $canvas,
            '> .registration-service.chisimba-form-page'
        ) && str_contains($canvas, 'background: transparent;')
            && str_contains($canvas, 'box-shadow: none;'),
        'complete cards own their track edges' => substr_count(
            $canvas,
            'padding: 0;'
        ) >= 2,
        'component spacing remains in base skin' => str_contains(
            $base,
            '.registration-service.chisimba-form-page'
        ) && str_contains($base, '.chisimba-form-card'),
    );
    foreach ($checks as $name => $passed) {
        if (!$passed) {
            fwrite(STDERR, "FAIL [$skin]: $name\n");
            exit(1);
        }
    }
}

echo "OK: registration layout has one canonical owner\n";
?>
