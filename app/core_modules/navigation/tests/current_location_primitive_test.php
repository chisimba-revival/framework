<?php
/** Verify the reusable current-location navigation primitive. */
$root = dirname(__DIR__);
$app = dirname(dirname($root));
$sidebar = file_get_contents($root . '/classes/sidebar_class_inc.php');
$register = file_get_contents($root . '/register.conf');
$skin = file_get_contents($app . '/skins/chisimba-reborn/stylesheet.css');

$checks = array(
    'navigation registers the visible You are here label' => str_contains(
        $register,
        'mod_navigation_youarehere'
    ),
    'renderer exposes one semantic current location' => str_contains(
        $sidebar,
        'class="chisimba-current-location" aria-current="page"'
    ),
    'renderer separates the cue from the destination name' => str_contains(
        $sidebar,
        'chisimba-current-location__label'
    ) && str_contains($sidebar, 'chisimba-current-location__name'),
    'skin owns a full-width current-location surface' => str_contains(
        $skin,
        '.chisimba-current-location {'
    ) && str_contains($skin, 'width: 100%;')
        && str_contains($skin, 'CHISIMBA CURRENT LOCATION PRIMITIVE'),
    'obsolete current-page styling is removed' => !str_contains(
        $skin,
        '.chisimba-current-page'
    ),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
