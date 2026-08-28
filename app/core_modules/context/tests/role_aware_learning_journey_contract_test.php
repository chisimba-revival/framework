<?php
$root = isset($argv[1]) ? rtrim($argv[1], '/') : dirname(__DIR__);
$block = file_get_contents($root . '/classes/block_learningjourney_class_inc.php');
$register = file_get_contents($root . '/register.conf');
$checks = array(
    'manager role is detected' => strpos($block, 'isContextLecturer($this->objUser->userId(), $contextCode)') !== false,
    'administrator role is detected' => strpos($block, '$this->objUser->isAdmin()') !== false,
    'manager action is translated' => strpos($block, "'mod_context_managecoursecontent'") !== false,
    'manager destination is the content manager' => strpos($block, "array('action' => 'showcontextchapters'), 'contextcontent'") !== false,
    'manager action systext is registered' => strpos($register, 'mod_context_managecoursecontent|Course manager learning journey action|Manage course content') !== false,
);
$failed = array_keys(array_filter($checks, function ($passed) { return !$passed; }));
if ($failed) {
    foreach ($failed as $message) { fwrite(STDERR, "FAIL: $message\n"); }
    exit(1);
}
echo "Role-aware learning journey contract: PASS\n";
?>
