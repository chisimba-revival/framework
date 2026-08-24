<?php
/**
 * Render course lecturers from canonical group membership.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   contextgroups
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2007 Tohir Solomons
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */

if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

/**
 * Course Members control-panel block.
 *
 * @category Chisimba
 * @package  contextgroups
 * @author   Derek Keats <derek@dkeats.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
class block_contextmembers extends ChisimbaObject
{
    /** @var object Course repository. */
    public $objContext;

    /** @var object Language service. */
    public $objLanguage;

    /** @var string Active course code. */
    public $contextCode;

    /**
     * Load services required by the block.
     *
     * @return void
     */
    public function init()
    {
        $this->objContext = $this->getObject('dbcontext', 'context');
        $this->contextCode = $this->objContext->getContextCode();
        $this->objLanguage = $this->getObject('language', 'language');
        $this->title = ucwords($this->objLanguage->code2Txt(
            'mod_contextgroups_toolbarname',
            'contextgroups',
            null,
            'Manage [-CONTEXT-] members'
        ));
    }

    /**
     * Render lecturers and the course-membership management action.
     *
     * @return string HTML fragment.
     */
    public function show()
    {
        if ($this->contextCode === 'root' || $this->contextCode === '') {
            return '';
        }

        $groups = $this->getObject('groupservice', 'groupadmin');
        $lecturerGroupId = $groups->groupIdForName(
            $this->contextCode . '^Lecturers'
        );
        $lecturers = $lecturerGroupId === false
            ? array()
            : $groups->getMembers($lecturerGroupId);
        $iconService = $this->getObject('iconservice', 'ui');
        $roleLabel = ucwords($this->objLanguage->code2Txt(
            'mod_contextgroups_rolelecturer',
            'contextgroups',
            null,
            '[-AUTHOR-]'
        ));

        if (count($lecturers) === 0) {
            $content = '<p class="course-control-members__empty">'
                . $this->escape($this->objLanguage->code2Txt(
                    'mod_contextgroups_nolecturers',
                    'contextgroups',
                    null,
                    'No [-AUTHORS-] in this [-CONTEXT-]'
                )) . '</p>';
        } else {
            $items = array();
            foreach ($lecturers as $lecturer) {
                $name = isset($lecturer['displayName'])
                    ? trim((string) $lecturer['displayName'])
                    : '';
                if ($name === '' && isset($lecturer['username'])) {
                    $name = trim((string) $lecturer['username']);
                }
                if ($name === '') {
                    continue;
                }
                $items[] = '<li><span class="course-control-member__icon">'
                    . $iconService->render('user', array(
                        'decorative' => true,
                        'class' => 'course-control-member__person-icon',
                    )) . '</span><span class="course-control-member__name">'
                    . $this->escape($name) . '</span><span '
                    . 'class="course-control-member__role">'
                    . $this->escape($roleLabel) . '</span></li>';
            }
            $content = '<ul class="course-control-members">'
                . implode('', $items) . '</ul>';
        }

        $manageLabel = $this->objLanguage->code2Txt(
            'mod_contextgroups_toolbarname',
            'contextgroups',
            null,
            'Manage [-CONTEXT-] members'
        );

        return $content . '<p class="course-control-action"><a href="'
            . $this->escape($this->uri(null, 'contextgroups')) . '">'
            . $this->escape($manageLabel) . '</a></p>';
    }

    /**
     * Escape a value for HTML output.
     *
     * @param mixed $value Value to escape.
     *
     * @return string Escaped value.
     */
    private function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
