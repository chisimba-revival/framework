<?php
$canvas = dirname(__DIR__) . '/canvases/ecotraining';
$css = file_get_contents($canvas . '/stylesheet.css');
$metadata = json_decode(file_get_contents($canvas . '/canvas.json'), true);

$checks = array(
    'canvas metadata is valid' => is_array($metadata)
        && ($metadata['name'] ?? '') === 'ecotraining',
    'primary green is configured' => str_contains(
        $css,
        '--chisimba-primary: #3b6641;'
    ),
    'accent green is configured' => str_contains(
        $css,
        '--chisimba-primary-soft: #9bb35c;'
    ),
    'brown footer is configured' => str_contains(
        $css,
        '--chisimba-charcoal: #64453b;'
    ),
    'combined logo suppresses duplicate site name' => preg_match(
        '/#sitename\.chisimba-site-banner__name\s*\{[^}]*display: none;/s',
        $css
    ) === 1,
    'canvas expects its combined SVG logo' => str_contains(
        $css,
        'images/ecotraining-logo.svg'
    ),
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}

fwrite(STDOUT, "EcoTraining canvas contract: PASS\n");
