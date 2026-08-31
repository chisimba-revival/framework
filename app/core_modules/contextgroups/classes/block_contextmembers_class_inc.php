<?php
/**
 * Render course members from canonical group membership.
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
            'mod_contextgroups_contextmembers',
            'contextgroups',
            null,
            '[-CONTEXT-] members'
        ));
    }

    /**
     * Render lecturers, students, and guests in the active course.
     *
     * @return string HTML fragment.
     */
    public function show()
    {
        if ($this->contextCode === 'root' || $this->contextCode === '') {
            return '';
        }

        $groups = $this->getObject('groupservice', 'groupadmin');
        $iconService = $this->getObject('iconservice', 'ui');
        $roleGroups = array(
            array('group' => 'Lecturers', 'language' => 'mod_contextgroups_rolelecturer', 'fallback' => '[-AUTHOR-]'),
            array('group' => 'Students', 'language' => 'mod_contextgroups_rolestudent', 'fallback' => '[-READONLY-]'),
            array('group' => 'Guests', 'language' => 'mod_contextgroups_roleguest', 'fallback' => 'Guest'),
        );
        $items = array();

        foreach ($roleGroups as $roleGroup) {
            $groupId = $groups->groupIdForName(
                $this->contextCode . '^' . $roleGroup['group']
            );
            $members = $groupId === false
                ? array()
                : $groups->getMembers($groupId);
            $roleLabel = ucwords($this->objLanguage->code2Txt(
                $roleGroup['language'],
                'contextgroups',
                null,
                $roleGroup['fallback']
            ));

            foreach ($members as $member) {
                $name = isset($member['displayName'])
                    ? trim((string) $member['displayName'])
                    : '';
                if ($name === '' && isset($member['username'])) {
                    $name = trim((string) $member['username']);
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
        }

        if (count($items) === 0) {
            $content = '<p class="course-control-members__empty">'
                . $this->escape($this->objLanguage->code2Txt(
                    'mod_contextgroups_nomembers',
                    'contextgroups',
                    null,
                    'No members in this [-CONTEXT-]'
                )) . '</p>';
        } else {
            $content = '<ul class="course-control-members">'
                . implode('', $items) . '</ul>';
        }
        return $content;
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
