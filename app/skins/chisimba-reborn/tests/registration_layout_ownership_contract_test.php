<?php
$skinsRoot = dirname(__DIR__, 2);
$skins = array('chisimba-reborn', 'kenga-learn');

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
            'margin-top: 5px !important'
        ) === 1,
        'account card is the sole surface' => str_contains(
            $canvas,
            '> .registration-service.chisimba-form-page'
        ) && str_contains($canvas, 'background: transparent;')
            && str_contains($canvas, 'box-shadow: none;'),
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

echo "OK: registration layout ownership is singular in both modern skins\n";
?>
