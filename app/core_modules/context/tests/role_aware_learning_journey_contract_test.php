<?php
$root = isset($argv[1]) ? rtrim($argv[1], '/') : dirname(__DIR__);
$block = file_get_contents($root . '/classes/block_learningjourney_class_inc.php');
$register = file_get_contents($root . '/register.conf');
$checks = array(
    'manager role is detected' => strpos($block, 'isCourseAdmin($contextCode)') !== false,
    'administrator role is detected' => strpos($block, '$this->objUser->isAdmin()') !== false,
    'manager action is translated' => strpos($block, "'mod_context_managerjourneyaction'") !== false,
    'manager destination is the course control panel' => strpos($block, "array('action' => 'controlpanel'), 'context'") !== false,
    'manager action systext is registered' => strpos($register, 'mod_context_managerjourneyaction|Course-manager journey action|Manage this [-context-]') !== false,
    'manager eyebrow uses context terminology' => strpos($register, 'mod_context_coursemanagement|Context-management journey eyebrow|[-context-] management') !== false,
    'manager prompt uses context terminology' => strpos($register, 'Manage learning, people and [-context-] settings') !== false,
    'manager labels parse system text' => substr_count($block, 'code2Txt(') >= 3,
);
$failed = array_keys(array_filter($checks, function ($passed) { return !$passed; }));
if ($failed) {
    foreach ($failed as $message) { fwrite(STDERR, "FAIL: $message\n"); }
    exit(1);
}
echo "Role-aware learning journey contract: PASS\n";
?>
