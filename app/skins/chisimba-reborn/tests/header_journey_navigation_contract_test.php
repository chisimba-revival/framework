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
    ) && str_contains(
        $template,
        "'class' => 'teaching'"
    ) && str_contains(
        $template,
        'if ($isAuthor'
    ),
    'administrator and author status are evaluated independently' =>
        str_contains($template, '$isAuthor = $roleContext->isLecturer();')
        && str_contains($template, 'if ($isSiteAdministrator) {')
        && str_contains($template, '} else {'),
    'learning and teaching roles remain additive' =>
        str_contains(
            $template,
            "'url' => \$this->uri(null, 'myteaching')"
        )
        && str_contains(
            $template,
            "'url' => \$this->uri(null, 'mylearning')"
        )
        && str_contains(
            $template,
            "'class' => 'learning'"
        ),
    'context creation remains one click away for authors' => str_contains(
        $template,
        "'url' => \$this->uri(array('action' => 'add'), 'contextadmin')"
    ) && str_contains($template, 'if ($isAuthor'),
    'administrator rail uses administration destinations' =>
        str_contains($template, "'url' => \$this->uri(null, 'myadmin')")
        && str_contains($template, "'url' => \$this->uri(null, 'toolbar')")
        && str_contains($language, 'mod_toolbar_myadministration')
        && str_contains($language, 'mod_toolbar_siteadministration'),
    'header journeys use icons and labelled links' => str_contains(
        $template,
        'chisimba-site-banner__journey'
    ) && str_contains($template, "\$icons->render(\$journeyLink['icon']"),
    'current journey is indicated without linking to itself' =>
        str_contains($template, "\$tag = \$journeyLink['isCurrent'] ? 'span' : 'a';")
        && str_contains($template, "' aria-current=\"page\"'")
        && str_contains($template, "'isCurrent' => \$isJourneyCurrent('mylearning')")
        && str_contains($template, "'isCurrent' => \$isJourneyCurrent('myteaching')")
        && str_contains($template, "'isCurrent' => \$isJourneyCurrent('myadmin')")
        && str_contains($css, '.chisimba-site-banner__journey--active {')
        && preg_match(
            '/\.chisimba-site-banner__journey--active \{[^}]*'
                . 'background: var\(--chisimba-surface-translucent\);[^}]*'
                . 'color: var\(--chisimba-ink\);/s',
            $css
        ) === 1,
    'journey labels use the language system' => str_contains(
        $language,
        'mod_toolbar_currentcontext'
    ) && str_contains($language, 'mod_toolbar_allteachingcontexts'),
    'journey pills have a shared compact treatment' => str_contains(
        $css,
        '.chisimba-site-banner__journey {'
    ) && str_contains($css, 'border-radius: 999px;')
        && str_contains($css, 'min-height: 1.65rem;'),
    'utility rail sits at the foot of the banner' => preg_match(
        '/\.chisimba-site-banner__utilities \{[^}]*inset-block-end: \.55rem;'
            . '[^}]*position: absolute;/s',
        $css
    ) === 1,
    'utility rail returns to flow on narrower screens' => preg_match(
        '/@media \(max-width: 64rem\) \{.*?'
            . '\.chisimba-site-banner__utilities \{[^}]*position: static;/s',
        $css
    ) === 1,
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}

fwrite(STDOUT, "Header journey navigation contract: PASS\n");
