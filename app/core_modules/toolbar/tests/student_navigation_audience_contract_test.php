<?php
/** Static contract for role-appropriate top-level navigation. */

$root = dirname(__DIR__);
$menu = file_get_contents($root . '/classes/menu_class_inc.php');
$security = file_get_contents(
    $root . '/classes/toolbarsecuritycontext_class_inc.php'
);
$cssMenu = file_get_contents($root . '/classes/cssmenu_class_inc.php');

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
$assert(
    strpos($menu, "'action' => 'join'") !== false
        && strpos($menu, "'action' => 'add'") !== false
        && strpos($menu, "'action' => 'controlpanel'") !== false,
    'Journey menus must expose useful root and current-course tasks.'
);
$assert(
    strpos($security, 'function isLecturer()') !== false,
    'Root teaching navigation must recognise lecturers outside course context.'
);
$assert(
    strpos($cssMenu, "'params' => \$params") !== false
        && strpos($cssMenu, 'uri($params, $link)') !== false,
    'One module must support several distinct task links without URL encoding shortcuts.'
);

fwrite(STDOUT, "PASS: student navigation audience contract\n");
