<?php
/** Context-home contextual Help contract. */
$root = dirname(__DIR__);
$provider = file_get_contents($root . '/classes/helpcontent_class_inc.php');
$template = file_get_contents($root . '/templates/content/context_home_tpl.php');
$register = file_get_contents($root . '/register.conf');
$checks = array(
    'home template renders contextual Help' => str_contains($template, "->show('context', 'managing-the-context-home')"),
    'Help remains optional' => str_contains($template, "checkIfRegistered('help')"),
    'topic is limited to managers' => str_contains($provider, '$this->user->isAdmin()')
        && str_contains($provider, '$this->groups->isContextLecturer()'),
    'guide explains creation and placement' => str_contains($register, 'Manage [-context-] landing content')
        && str_contains($register, 'main Add a Block list'),
    'guide distinguishes landing and chapter content' => str_contains($register, 'not attached to chapters')
        && str_contains($register, '[-context-] Content'),
    'guide retains defaults when catalogue import is delayed' => str_contains($provider, '$defaults[$suffix]'),
);
foreach ($checks as $name => $ok) {
    if (!$ok) { fwrite(STDERR, "FAIL: $name\n"); exit(1); }
    echo "PASS: $name\n";
}
