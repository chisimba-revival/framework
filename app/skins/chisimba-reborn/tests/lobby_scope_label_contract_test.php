<?php
/**
 * Contract checks for the human-facing site-wide scope label.
 *
 * The internal scope identifier remains "root" for compatibility, while the
 * interface describes that location as the Lobby.
 *
 * @author Derek Keats
 */

$app = dirname(__DIR__, 3);
$register = file_get_contents($app . '/core_modules/toolbar/register.conf');
$template = file_get_contents(dirname(__DIR__) . '/templates/page/page_template.php');

$checks = array(
    'site-wide scope is labelled Lobby' => str_contains(
        $register,
        'TEXT: mod_toolbar_scope_root|Lobby scope|Lobby'
    ),
    'skin resolves the stable internal root language key' => str_contains(
        $template,
        "'mod_toolbar_scope_root'"
    ),
    'skin does not hard-code the replacement label' => !str_contains(
        $template,
        "footerScopeValue = 'Lobby'"
    ),
);

foreach ($checks as $description => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$description}\n");
        exit(1);
    }
    echo "PASS: {$description}\n";
}
