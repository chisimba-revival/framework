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
if (strpos($template, "uri(array('action'=>'controlpanel'), 'context')") === false
    || strpos($register, 'mod_contextadmin_coursecontrolpanel|') === false) {
    $errors[] = 'Lecturer management must provide a clear Course Control Panel route.';
}
if (strpos($template, "'action'=>'edit'") !== false) {
    $errors[] = 'The lecturer workflow must not drop users into the four-step course editor.';
}

if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

echo "Author workflow regression contract passed.\n";
