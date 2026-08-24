<?php

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$iconService = $this->getObject('iconservice', 'ui');
$contextCode = (string) $this->objContext->getContextCode();
$taskLinks = array(
    array(
        'icon' => 'house',
        'label' => $objLanguage->code2Txt('mod_context_opencontextpage', 'context', null, 'Open [-context-] page'),
        'help' => $objLanguage->code2Txt('mod_context_opencontextpagehelp', 'context', null, 'See the [-context-] as its members see it.'),
        'url' => $this->uri(null, 'context'),
    ),
    array(
        'icon' => 'book-open-text',
        'label' => $objLanguage->code2Txt('mod_context_managecontent', 'context', null, 'Manage content'),
        'help' => $objLanguage->code2Txt('mod_context_managecontenthelp', 'context', null, 'Create, import and organise chapters and pages.'),
        'url' => $this->uri(null, 'contextcontent'),
    ),
    array(
        'icon' => 'users',
        'label' => $objLanguage->code2Txt('mod_context_managepeople', 'context', null, 'Manage members'),
        'help' => $objLanguage->code2Txt('mod_context_managepeoplehelp', 'context', null, 'Add or remove students, lecturers and guests.'),
        'url' => $this->uri(null, 'contextgroups'),
    ),
    array(
        'icon' => 'user-cog',
        'label' => $objLanguage->code2Txt('mod_context_managelecturers', 'context', null, 'Manage lecturers and ownership'),
        'help' => $objLanguage->code2Txt('mod_context_managelecturershelp', 'context', null, 'Assign lecturers or transfer ownership of this [-context-].'),
        'url' => $this->uri(array('action' => 'authors', 'contextcode' => $contextCode), 'contextadmin'),
    ),
    array(
        'icon' => 'settings',
        'label' => $objLanguage->code2Txt('mod_context_editsettings', 'context', null, 'Edit settings'),
        'help' => $objLanguage->code2Txt('mod_context_editsettingshelp', 'context', null, 'Change availability, access, title and course image.'),
        'url' => $this->uri(array('action' => 'updatesettings'), 'context'),
    ),
    array(
        'icon' => 'blocks',
        'label' => $objLanguage->code2Txt('mod_context_managetools', 'context', null, 'Manage course tools'),
        'help' => $objLanguage->code2Txt('mod_context_managetoolshelp', 'context', null, 'Choose the learning and assessment tools used here.'),
        'url' => $this->uri(array('action' => 'manageplugins'), 'context'),
    ),
);

$ret = '<div class="course-control-workspace"><header class="course-control-header">'
    . '<h1>' . $escape($objLanguage->code2Txt('mod_context_courseadministration', 'context', null, '[-context-] administration')) . '</h1>'
    . '<p>' . $escape($contextTitle) . '</p></header>'
    . '<nav class="course-control-tasks" aria-label="'
    . $escape($objLanguage->code2Txt('mod_context_commontasks', 'context', null, 'Common course-administration tasks')) . '"><h2>'
    . $escape($objLanguage->code2Txt('mod_context_commontasks', 'context', null, 'Common tasks')) . '</h2><ul>';
foreach ($taskLinks as $task) {
    $ret .= '<li><a class="chisimba-navigation-action course-control-task" href="' . $escape($task['url']) . '"><span class="course-control-task__icon">'
        . $iconService->render($task['icon'], array('decorative' => true))
        . '</span><span><strong>' . $escape($task['label']) . '</strong><small>'
        . $escape($task['help']) . '</small></span></a></li>';
}
$ret .= '</ul></nav>';
$cpBlocks = array();
$objBlocks = $this->getObject('blocks', 'blocks');
$cpBlocks[] = $objBlocks->showBlock('contextsettings', 'context', NULL, 20, TRUE, FALSE);
$cpBlocks[] = $objBlocks->showBlock('sasiwebserver', 'sasicontext', NULL, 20, TRUE, FALSE);
$cpBlocks[] = $objBlocks->showBlock('contextmembers', 'contextgroups', NULL, 20, TRUE, FALSE);
$cpBlocks[] = $objBlocks->showBlock('contextmodules', 'context', NULL, 20, TRUE, FALSE);
//$cpBlocks[] = $objBlocks->showBlock('contextstats', 'context', NULL, 20, TRUE, FALSE);
$ret .= '<div class="course-control-details">';
foreach ($cpBlocks as $block) {
    if (trim((string) $block) !== '') {
        $ret .= '<div class="course-control-details__item">' . $block . '</div>';
    }
}
$ret .= '</div></div>';
echo $ret;
?>
