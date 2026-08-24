<?php

/**
* Context Side Bar
*
* This class generates a navigation side bar for context modules
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
* @copyright 2008 Tohir Solomons
* @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
* @version   $Id$
* @link      http://avoir.uwc.ac.za
* @see       core
*/
/* -------------------- dbTable class ----------------*/
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
* Context Side Bar
*
* This class generates a navigation side bar for context modules
*
* @category  Chisimba
* @package   context
* @author    Tohir Solomons <tsolomons@uwc.ac.za>
* @copyright 2008 Tohir Solomons
* @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
* @version   Release: @package_version@
* @link      http://avoir.uwc.ac.za
* @see       core
*/
class contextsidebar extends ChisimbaObject
{


    /**
     * Constructor
     */
    public function init()
    {
        $this->objContext = $this->getObject('dbcontext');
        $this->contextCode = $this->objContext->getContextCode();
        $this->objLanguage = $this->getObject('language', 'language');
        $this->objUser = $this->getObject('user', 'security');
		$this->objContextGroups = $this->getObject('managegroups', 'contextgroups');
    }

    /**
     * Method to show the Side Bar
     *
     */
    public function show()
    {
        $str = '';
        $this->loadClass('htmlheading', 'htmlelements');
        $header = new htmlheading();
        $header->type = 2;
        $header->str = $this->objContext->getTitle();
        $str .= $header->show();
        // Put in the image for the context (course)
        $objContextImage = $this->getObject('contextimage');
        $contextImage = $objContextImage->getContextImage($this->contextCode);
        if ($contextImage !== FALSE) {
            $str .= '<p align="center"><img src="'.$contextImage.'" /></p><br />';
        }
        // Add the search form
        $str .= $this->searchForm();
        // Add all the context modules
        $objContextModules = $this->getObject('dbcontextmodules');
        $contextModules = $objContextModules->getContextModules($this->contextCode);
        $objModules = $this->getObject('modules', 'modulecatalogue');
        $mayManageLearning = $this->objUser->isAdmin()
            || $this->objContextGroups->isContextLecturer();
        $learnerHiddenModules = array(
            'contentblocks',
            'gradebook',
            'mcqtests',
            'assignment',
            'rubric',
            'worksheet',
            'essay',
            'essayadmin',
            'pbl',
            'pbladmin',
        );
        $nodes = array();
        $nodes[] = array('text'=>ucwords($this->objLanguage->code2Txt('mod_context_contexthome', 'context', NULL, '[-context-] Home')), 'uri'=>$this->uri(NULL, 'context'), 'nodeid'=>'context', 'css'=>'sidebarhomelink');
        if ((is_countable($contextModules) ? count($contextModules) : 0) > 0) {
            foreach ($contextModules as $module) {
                $moduleId = strtolower(trim((string) $module));
                if (!$mayManageLearning
                    && in_array($moduleId, $learnerHiddenModules, true)) {
                    continue;
                }
                $moduleInfo = $objModules->getModuleInfo($module);
                if ($moduleInfo['isreg']) {
                    // Make sure that Wall is not included as a link since it cannot be used this way
                    if (strtolower($module) !== 'wall') {
                        $nodes[] = array('text'=>ucwords($moduleInfo['name']), 'uri'=>$this->uri(NULL, $module), 'nodeid'=>$module);
                    }
                }
            }
        }
        if ($mayManageLearning) {
            $nodes[] = array('text'=>ucwords($this->objLanguage->code2Txt('mod_context_contextcontrolpanel', 'context', NULL, '[-context-] Control Panel')), 'uri'=>$this->uri(array('action'=>'controlpanel'), 'context'), 'nodeid'=>'controlpanel');
        }
        $nodes[] = array('text'=>ucwords($this->objLanguage->code2Txt('phrase_leavecourse', 'system', NULL, 'Leave [-context-]')), 'uri'=>$this->uri(array('action'=>'leavecontext'), 'context'), 'nodeid'=>'leavecontext');
        $objSideBar = $this->getObject('sidebar', 'navigation');
        $objSideBar->showHomeLink = FALSE;

        if ($this->getParam('module') == 'context') {
            if ($this->getParam('action') == 'controlpanel') {
                $activeId = 'controlpanel';
            } else {
                $activeId = 'context';
            }
        } else {
            $activeId = $this->getParam('module');
        }

        foreach ($nodes as &$node) {
            $node['current'] = isset($node['nodeid'])
                && $node['nodeid'] === $activeId;
        }
        unset($node);

        $str .= $objSideBar->show($nodes, $activeId);

        return "<div class='context_sidebar'>$str</div>";
    }

    /**
     * Method to generate a context search form
     */
    public function searchForm()
    {
        $this->loadClass('form', 'htmlelements');
        $this->loadClass('textinput', 'htmlelements');
        $this->loadClass('button', 'htmlelements');
        $this->loadClass('hiddeninput', 'htmlelements');

        $form = new form('contextsearch', $this->uri(array('action'=>'search'), 'context'));
        $form->method = 'GET';

        $module = new hiddeninput('module', 'context');
        $action = new hiddeninput('action', 'search');

        $form->addToForm($module->show().$action->show());

        $textinput = new textinput('search', $this->getParam('search'));

        $searchLabel = ucwords($this->objLanguage->code2Txt(
            'mod_context_searchcontext',
            'context',
            null,
            'Search [-context-]'
        ));
        $iconService = $this->getObject('iconservice', 'ui');
        $searchButton = '<button class="button context-sidebar-search__button" '
            . 'type="submit">' . $iconService->render('search', array(
                'decorative' => true,
                'class' => 'context-sidebar-search__icon',
            )) . '<span>' . htmlspecialchars(
                $searchLabel,
                ENT_QUOTES,
                'UTF-8'
            ) . '</span></button>';

        $form->addToForm('<div class="context-sidebar-search">'
            . $textinput->show() . $searchButton . '</div>');

        return $form->show();
    }

}
?>
