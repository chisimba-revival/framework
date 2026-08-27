<?php
$context = dirname(__DIR__);
$frameworkModules = dirname($context);
$database = file_get_contents($context . '/classes/dbcontext_class_inc.php');
$catalogue = file_get_contents($context . '/classes/coursecatalogue_class_inc.php');
$contextRegister = file_get_contents($context . '/register.conf');
$adminTemplate = file_get_contents($frameworkModules . '/contextadmin/templates/content/step1.php');
$adminRegister = file_get_contents($frameworkModules . '/contextadmin/register.conf');

$checks = array(
    'canonical persistence accepts masterclass' => str_contains(
        $database,
        "array('standard', 'microlearning', 'masterclass')"
    ),
    'course editor offers masterclass' => str_contains(
        $adminTemplate,
        "addOption('masterclass'"
    ),
    'masterclass has a useful definition' => str_contains(
        $adminRegister,
        'A short, focused class on a specific topic, typically one to three hours in duration.'
    ),
    'format guidance follows the selection' => str_contains($adminTemplate, 'delivery-format-help')
        && str_contains($adminTemplate, 'JSON.parse(help.dataset.formatDescriptions'),
    'course cards identify masterclasses' => str_contains($catalogue, "\$format === 'masterclass'")
        && str_contains($contextRegister, 'mod_context_formatmasterclass'),
    'module versions expose both updates' => str_contains($contextRegister, 'MODULE_VERSION: 2.030')
        && str_contains($adminRegister, 'MODULE_VERSION: 1.424'),
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}

echo 'OK: ' . count($checks) . " masterclass course-format checks\n";
