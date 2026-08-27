<?php
/** Ensure authentication button icons follow the button foreground colour. */
$css = file_get_contents(
    dirname(__DIR__) . '/canvases/_default/stylesheet.css'
);

$checks = array(
    'field icons retain the muted treatment' => str_contains(
        $css,
        '#form_loginform .chisimba-icon,'
    ) && str_contains($css, 'color: var(--chisimba-muted, #64748b);'),
    'login button icon inherits its contrasting foreground' => str_contains(
        $css,
        '#form_loginform .loginbuttonwrap button .chisimba-icon,'
    ) && str_contains(
        $css,
        ".auth-block__logout button .chisimba-icon {\n    color: currentColor;"
    ),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}

fwrite(STDOUT, "PASS: login button icon contrast contract\n");
