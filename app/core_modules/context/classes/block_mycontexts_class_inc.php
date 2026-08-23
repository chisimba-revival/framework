<?php

/**
 * My Contexts block
 * 
 * A block to show the list of contexts a user belongs to
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
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2007 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id: block_context_class_inc.php 3591 2008-02-19 13:33:48Z tohir $
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
 * My Contexts block
 * 
 * A block to show the list of contexts a user belongs to
 * 
 * @category  Chisimba
 * @package   context
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2007 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
class block_mycontexts extends ChisimbaObject
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
            $this->objUserContext = $this->getObject('usercontext', 'context');
            $this->objUser = $this->getObject('user', 'security');
            $this->objContext =  $this->getObject('dbcontext');
            $this->objGroups =  $this->getObject('groupservice', 'groupadmin');
            $this->title = ucWords($this->objLanguage->code2Txt('phrase_mycourses', 'system', NULL, 'My [-contexts-]'));
            
        } catch (customException $e) {
            customException::cleanUp();
        }
    }
    
    /**
    * Standard block show method. 
    */
    public function show()
    {
        // Get all user contents
        $contexts = $this->objUserContext->getUserContext($this->objUser->userId());
        
        if ((is_countable($contexts) ? count($contexts) : 0) == 0) {
            return $this->objLanguage->code2Txt('mod_context_youdonotbelongtocontexts', 'context', NULL, 'You do not belong to any [-contexts-]');
        } else {
        
            $contextArray = array();
            
            foreach ($contexts AS $contextCode)
            {
               $contextDetails = $this->objContext->getContextDetails($contextCode);
               //check if this course is unpublished
               	
               if($contextDetails["status"] == "Unpublished"){
               		//if so check if this person is lecturer lecturer of the course
                    $groupId = $this->objGroups->groupIdForName($contextCode . '^Lecturers');
        			$ret = $this->objGroups->isGroupMember($this->objUser->userId(), $groupId);        			
        			if ($ret){
        				$contextArray[$contextDetails['title']] = $contextCode;
        			}
               }else{        		
        			$contextArray[$contextDetails['title']] = $contextCode;
               }
        		
        		
            }
            if((is_countable($contextArray) ? count($contextArray) : 0) < 1){
            	 return $this->objLanguage->code2Txt('mod_context_youdonotbelongtocontexts', 'context', NULL, 'You do not belong to any [-contexts-]');
            }
            
            ksort($contextArray);
            
            $output = '<nav class="course-shortcuts" aria-label="'
                . htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8')
                . '"><ul class="course-shortcuts__list">';
            foreach ($contextArray as $title=>$code) {
                $output .= '<li><a class="course-shortcuts__link" href="'
                    . $this->uri(array(
                        'action' => 'joincontext',
                        'contextcode' => $code,
                    ), 'context') . '"><span>'
                    . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
                    . '</span><span class="course-shortcuts__arrow" aria-hidden="true">&rsaquo;</span></a></li>';
            }
            return $output . '</ul></nav>';
        }
    }
}
?>
