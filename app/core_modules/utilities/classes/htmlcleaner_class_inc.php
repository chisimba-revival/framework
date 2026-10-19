<?php

/* -------------------- dbTable class ----------------*/
if (!
/**
 * Description for $GLOBALS
 * @global unknown $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 */
$GLOBALS['kewl_entry_point_run']){
    die("You cannot view this page directly");
}
// end security check/**

/**
* Class to clean html
*
* @package utilities
* @category utilities
* @author Wesley  Nitsckie
* @copyright 2004, University of the Western Cape & AVOIR Project
* @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General
Public License
* @version   $Id$
* @link      http://avoir.uwc.ac.za
*/

require_once dirname(__DIR__, 3) . '/classes/core/chisimbahtmlsanitizer.php';
class htmlcleaner  extends ChisimbaObject
{
   /**
     * @var object $_purifier The shared maintained HTML purifier
     * @access protected
     */
    protected $_purifier;

    /**
     * Constructor
     */
    public function init()
    {

        $this->_purifier = ChisimbaHtmlSanitizer::purifier();
    }

    /**
     * Method to clean the html
     * @param string $html the html
     * @return string the clean scrubbed html
     * @access public
     * @author Wesley Nitsckie
     */
    public function cleanHtml($html = NULL)
    {
        if(is_null($html))
        {
            return $html;
        } else {
            return $this->_purifier->purify($html);
        }
    }

}
?>