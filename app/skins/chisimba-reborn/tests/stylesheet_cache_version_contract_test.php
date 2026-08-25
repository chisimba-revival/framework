<?php
/** Static contract ensuring deployed skin changes invalidate browser caches. */
$template = file_get_contents(
    dirname(__DIR__) . '/templates/page/page_template.php'
);

$checks = array(
    'skin stylesheet uses its modification time' => str_contains(
        $template,
        "filemtime(\$skinCss)"
    ) && str_contains($template, '$skinCssVersion'),
    'canvas stylesheet uses its modification time' => str_contains(
        $template,
        "filemtime(\$canvasCss)"
    ) && str_contains($template, '$canvasCssVersion'),
    'base canvas layout is loaded and independently versioned' => str_contains(
        $template,
        "filemtime(\$baseCanvasCss)"
    ) && str_contains($template, '$baseCanvasCssVersion')
        && strpos($template, '$baseCanvasCss . $baseCanvasCssVersion')
            < strpos($template, '$canvasCss . $canvasCssVersion'),
    'both version markers are emitted in links' => substr_count(
        $template,
        'CssVersion'
    ) >= 6,
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}

fwrite(STDOUT, "PASS: stylesheet cache version contract\n");
