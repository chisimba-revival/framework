<?php
/** Verify the course-home editing route to reusable landing content. */
$template = file_get_contents(dirname(__DIR__) . '/templates/content/context_home_tpl.php');
$checks = array(
    'route is conditional on Content blocks' => str_contains($template, "checkIfRegistered('contentblocks')"),
    'route opens the current context scope' => str_contains($template, "'scope' => 'context'")
        && str_contains($template, "'contentblocks'"),
    'route uses the shared secondary button primitive' => str_contains($template, 'chisimba-button-secondary chisimba-button-compact'),
    'route is shown once in the wide block editor' => substr_count($template, '$manageLandingContentAction') === 3,
);
foreach ($checks as $name => $ok) {
    if (!$ok) { fwrite(STDERR, "FAIL: $name\n"); exit(1); }
    echo "PASS: $name\n";
}
