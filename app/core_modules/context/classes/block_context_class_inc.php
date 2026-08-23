<?php

/**
 * Context blocks
 * 
 * Chisimba Context blocks class
 * 
 * PHP version 5
 * 
 * This program is free software; you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License as published by 
 * the Free Software Foundation; either version 2 of the License, or 
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License 
 * along with this program; if not, write to the 
 * Free Software Foundation, Inc., 
 * 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 * 
 * @category  Chisimba
 * @package   context
 * @author    Wesley Nitsckie <wnitsckie@uwc.ac.za>
 * @copyright 2007 Wesley Nitsckie
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */


// security check - must be included in all scripts
if (!
/**
 * Description for $GLOBALS
 * @global entry point $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 */
$GLOBALS['kewl_entry_point_run'])
{
    die("You cannot view this page directly");
}
// end security check


/**
 * Context blocks
 * 
 * Chisimba Context blocks class
 * 
 * @category  Chisimba
 * @package   context
 * @author    Wesley Nitsckie <wnitsckie@uwc.ac.za>
 * @copyright 2007 Wesley Nitsckie
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
class block_context extends ChisimbaObject
{
    /**
    * @var string $title The title of the block
    */
    public $title;
    
    /**
    * @var object $objLanguage String to hold the language object
    */
    private $objLanguage;

    /**
    * Standard init function to instantiate language object
    * and create title, etc
    */
    public function init()
    {
        try {
            $this->objLanguage =  $this->getObject('language', 'language');
            $this->title = ucWords($this->objLanguage->code2Txt('mod_context_allcontexts', 'context', NULL, 'All [-contexts-]'));
            
        } catch (customException $e) {
            customException::cleanUp();
        }
    }
    
    /**
    * Standard block show method. 
    */
    public function show()
    {
        try {
        $objContext = $this->getObject('dbcontext', 'context');
        $courses = $objContext->getListOfPublicContext();
        if ((is_countable($courses) ? count($courses) : 0)==0) {
            $msg = $this->objLanguage->code2Txt('mod_context_nocontexts','context');
            return "<span class='noRecordsMessage'>$msg</span>";
            
        } else {
            $output = '<nav class="course-shortcuts" aria-label="'
                . htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8')
                . '"><ul class="course-shortcuts__list">';
            foreach ($courses AS $course) {
                $title = !empty($course['title'])
                    ? $course['title'] : $course['menutext'];
                $output .= '<li><a class="course-shortcuts__link" href="'
                    . $this->uri(array(
                        'action' => 'joincontext',
                        'contextcode' => $course['contextcode'],
                    ), 'context') . '"><span>'
                    . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
                    . '</span><span class="course-shortcuts__arrow" aria-hidden="true">&rsaquo;</span></a></li>';
            }
            $output .= '</ul><a class="course-shortcuts__catalogue" href="'
                . $this->uri(array('action' => 'catalogue'), 'context')
                . '">Browse course catalogue</a></nav>';
            return $output;
        }

        } catch (customException $e) {
            customException::cleanUp();
        }
    }
}
?>
