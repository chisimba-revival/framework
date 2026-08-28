<?php
$app = dirname(dirname(__DIR__));
$sidebar = file_get_contents($app . '/context/classes/contextsidebar_class_inc.php');
$contextRegister = file_get_contents($app . '/context/register.conf');
$form = file_get_contents(dirname(__DIR__) . '/templates/content/step1.php');
$register = file_get_contents(dirname(__DIR__) . '/register.conf');
$checks = array(
    'sidebar exposes course settings' => strpos($sidebar, "'mod_context_coursesettings'") !== false
        && strpos($sidebar, "array('action' => 'edit', 'contextcode' => \$this->contextCode)") !== false,
    'sidebar systext is registered' => strpos($contextRegister, 'mod_context_coursesettings|Course manager navigation link|Course settings') !== false,
    'publication controls follow the title' => strpos($form, '$table->addCell($title->show())')
        < strpos($form, "'mod_contextadmin_courseavailability'")
        && strpos($form, "'mod_contextadmin_courseavailability'")
        < strpos($form, "'mod_contextadmin_learningdesign'")
        && substr_count($form, '$status->show()') === 1,
    'publication guidance is registered' => strpos($register, 'mod_contextadmin_courseavailabilityhelp|Course publication guidance|') !== false,
);
$failed = array_keys(array_filter($checks, function ($passed) { return !$passed; }));
if ($failed) {
    foreach ($failed as $message) { fwrite(STDERR, "FAIL: $message\n"); }
    exit(1);
}
echo "Course settings discoverability contract: PASS\n";
?>
