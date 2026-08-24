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
    strpos($menu, "\$category === 'admin'") !== false
        && strpos($menu, 'isSiteAdministrator()') !== false,
    'Admin category must be restricted to site administrators.'
);
$assert(
    strpos($menu, "\$category === 'assessment'") !== false
        && strpos($menu, 'isCurrentContextLecturer()') !== false,
    'Assessment category must be restricted to course lecturers and admins.'
);
$assert(
    strpos($security, 'function isCurrentContextLecturer()') !== false
        && strpos($security, 'isContextLecturer($userId, $contextCode)') !== false,
    'Lecturer navigation must be decided for the current course.'
);

fwrite(STDOUT, "PASS: student navigation audience contract\n");
