<?php
/** Contract checks for canonical course-aware deep links. @author Derek Keats */
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$service = file_get_contents($root . '/classes/courseawarelaunchservice_class_inc.php');
$confirm = file_get_contents($root . '/templates/content/course_activity_launch_tpl.php');
$denied = file_get_contents($root . '/templates/content/course_activity_denied_tpl.php');
$checks = array(
    'shared launcher owns deep-link wrapping' => str_contains($service, "'action' => 'launchcourseactivity'")
        && str_contains($service, "'targetmodule'") && str_contains($service, "'targetparams'"),
    'course launch requires login' => str_contains($controller, "'launchcourseactivity', 'entercourseactivity'"),
    'membership is checked before dispatch' => str_contains($controller, 'isContextMember(')
        && strpos($controller, 'mayLaunchCourseTarget($target)') < strpos($controller, 'dispatchCourseTarget($target)'),
    'non-members keep the destination while course entry is attempted' => str_contains($controller, 'validCourseLaunchTarget($target)')
        && str_contains($controller, 'objContext->joinContext(')
        && strpos($controller, 'objContext->joinContext(') < strrpos($controller, 'mayLaunchCourseTarget($target)'),
    'destination module must be enabled in the course' => str_contains($controller, 'objContextModules->isVisible('),
    'direct module URLs have a reusable active-scope guard' => str_contains($service, 'mayUseActiveCourse(')
        && str_contains($service, '$courseCode === \'root\'') && str_contains($service, 'isContextMember('),
    'active scope may dispatch directly' => str_contains($controller, '$this->contextCode === (string) $target[\'coursecode\']'),
    'course change requires POST and CSRF' => str_contains($controller, "REQUEST_METHOD")
        && str_contains($controller, 'COURSE_LAUNCH_CSRF') && str_contains($controller, 'csrf->consume'),
    'confirmation names the course' => str_contains($confirm, 'You are not in this course')
        && str_contains($confirm, '$courseLaunchTitle') && str_contains($confirm, 'Enter course'),
    'non-members receive recovery not a destination' => str_contains($denied, 'not a member of this course')
        && str_contains($denied, 'Return to My Learning'),
    'destination is internal and bounded' => str_contains($service, "preg_match('/^[A-Za-z0-9_-]{1,128}$/")
        && str_contains($service, 'count($values) > 20'),
);
$failed = false;
foreach ($checks as $name => $passed) {
    echo ($passed ? 'PASS: ' : 'FAIL: ') . $name . PHP_EOL;
    $failed = $failed || !$passed;
}
exit($failed ? 1 : 0);
