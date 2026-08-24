<?php
/** Static contract for the canonical, separate My Learning landing surface. */
$root = dirname(__DIR__);
$coreModules = dirname($root);
$controller = file_get_contents($root . '/controller.php');
$template = file_get_contents($root . '/templates/content/main_tpl.php');
$resolver = file_get_contents($root . '/classes/landingresolver_class_inc.php');
$bestGuess = file_get_contents($coreModules . '/utilities/classes/bestguess_class_inc.php');
$contextController = file_get_contents($coreModules . '/context/controller.php');
$sideMenu = file_get_contents($coreModules . '/toolbar/classes/sidemenu_class_inc.php');
$engine = file_get_contents(dirname($coreModules) . '/classes/core/engine_class_inc.php');
$overview = file_get_contents(
    $coreModules . '/context/classes/studentlearningoverview_class_inc.php'
);
$skin = file_get_contents(
    dirname($coreModules) . '/skins/chisimba-reborn/stylesheet.css'
);

$checks = array(
    'Site Home remains a general page' => !str_contains($controller, 'studentlearningoverview')
        && !str_contains($template, '$myLearningOverview'),
    'resolver recognises only student-only accounts' => str_contains($resolver, 'isStudentOnly')
        && str_contains($resolver, 'getContextWhereStudent')
        && str_contains($resolver, 'getContextWhereLecturer'),
    'default navigation uses the landing resolver' => str_contains($bestGuess, "getObject('landingresolver', 'postlogin')")
        && str_contains($bestGuess, 'defaultModule'),
    'clean site root uses the landing resolver' => str_contains(
        $engine,
        "'landingresolver',"
    ) && str_contains($engine, '->defaultModule($defaultModule)'),
    'leaving a course resolves before context is cleared' => strpos($contextController, 'leaveCourseModule')
        < strpos($contextController, 'leaveContext()'),
    'student account menu names both destinations' => str_contains($sideMenu, 'addStudentHomeLinks')
        && str_contains($sideMenu, "'mylearning'")
        && str_contains($sideMenu, "'postlogin'"),
    'overview reuses learning journey state' => str_contains($overview, "getObject('learningjourney', 'contextcontent')")
        && str_contains($overview, 'getState($code, $userId)'),
    'course continuation joins the correct context' => str_contains($overview, "'action' => 'joincontext'")
        && str_contains($overview, "'contextmodule' => 'contextcontent'")
        && str_contains($overview, "'contextaction' => 'viewpage'"),
    'shared skin owns presentation' => str_contains($skin, 'CHISIMBA MY LEARNING OVERVIEW'),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}
fwrite(STDOUT, "PASS: separate My Learning landing contract\n");
