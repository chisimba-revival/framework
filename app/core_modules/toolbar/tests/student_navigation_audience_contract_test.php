<?php
/** Static contract for role-appropriate top-level navigation. */

$root = dirname(__DIR__);
$menu = file_get_contents($root . '/classes/menu_class_inc.php');
$security = file_get_contents(
    $root . '/classes/toolbarsecuritycontext_class_inc.php'
);
$cssMenu = file_get_contents($root . '/classes/cssmenu_class_inc.php');
$sideMenu = file_get_contents($root . '/classes/sidemenu_class_inc.php');
$register = file_get_contents($root . '/register.conf');

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
    strpos($menu, "\$journeys['learning'][] = \$this->journeyLink(\n                'mylearning', array('action' => 'manage')") !== false
        && strpos($menu, "\$journeys['teaching'][] = \$this->journeyLink(\n                'myteaching', array('action' => 'manage')") !== false,
    'Dashboard management must stay with its corresponding journey menu.'
);
$assert(
    strpos($menu, "checkIfRegistered('myadmin')") !== false
        && strpos($sideMenu, "'nodeid' => 'myadmin'") !== false
        && strpos($sideMenu, "'nodeid' => 'manage-myadmin'") !== false,
    'Administrators must have separate My Administration view and management journeys.'
);
$assert(
    strpos($menu, "checkIfRegistered('myteaching')") !== false
        && strpos($menu, "if (\$mayTeach\n            && \$this->objModule->checkIfRegistered('myteaching'))") !== false
        && strpos($menu, "'mod_toolbar_managemyteaching'") !== false
        && strpos($sideMenu, "'nodeid' => 'myteaching'") !== false
        && strpos($sideMenu, "'nodeid' => 'manage-myteaching'") !== false,
    'Personal teaching and administrator page management must be separate journeys.'
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
    strpos($menu, "'action' => 'catalogue'") !== false
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
$assert(
    strpos($sideMenu, "checkIfRegistered('payment-service')") !== false
        && strpos($sideMenu, "'action' => 'tiers'") !== false,
    'The learner account card must expose the membership journey when payments are available.'
);
$assert(
    strpos($sideMenu, "checkIfRegistered(\$line['module'])") !== false,
    'Account-card links must disappear when their target module is not installed.'
);
$assert(
    strpos($sideMenu, 'addLearningJourneyLink') !== false
        && strpos($sideMenu, "'nodeid' => 'mylearning'") !== false,
    'The account card must keep the learner journey close at hand.'
);
$assert(
    strpos($menu, "'mod_toolbar_managemylearning'") !== false
        && strpos($menu, "array('action' => 'manage')") !== false
        && strpos($sideMenu, "'nodeid' => 'manage-mylearning'") !== false
        && strpos($sideMenu, 'hasStudentLearning()') !== false,
    'Personal learning and administrator page management must be separate journeys.'
);
$assert(
    strpos($menu, "!empty(\$item['dependscontext'])") !== false
        && strpos($menu, "in_array(strtolower(\$item['module']), \$visModules, true)") !== false
        && strpos($menu, "isContextPlugin(\n                        \$this->contextCode,\n                        'contextcontent'") !== false,
    'Course toolbar destinations must be gated by Manage Course Tools.'
);
$assert(
    strpos($sideMenu, "!empty(\$module['dependscontext'])") !== false
        && strpos($sideMenu, 'getContextModules($this->contextcode)') !== false,
    'Course sidebar destinations must be gated by Manage Course Tools.'
);
$assert(
    strpos($sideMenu, "isset(\$node['uri']) && \$node['uri'] === \$moduleUri") !== false,
    'Account-card aggregation must suppress duplicate destinations.'
);
$assert(
    strpos($menu, "'label' => ucwords(\$this->objLanguage->code2Txt(") !== false
        && strpos($register, 'mod_toolbar_coursehome|Current context home journey action|[-context-] home') !== false
        && strpos($register, 'mod_toolbar_coursecontent|Current context content journey action|[-context-] content') !== false,
    'Shared journey labels must preserve configured context terminology.'
);

fwrite(STDOUT, "PASS: student navigation audience contract\n");
