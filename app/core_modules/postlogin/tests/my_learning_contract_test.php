<?php
/** Static contract for the canonical My Learning post-login surface. */
$root = dirname(__DIR__);
$coreModules = dirname($root);
$controller = file_get_contents($root . '/controller.php');
$template = file_get_contents($root . '/templates/content/main_tpl.php');
$overview = file_get_contents(
    $coreModules . '/context/classes/studentlearningoverview_class_inc.php'
);
$skin = file_get_contents(
    dirname($coreModules) . '/skins/chisimba-reborn/stylesheet.css'
);

$checks = array(
    'postlogin composes My Learning' => str_contains($controller, 'studentlearningoverview')
        && str_contains($template, '$myLearningOverview'),
    'My Learning precedes generic blocks' => strpos($template, '$myLearningOverview')
        < strpos($template, '$middleBlocksStr'),
    'legacy My Courses block is suppressed when overview exists' => str_contains(
        $controller,
        "array('block|mycontexts|context')"
    ) && str_contains($controller, '$myLearningOverview ==='),
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
fwrite(STDOUT, "PASS: My Learning post-login contract\n");
