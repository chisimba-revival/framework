<?php
/** Static contract for form cards that fill a wide workspace column. */
$skin = file_get_contents(dirname(__DIR__) . '/stylesheet.css');

$ok = preg_match(
    '/\.chisimba-form-card--wide\s*\{[^}]*width:\s*100%;[^}]*max-width:\s*none;/s',
    $skin
) === 1;

if (!$ok) {
    fwrite(STDERR, "FAIL: wide form-card primitive must remove the base width cap\n");
    exit(1);
}

fwrite(STDOUT, "PASS: wide form-card primitive contract\n");
