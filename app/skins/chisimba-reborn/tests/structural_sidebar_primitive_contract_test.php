<?php
/** Static contract for reusable structural main and sidebar regions. */
$skinRoot = dirname(__DIR__);
$canvas = file_get_contents($skinRoot . '/canvases/_default/stylesheet.css');

$checks = array(
    'primitive has a documented ownership boundary' => str_contains(
        $canvas,
        'CHISIMBA_STRUCTURAL_SIDEBAR'
    ),
    'primitive removes canvas region inset' => str_contains(
        $canvas,
        ':has(> .chisimba-structural-sidebar)'
    ) && preg_match(
        '/:has\(> \.chisimba-structural-sidebar\)[^{]*\{[^}]*padding:\s*0;/s',
        $canvas
    ) === 1,
    'primitive resets first-card top margin' => str_contains(
        $canvas,
        '.chisimba-structural-sidebar > :first-child'
    ) && str_contains($canvas, 'margin-top: 0 !important;'),
    'structural main removes duplicate region card and gutter' => str_contains(
        $canvas,
        'CHISIMBA_STRUCTURAL_MAIN'
    ) && str_contains(
        $canvas,
        '#Canvas_Content_Body_Region2:has(> .chisimba-structural-main)'
    ) && preg_match(
        '/#Canvas_Content_Body_Region2:has\(> \.chisimba-structural-main\)[^{]*\{[^}]*background:\s*transparent;[^}]*border:\s*0;[^}]*box-shadow:\s*none;[^}]*padding:\s*0;/s',
        $canvas
    ) === 1,
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}

fwrite(STDOUT, "PASS: structural sidebar primitive contract\n");
