<?php
/** Static contract ensuring branding canvases may own the browser favicon. */
$template = file_get_contents(
    dirname(__DIR__) . '/templates/page/page_template.php'
);
$contract = file_get_contents(dirname(__DIR__) . '/CANVAS_CONTRACT.md');

$checks = array(
    'selected canvas favicon is considered first' => str_contains(
        $template,
        "'/canvases/'\n        . \$canvasName . '/favicon.png'"
    ) && str_contains($template, 'is_file($siteRootPath . $canvasFavicon)'),
    'skin favicon remains the compatibility fallback' => str_contains(
        $template,
        "\$skinFavicon = 'skins/' . \$skinName . '/favicon.png';"
    ) && str_contains($template, '$favicon = $skinFavicon;'),
    'favicon is independently cache versioned' => str_contains(
        $template,
        'filemtime($siteRootPath . $favicon)'
    ) && str_contains($template, '$faviconVersion'),
    'rendered favicon path is escaped' => str_contains(
        $template,
        '$favicon . $faviconVersion'
    ) && str_contains($template, 'htmlspecialchars('),
    'canvas contract documents favicon ownership' => str_contains(
        $contract,
        'A canvas may provide `favicon.png`'
    ) && str_contains($contract, "skin's `favicon.png`"),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}

fwrite(STDOUT, "PASS: canvas favicon contract\n");
