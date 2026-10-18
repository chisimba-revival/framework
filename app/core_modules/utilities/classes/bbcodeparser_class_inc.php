<?php
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check

/**
 * This class to convert a string to a XHTML compliant string with divs
 *
 * @category  Chisimba
 * @package   utitilies
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General
Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 */

require_once __DIR__.'/nativebbcode.php';

class bbcodeparser extends ChisimbaObject
{
    public function init() {}

    /** Format BBCode inside caller-owned HTML; this is not an HTML sanitiser. */
    public function parse4bbcode($text)
    {
        return ChisimbaBbcode::parse((string)$text);
    }
}
