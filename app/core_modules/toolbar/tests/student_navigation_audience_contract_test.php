<?php
/** Static contract for role-appropriate top-level navigation. */

$root = dirname(__DIR__);
$menu = file_get_contents($root . '/classes/menu_class_inc.php');
$security = file_get_contents(
    $root . '/classes/toolbarsecuritycontext_class_inc.php'
);

$assert = static function ($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
};

$assert(
    strpos($menu, "'administration' => array()") !== false
        && strpos($menu, "\$journey === 'administration' && !\$isAdmin") !== false,
    'Administration journey must be restricted to site administrators.'
);
$assert(
    strpos($menu, "'teaching' => array()") !== false
        && strpos($menu, "\$journey === 'teaching' && !\$mayTeach") !== false,
    'Teaching journey must be restricted to course lecturers and admins.'
);
$assert(
    strpos($menu, "'learning' => array()") !== false
        && strpos($menu, "'information' => array()") !== false
        && strpos($menu, "checkIfRegistered('mylearning')") !== false,
    'Launch navigation must provide bounded Learning and Information journeys.'
);
$assert(
    strpos($menu, 'if ($modules !== array())') !== false,
    'Empty journey menus must be suppressed.'
);
$assert(
    strpos($security, 'function isCurrentContextLecturer()') !== false
        && strpos($security, 'isContextLecturer($userId, $contextCode)') !== false,
    'Lecturer navigation must be decided for the current course.'
);
$assert(
    strpos($security, 'function hasStudentLearning()') !== false
        && strpos($security, 'getContextWhereStudent($userId)') !== false
        && strpos($menu, 'hasStudentLearning()') !== false,
    'My Learning must be shown only when the account has a learner journey.'
);

fwrite(STDOUT, "PASS: student navigation audience contract\n");
