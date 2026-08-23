<?php
/**
 * Guard the author-management workflow against broken action URLs and
 * unclear ownership-transfer guidance.
 */

$moduleRoot = dirname(__DIR__);
$template = file_get_contents($moduleRoot . '/templates/content/authors.php');
$register = file_get_contents($moduleRoot . '/register.conf');

if ($template === false || $register === false) {
    fwrite(STDERR, "Unable to read contextadmin author workflow files.\n");
    exit(1);
}

$errors = array();

if (strpos($template, "languageText('word_actions')") !== false) {
    $errors[] = 'The template still depends on the missing system word_actions item.';
}
if (strpos($template, "mod_contextadmin_actions") === false
    || strpos($register, 'TEXT: mod_contextadmin_actions|') === false) {
    $errors[] = 'The module-owned Actions language item is incomplete.';
}
if (strpos($template, '$e($this->uri(') !== false) {
    $errors[] = 'A generated URI is still escaped twice.';
}
if (substr_count($template, '<form method="post"') !== 3) {
    $errors[] = 'Expected exactly three author-management POST forms.';
}
if (substr_count($template, 'name="contextcode"') !== 3) {
    $errors[] = 'Every author-management POST form must retain the context code.';
}
if (strpos($template, 'mod_contextadmin_transferownershiphelp') === false
    || strpos($register, 'TEXT: mod_contextadmin_transferownershiphelp|') === false) {
    $errors[] = 'The two-step ownership-transfer guidance is incomplete.';
}
if (strpos($register, 'first add the new owner to [-authors-]') === false) {
    $errors[] = 'Ownership guidance must use the article-neutral [-authors-] system-text token.';
}
if (strpos($register, 'mod_contextadmin_backtocourseadmin|Back to course settings|Back to [-context-] settings') === false) {
    $errors[] = 'The return link must describe course settings.';
}

if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

echo "Author workflow regression contract passed.\n";
