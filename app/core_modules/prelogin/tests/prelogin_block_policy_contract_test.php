<?php
$prelogin = dirname(__DIR__);
$core = dirname($prelogin);
$template = file_get_contents($prelogin . '/templates/content/prelogin_tpl.php');
$defaults = file_get_contents($prelogin . '/sql/defaultdata.xml');
$language = file_get_contents($core . '/language/classes/block_language_class_inc.php');
$skin = file_get_contents($core . '/skin/classes/block_skinchooser_class_inc.php');
$canvas = file_get_contents($core . '/canvas/classes/block_selecttype_class_inc.php');
$checks = array(
    'login is intrinsic to prelogin' => str_contains($template, "showBlock('login', 'security')")
        && str_contains($template, "\$block['blockname'] === 'login'"),
    'obsolete defaults are absent' => !str_contains($defaults, '<id>init_1</id>')
        && !str_contains($defaults, 'Other Sites')
        && !str_contains($defaults, 'Join this project')
        && !str_contains($defaults, 'w3link')
        && !str_contains($defaults, 'blockName>context'),
    'validator implementation is removed' => !file_exists($prelogin . '/classes/block_w3link_class_inc.php'),
    'language chooser needs two languages' => str_contains($language, 'getLanguageList()) < 2'),
    'skin chooser is administrator-only' => str_contains($skin, "if (!\$this->objUser->isAdmin()) return '';"),
    'canvas selector is administrator-only' => str_contains($canvas, "if (!\$this->objUser->isAdmin()) return '';"),
);
foreach ($checks as $name => $passed) {
    if (!$passed) { fwrite(STDERR, "FAIL: {$name}\n"); exit(1); }
}
fwrite(STDOUT, "Prelogin block policy contract: PASS\n");
