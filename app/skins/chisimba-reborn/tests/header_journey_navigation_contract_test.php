<?php
/** Verify persistent, role-aware journey navigation in the site banner. */
$skin = dirname(__DIR__);
$template = file_get_contents($skin . '/templates/page/page_template.php');
$css = file_get_contents($skin . '/stylesheet.css');
$toolbar = dirname($skin, 2) . '/core_modules/toolbar/register.conf';
$language = file_get_contents($toolbar);

$checks = array(
    'current context links to its home' => str_contains(
        $template,
        "'url' => \$this->uri(array('action' => 'home'), 'context')"
    ),
    'teaching dashboard remains one click away' => str_contains(
        $template,
        "'url' => \$this->uri(null, 'myteaching')"
    ),
    'context creation remains one click away' => str_contains(
        $template,
        "'url' => \$this->uri(array('action' => 'add'), 'contextadmin')"
    ),
    'header journeys use icons and labelled links' => str_contains(
        $template,
        'chisimba-site-banner__journey'
    ) && str_contains($template, "\$icons->render(\$journeyLink['icon']"),
    'journey labels use the language system' => str_contains(
        $language,
        'mod_toolbar_currentcontext'
    ) && str_contains($language, 'mod_toolbar_allteachingcontexts'),
    'journey pills have a shared compact treatment' => str_contains(
        $css,
        '.chisimba-site-banner__journey {'
    ) && str_contains($css, 'border-radius: 999px;'),
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}

fwrite(STDOUT, "Header journey navigation contract: PASS\n");
