<?php

declare(strict_types=1);

$moduleRoot = dirname(__DIR__);
$appRoot = dirname(__DIR__, 3);
$register = file_get_contents($moduleRoot . '/register.conf');
$defaults = file(
    $appRoot . '/installer/dbhandlers/default_modules.txt',
    FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
);

if ($register === false || $defaults === false) {
    fwrite(STDERR, "Unable to read UI installation metadata.\n");
    exit(1);
}

$required = [
    'MODULE_ID: ui',
    'MODULE_PATH: ui',
    'MODULE_CATEGORY: core',
    'MODULE_STATUS: core',
];

foreach ($required as $metadata) {
    if (!str_contains($register, $metadata)) {
        fwrite(STDERR, "Missing UI registration metadata: {$metadata}\n");
        exit(1);
    }
}

$moduleCataloguePosition = array_search('modulecatalogue', $defaults, true);
$uiPosition = array_search('ui', $defaults, true);

if ($uiPosition === false) {
    fwrite(STDERR, "UI is absent from the default core module installation list.\n");
    exit(1);
}

if ($moduleCataloguePosition === false || $uiPosition !== $moduleCataloguePosition + 1) {
    fwrite(STDERR, "UI must install immediately after modulecatalogue.\n");
    exit(1);
}

fwrite(STDOUT, "UI core installation metadata contract passed.\n");
