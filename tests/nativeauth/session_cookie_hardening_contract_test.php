<?php
/** Ensure legacy LiveUser configuration cannot weaken PHP's session cookies. */
$engine = file_get_contents(
    dirname(__DIR__, 2) . '/app/classes/core/engine_class_inc.php'
);

$checks = array(
    'LiveUser session cookies are always secure' =>
        str_contains($engine, '$cookiesecure = true;'),
    'LiveUser persistent cookies are HttpOnly' =>
        str_contains($engine, "'httponly' => true,"),
    'legacy database cookie setting is not used as a secure-cookie switch' =>
        !str_contains($engine, "getValue ( 'auth_cookiesecure', 'security', false )"),
);

foreach ($checks as $label => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$label}\n");
        exit(1);
    }
}

echo "PASS: session cookie hardening contract verified.\n";
