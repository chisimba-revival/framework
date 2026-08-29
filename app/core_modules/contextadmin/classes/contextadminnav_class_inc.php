<?php

/**
 * Context Admin Navigation
 * 
 * Class to generate a navigation for context admin
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
 * @package   contextadmin
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2008 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 */
// security check - must be included in all scripts
if (!
/**
 * Description for $GLOBALS
 * @global entry point $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 */
$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check


/**
 * Context Admin Navigation
 * 
 * Class to generate a navigation for context admin
 * 
 * @category  Chisimba
 * @package   contextadmin
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2008 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 */
class contextadminnav extends ChisimbaObject
{
    
    /**
     * Constructor
     */
    public function init()
    {
        $this->loadClass('htmlheading', 'htmlelements');
        $this->loadClass('form', 'htmlelements');
        $this->loadClass('textinput', 'htmlelements');
        $this->loadClass('button', 'htmlelements');
        $this->loadClass('hiddeninput', 'htmlelements');
        $this->loadClass('link', 'htmlelements');
        
        $this->objLanguage = $this->getObject('language', 'language');
    }
    
    /**
     * Method to display the navigation
     */
    public function show()
    {
        $objUser = $this->getObject('user', 'security');
        $objContext = $this->getObject('dbcontext', 'context');
        $contextCode = trim((string) $this->getParam('contextcode', $objContext->getContextCode()));
        $currentAction = (string) $this->getParam('action', '');
        $inContext = $contextCode !== '' && $contextCode !== 'root'
            && ($objUser->isAdmin() || $objUser->isContextLecturer($objUser->userId(), $contextCode));
        $items = array();
        if ($inContext) {
            $items[] = array('icon'=>'layout-dashboard','label'=>$this->objLanguage->code2Txt('mod_contextadmin_coursecontrolpanel','contextadmin',NULL,'[-context-] Control Panel'),'url'=>$this->uri(array('action'=>'controlpanel'), 'context'));
            $items[] = array('icon'=>'settings','label'=>$this->objLanguage->code2Txt('mod_contextadmin_editsettings','contextadmin',NULL,'Edit settings'),'url'=>$this->uri(array('action'=>'updatesettings'), 'context'));
            $items[] = array('icon'=>'list-checks','label'=>$this->objLanguage->code2Txt('mod_contextadmin_fullwizard','contextadmin',NULL,'Full [-context-] wizard'),'url'=>$this->uri(array('action'=>'edit','contextcode'=>$contextCode), 'contextadmin'));
            $items[] = array('icon'=>'users','label'=>$this->objLanguage->code2Txt('mod_contextadmin_managemembers','contextadmin',NULL,'Manage [-context-] members'),'url'=>$this->uri(array(), 'contextgroups'));
            if ($currentAction !== 'authors') {
                $items[] = array('icon'=>'user-cog','label'=>$this->objLanguage->code2Txt('mod_contextadmin_manageauthors','contextadmin'),'url'=>$this->uri(array('action'=>'authors','contextcode'=>$contextCode), 'contextadmin'));
            }
        }
        $items[] = array('icon'=>'book-open','label'=>$this->objLanguage->code2Txt('phrase_mycourses','system',NULL,'My [-contexts-]'),'url'=>$this->uri(NULL));
        if ($objUser->isAdmin() || $objUser->isLecturer()) {
            $items[] = array('icon'=>'plus','label'=>$this->objLanguage->code2Txt('mod_contextadmin_createcontext','contextadmin',NULL,'Create [-context-]'),'url'=>$this->uri(array('action'=>'add')));
        }
        $e = static function($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
        $icons = $this->getObject('iconservice','ui');
        $html = '<nav class="contextadmin-navigation" aria-label="' . $e($this->objLanguage->code2Txt('mod_contextadmin_navigation','contextadmin',NULL,'[-context-] administration')) . '"><h2>'
            . $e(ucwords($this->objLanguage->code2Txt('mod_contextadmin_name','contextadmin',NULL,'[-context-] Admin'))) . '</h2><ul>';
        foreach ($items as $item) {
            $html .= '<li><a class="chisimba-navigation-action" href="' . $e($item['url']) . '"><span>'
                . $icons->render($item['icon'], array('decorative'=>true)) . '</span><strong>' . $e(ucwords($item['label'])) . '</strong></a></li>';
        }
        return $html . '</ul></nav>';
    }
    
    /**
     * Method to generate an alphabetical navigation of courses
     */
    public function getAlphaListingTable()
    {
        $objContext = $this->getObject('dbcontext', 'context');
        $sql = 'SELECT title FROM tbl_context WHERE access != \'Private\' ORDER BY title';
        $results = $objContext->getArray($sql);
        
        $available = array();
        
        if ((is_countable($results) ? count($results) : 0) > 0) {
            foreach ($results as $title)
            {
                $letter = substr(strtoupper(trim($title['title'])), 0, 1);
                
                @$available[$letter]++;
            }
        }
        
        
        // Character Bounds for A, Z
        $lBound=65;
        $uBound=90;
        
        $perLine = 7;
        $counter = 0;
        
        $numContexts = ucwords($this->objLanguage->code2Txt('mod_contextadmin_numcontexts', 'contextadmin', NULL, '[-num-] [-contexts-]'));
        $oneContext = ucwords($this->objLanguage->code2Txt('mod_contextadmin_onecontext', 'contextadmin', NULL, '1 [-context-]'));
        
        $table = $this->newObject('htmltable', 'htmlelements');
        
        for ($i=$lBound; $i<=$uBound; $i++)
        {
            if ($counter % $perLine == 0) {
                $table->startRow();
            }
            
            if (array_key_exists(chr($i), $available)) {
                $alphaLink = new link ($this->uri(array('action'=>'browseother', 'letter'=>chr($i))));
                $alphaLink->link = chr($i);
                
                if ($available[chr($i)] == 1) {
                    $alphaLink->title = $oneContext;
                    $alphaLink->alt = $oneContext;
                } else {
                    $alphaLink->title = str_replace('[-num-]', $available[chr($i)], $numContexts);
                    $alphaLink->alt = str_replace('[-num-]', $available[chr($i)], $numContexts);
                }
                
                $str = $alphaLink->show();
            } else {
                $str = chr($i);
            }
            
            $table->addCell($str, round(100/$perLine).'%');
            
            
            $counter++;
            
            if ($counter % $perLine == 0) {
                $table->endRow();
            }
        }
        
        //echo $perLine - ($i % $perLine);
        
        if ($counter % $perLine != 0) {
            for ($j = ($perLine - ($counter % $perLine)); $j--; $j >= 0)
            {
                $table->addCell('&nbsp;', round(100/$perLine).'%');
            }
            
            $table->endRow();
        }
        
        $table->extra = 'border="1"';
        
        return $table->show();
    }
    
    
    /**
     * Method to generate an alphabetical navigation of courses
     */
    public function getAlphaListingAjax()
    {
        $objContext = $this->getObject('dbcontext', 'context');
        $sql = 'SELECT title FROM tbl_context WHERE access != \'Private\' ORDER BY title';
        $results = $objContext->getArray($sql);
        
        $available = array();
        
        if ((is_countable($results) ? count($results) : 0) > 0) {
            foreach ($results as $title)
            {
                $letter = substr(strtoupper(trim($title['title'])), 0, 1);
                
                @$available[$letter]++;
            }
        }
        
        $alphaListing = '';
        $divider = ' | ';
        
        // Character Bounds for A, Z
        $lBound=65;
        $uBound=90;
        
        $numContexts = ucwords($this->objLanguage->code2Txt('mod_contextadmin_numcontexts', 'contextadmin', NULL, '[-num-] [-contexts-]'));
        $oneContext = ucwords($this->objLanguage->code2Txt('mod_contextadmin_onecontext', 'contextadmin', NULL, '1 [-context-]'));
        
        $table = $this->newObject('htmltable', 'htmlelements');
        
        for ($i=$lBound; $i<=$uBound; $i++)
        {
            
            if (array_key_exists(chr($i), $available)) {
                $alphaLink = new link ("javascript:getContexts('".chr($i)."');");
                $alphaLink->link = chr($i);
                
                if ($available[chr($i)] == 1) {
                    $alphaLink->title = $oneContext;
                    $alphaLink->alt = $oneContext;
                } else {
                    $alphaLink->title = str_replace('[-num-]', $available[chr($i)], $numContexts);
                    $alphaLink->alt = str_replace('[-num-]', $available[chr($i)], $numContexts);
                }
                
                $str = $alphaLink->show();
            } else {
                $str = chr($i);
            }
            
            $alphaListing .= $str.$divider;
        }
        
        
        return $alphaListing;
    }
}

?>
