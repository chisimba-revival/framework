<?php
/**
 * Render the settings summary for the active course.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   context
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2008 Tohir Solomons
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */

if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

/**
 * Course Settings control-panel block.
 *
 * @category Chisimba
 * @package  context
 * @author   Derek Keats <derek@dkeats.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
class block_contextsettings extends ChisimbaObject
{
    /** @var object Course repository. */
    public $objContext;

    /** @var object Language service. */
    public $objLanguage;

    /** @var object System configuration repository. */
    public $objSysConfig;

    /** @var object Current-user service. */
    public $objUser;

    /** @var string Active course code. */
    public $contextCode;

    /**
     * Load services required by the block.
     *
     * @return void
     */
    public function init()
    {
        $this->objContext = $this->getObject('dbcontext');
        $this->contextCode = $this->objContext->getContextCode();
        $this->objLanguage = $this->getObject('language', 'language');
        $this->objSysConfig = $this->getObject('dbsysconfig', 'sysconfig');
        $this->objUser = $this->getObject('user', 'security');
        $this->title = ucwords($this->objLanguage->code2Txt(
            'mod_context_contextsettings',
            'context',
            null,
            '[-context-] Settings'
        ));
    }

    /**
     * Render the active course summary.
     *
     * @return string HTML fragment.
     */
    public function show()
    {
        if ($this->contextCode === 'root' || $this->contextCode === '') {
            return '';
        }

        $details = $this->objContext->getContextDetails($this->contextCode);
        if (!is_array($details)) {
            return '';
        }

        $title = isset($details['title']) ? (string) $details['title'] : '';
        $contextImage = $this->getObject('contextimage');
        $imageUrl = $contextImage->getContextImage($this->contextCode);
        if ($imageUrl === false || $imageUrl === '') {
            $iconService = $this->getObject('iconservice', 'ui');
            $media = '<span class="course-control-settings__placeholder">'
                . $iconService->render('image-plus', array(
                    'decorative' => true,
                    'class' => 'course-control-settings__placeholder-icon',
                )) . '</span>';
        } else {
            $media = '<img class="course-control-settings__image" src="'
                . $this->escape($imageUrl) . '" alt="'
                . $this->escape($title) . '">';
        }

        $rows = array(
            array(
                $this->objLanguage->code2Txt(
                    'mod_context_contexttitle',
                    'context',
                    null,
                    '[-context-] Title'
                ),
                $title,
            ),
            array(
                $this->objLanguage->code2Txt(
                    'mod_context_contextstatus',
                    'context',
                    null,
                    '[-context-] status'
                ),
                isset($details['status']) ? (string) $details['status'] : '',
            ),
        );

        if ($this->objSysConfig->getValue(
            'context_access_private_only',
            'context',
            'false'
        ) === 'false' || $this->objUser->isAdmin()) {
            $policy = strtolower(trim((string) ($details['access_policy'] ?? '')));
            $policyLabels = array(
                'public' => 'Public', 'free' => 'Free',
                'tier_1' => 'Tier 1', 'tier_2' => 'Tier 2',
                'private' => 'Private',
            );
            $accessLabel = isset($policyLabels[$policy])
                ? $policyLabels[$policy]
                : (isset($details['access']) ? (string) $details['access'] : '');
            if ($policy === 'private' && !empty($details['private_admission_mode'])) {
                $accessLabel .= $details['private_admission_mode'] === 'automatic_payment'
                    ? ' — admit after confirmed payment'
                    : ' — manual admission';
            }
            $rows[] = array(
                $this->objLanguage->languageText(
                    'mod_context_accessettings',
                    'context',
                    'Access Settings'
                ),
                $accessLabel,
            );
        }

        $summary = '<dl class="course-control-settings__details">';
        foreach ($rows as $row) {
            $summary .= '<div><dt>' . $this->escape(ucwords($row[0]))
                . '</dt><dd>' . $this->escape($row[1]) . '</dd></div>';
        }
        $summary .= '</dl>';

        return '<div class="course-control-settings"><div '
            . 'class="course-control-settings__media">' . $media . '</div>'
            . $summary . '</div>';
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
