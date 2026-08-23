<?php

$toolbar = dirname(__DIR__);
$cssMenu = file_get_contents($toolbar . '/classes/cssmenu_class_inc.php');
$flatMenu = file_get_contents($toolbar . '/classes/flatmenu_class_inc.php');
$tabsMenu = file_get_contents($toolbar . '/classes/tabsmenu_class_inc.php');
$elearnMenu = file_get_contents($toolbar . '/classes/toolbar_elearn_class_inc.php');
$tools = file_get_contents($toolbar . '/classes/tools_class_inc.php');

$checks = array(
    'dropdown toolbar has no redundant Home item' =>
        !str_contains($cssMenu, '<li id="home"'),
    'alternative toolbars have no redundant Home item' =>
        !str_contains($flatMenu, "languageText('word_home'")
        && !str_contains($tabsMenu, "menuItems['home']")
        && !str_contains($elearnMenu, "menuItems['home']"),
    'standard breadcrumb links Home to site root' => str_contains(
        $tools,
        'new link($this->objConfig->getSiteRoot())'
    ),
    'standard breadcrumb no longer links Home to _default' =>
        !str_contains(
            $tools,
            "new link ( \$this->uri ( array(), '_default' ) )"
        ),
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, 'FAIL: ' . $name . "\n");
        exit(1);
    }
}

fwrite(STDOUT, "PASS: visible Home navigation uses one canonical destination.\n");

?>
