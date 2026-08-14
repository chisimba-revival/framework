<?php

/**
 * Latest courses block.
 *
 * @category  Chisimba
 * @package   context
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @version   Release: @package_version@
 * @link      https://github.com/chisimba-revival/framework
 */

if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

/**
 * Latest courses block.
 *
 * @category  Chisimba
 * @package   context
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
class block_latestcourses extends ChisimbaObject
{
    /** @var object Language service. */
    public $objLanguage;

    /** @var string Translated block title. */
    public $title;

    /** @var boolean Whether the block wrapper should constrain the content. */
    public $wrapStr;

    /**
     * Set the translated block title and wide-block behaviour.
     *
     * @return void
     * @access public
     */
    public function init()
    {
        $this->objLanguage = $this->getObject('language', 'language');
        $this->title = $this->objLanguage->code2Txt(
            'mod_context_latestcourses',
            'context',
            null,
            'Latest courses'
        );
        $this->wrapStr = false;
    }

    /**
     * Render up to six newest published courses.
     *
     * @return string Rendered course catalogue block
     * @access public
     */
    public function show()
    {
        return $this->getObject('coursecatalogue', 'context')->renderLatest(6);
    }
}

?>
