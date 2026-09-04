<?php
/**
* Class menu extends ChisimbaObject.
* @package toolbar
* @filesource
*/

// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']){
    die("You cannot view this page directly");
}

/**
* Class for building the toolbar for KEWL.nextgen.
*
* The class builds a css style menu from the list of modules based on which modules
* the user has premission to access.
*
* @author Megan Watson
* @copyright (c)2004 UWC
* @package toolbar
* @version 0.9
*/

class menu extends ChisimbaObject
{
    /**
    * @var $contextCode The current context code
    * @access private
    */
    private $contextCode = '';

    /**
    * Method to construct the class.
    **/
    function init()
    {
        $this->cssMenu = $this->getObject('cssmenu');
        $this->flatMenu = $this->getObject('flatmenu');
        $this->dbmenu = $this->getObject('dbmenu');
        $this->tools = $this->getObject('tools');

        $this->objLanguage = $this->getObject('language','language');
        $this->objSkin = $this->newObject('skin','skin');
        $this->securityContext = $this->getObject('toolbarsecuritycontext');
        $this->objSysConfig = $this->getObject('dbsysconfig','sysconfig');
        $this->objModule = $this->getObject('modules', 'modulecatalogue');
        $this->objTable = $this->newObject('htmltable','htmlelements');
        $this->objLayer = $this->getObject('layer','htmlelements');

        $this->objContext = $this->getObject('dbcontext','context');
        $this->objDbConMod = $this->getObject('dbcontextmodules','context');
        $this->contextCode = $this->objContext->getContextCode();
        $this->context = FALSE; $this->im = FALSE;
        $this->objUserId = $this->securityContext->userId();
        // First check if the user is in a context
        if(!empty($this->contextCode)){
            $this->context = TRUE;
        }
    }

    /**
    * Method to get the list of modules for building the menu.
    * The method gets a list of modules from the database based on whether
    * they're context dependent or admin only. A function is called to check
    * which modules the user has permission to access. The menu is then built using
    * the cssmenu class.
    * @return string $menu The menu.
    */
    function menuBar()
    {
        $access = 2;

        if($this->securityContext->isSiteAdministrator()){
            $access = 1;
        }

        // get category and module data
        $rows = $this->dbmenu->getModules($access, $this->context);

        if(!empty($rows)){
            // Check user permissions and get a nested array of categories and modules
            $rows = $this->isVisible($rows);
            return $this->journeyMenus($rows);
        }

        // My Learning is intentionally a journey destination rather than a
        // legacy module-menu declaration, so it can still form a useful menu.
        return $this->journeyMenus(array());
    }

    /**
     * Turn legacy module categories into a small, stable launch navigation.
     *
     * Direct module permissions remain authoritative. This method only decides
     * where an already-visible destination belongs and suppresses empty menus.
     */
    private function journeyMenus($legacyMenus)
    {
        $journeys = array(
            'learning' => array(),
            'teaching' => array(),
            'administration' => array(),
            'information' => array(),
        );
        $moduleJourney = array(
            'calendar' => 'learning',
            'assignment' => 'teaching',
            'essay' => 'teaching',
            'essayadmin' => 'teaching',
            'gradebook' => 'teaching',
            'mcqtests' => 'teaching',
            'offlineassessment' => 'teaching',
            'pbl' => 'teaching',
            'pbladmin' => 'teaching',
            'practicals' => 'teaching',
            'rubric' => 'teaching',
            'tutorials' => 'teaching',
            'worksheet' => 'teaching',
            'worksheetadmin' => 'teaching',
            'toolbar' => 'administration',
            'about' => 'information',
            'announcements' => 'information',
            'blog' => 'information',
            'faq' => 'information',
            'help' => 'information',
            'simpleblog' => 'information',
        );
        $mayTeach = $this->securityContext->isSiteAdministrator()
            || ($this->context
                ? $this->securityContext->isCurrentContextLecturer()
                : $this->securityContext->isLecturer());
        $isAdmin = $this->securityContext->isSiteAdministrator();

        if ($this->securityContext->hasStudentLearning()
            && $this->objModule->checkIfRegistered('mylearning')) {
            $journeys['learning'][] = 'mylearning';
        }
        if ($this->securityContext->isLecturer()
            && $this->objModule->checkIfRegistered('myteaching')) {
            $journeys['teaching'][] = 'myteaching';
        }
        if ($isAdmin && $this->objModule->checkIfRegistered('mylearning')) {
            $journeys['learning'][] = $this->journeyLink(
                'mylearning', array('action' => 'manage'),
                'mod_toolbar_managemylearning', 'Manage My Learning page'
            );
        }
        if ($isAdmin && $this->objModule->checkIfRegistered('myteaching')) {
            $journeys['teaching'][] = $this->journeyLink(
                'myteaching', array('action' => 'manage'),
                'mod_toolbar_managemyteaching', 'Manage My Teaching page'
            );
        }
        if ($isAdmin && $this->objModule->checkIfRegistered('myadmin')) {
            $journeys['administration'][] = 'myadmin';
            $journeys['administration'][] = $this->journeyLink(
                'myadmin', array('action' => 'manage'),
                'mod_myadmin_manage', 'Manage My Administration page'
            );
        }

        if ($this->context) {
            $journeys['learning'][] = $this->journeyLink(
                'context', NULL, 'mod_toolbar_coursehome', '[-context-] home'
            );
            if ($mayTeach) {
                $journeys['teaching'][] = $this->journeyLink(
                    'context', array('action' => 'controlpanel'),
                    'mod_toolbar_coursecontrolpanel', '[-context-] control panel'
                );
                if ($this->objModule->checkIfRegistered('contextcontent')
                    && $this->objDbConMod->isContextPlugin(
                        $this->contextCode,
                        'contextcontent'
                    )) {
                    $journeys['teaching'][] = $this->journeyLink(
                        'contextcontent', NULL,
                        'mod_toolbar_coursecontent', '[-context-] content'
                    );
                }
                if ($this->objModule->checkIfRegistered('contextgroups')
                    && $this->objDbConMod->isContextPlugin(
                        $this->contextCode,
                        'contextgroups'
                    )) {
                    $journeys['teaching'][] = $this->journeyLink(
                        'contextgroups', NULL,
                        'mod_toolbar_coursemembers', '[-context-] members'
                    );
                }
            }
        } else {
            $journeys['learning'][] = $this->journeyLink(
                'context', array('action' => 'catalogue'),
                'mod_toolbar_browsecourses', 'Browse [-contexts-]'
            );
            if ($mayTeach && $this->objModule->checkIfRegistered('contextadmin')) {
                $journeys['teaching'][] = $this->journeyLink(
                    'contextadmin', NULL,
                    'mod_toolbar_courseadministration', '[-context-] administration'
                );
                $journeys['teaching'][] = $this->journeyLink(
                    'contextadmin', array('action' => 'add'),
                    'mod_toolbar_createcourse', 'Create [-context-]'
                );
            }
        }

        foreach ((array) $legacyMenus as $modules) {
            foreach ((array) $modules as $module) {
                $module = strtolower(trim((string) $module));
                if (!isset($moduleJourney[$module])) {
                    continue;
                }
                $journey = $moduleJourney[$module];
                if ($journey === 'teaching' && !$mayTeach) {
                    continue;
                }
                if ($journey === 'administration' && !$isAdmin) {
                    continue;
                }
                if (!in_array($module, $journeys[$journey], true)) {
                    $journeys[$journey][] = $module;
                }
            }
        }

        $result = array();
        foreach ($journeys as $journey => $modules) {
            if ($modules !== array()) {
                $result[ucwords($journey)] = $modules;
            }
        }
        return $result;
    }

    /** Build one explicit task destination without bypassing module access. */
    private function journeyLink($module, $params, $languageKey, $fallback)
    {
        return array(
            'module' => $module,
            'params' => $params,
            'label' => ucwords($this->objLanguage->code2Txt(
                $languageKey, 'toolbar', NULL, $fallback
            )),
        );
    }

    /**
    * Method to check Module visibility and permissions.
    * The users permission to access each module is checked.
    * If in a context, the context aware modules are checked to determine
    * whether they are visible.
    * The method then creates an array of categories with a nested array of
    * the visible modules for each category.
    * @param array $data Array of all registered modules
    * @return array $menu Array of visible modules
    */
    function isVisible($data)
    {
        $i=0; $menu=array(); $visModules = array();

        if($this->context){
            $visibleMod = $this->objDbConMod->getContextModules($this->contextCode);
            if (!empty($visibleMod)) {
                foreach($visibleMod as $vis){
                    $moduleId = is_array($vis) ? ($vis['moduleid'] ?? '') : $vis;
                    if ($moduleId !== '') {
                        $visModules[] = strtolower((string) $moduleId);
                    }
                }
            }
        }

        foreach($data as $item){
            if($this->tools->checkPermissions($item, $this->context)){
                if ($this->context
                    && !empty($item['dependscontext'])
                    && !in_array(strtolower($item['module']), $visModules, true)) {
                    continue;
                }
                if(!empty($item['category'])){
                    $menu[$item['category']][]=$item['module'];
                }
            }
        }
        return $menu;
    }

    /**
    * Method to build up the menu from the list of categories and modules.
    * @param array $items Array of categories and modules.
    * @return string $menu The menu for display.
    */
    function buildMenu($modules)
    {
        // build menu
        if(is_array($modules)) {
            foreach($modules as $key=>$item){
                $category = strtolower($key);
                $this->cssMenu->addHeader($this->objLanguage->languageText('category_'.$category,'toolbar', ucwords($category)));
                foreach($item as $k=>$v){
                    if (is_array($v) && isset($v['module'], $v['label'])) {
                        $this->cssMenu->addMenuItem(
                            $this->objLanguage->languageText(
                                'category_'.$category,
                                'toolbar',
                                ucwords($category)
                            ),
                            $v['label'],
                            $v['module'],
                            isset($v['params']) ? $v['params'] : NULL
                        );
                        continue;
                    }
                    if(!(strpos(strtolower($v), 'context')===FALSE)){
                        $array = array('context'=>'course');
                        $text = $this->objLanguage->code2Txt('mod_'.$v.'_toolbarname',$v);
                    }else{
                        $text = $this->objLanguage->code2Txt('mod_'.$v.'_name',$v);
                    }
                    $text= $this->objLanguage->abstractText($text);
                    $this->cssMenu->addMenuItem($this->objLanguage->languageText('category_'.$category, 'toolbar',ucwords($category)), ucwords($text),$v);
                }
            }
        }

        return $this->cssMenu->show();
    }

    /**
    * Method to build up the menu from the list of categories and modules.
    * @param array $items Array of categories and modules.
    * @return string $menu The menu for display.
    */
    function buildFlatMenu($modules)
    {
        // build menu
        if(!empty($modules)){
            foreach($modules as $key=>$item){
                $module = $item['module'];
                $catArr = explode('|', $item['category']);
                $action = isset($catArr[1]) ? $catArr[1] : '';
                $catArr2 = explode('_', $catArr[0]);
                $category = isset($catArr2[1]) ? strtolower($catArr2[1]) : strtolower($item['module']);

                $text = $this->objLanguage->code2Txt('mod_'.$module.'_'.$category, $module);
                $this->flatMenu->addItem($module, $text, $action);
            }
        }
        return $this->flatMenu->show();
    }

    /**
    * Method to display the toolbar.
    * @return string $navbar The toolbar.
    */
    function show()
    {
        if($this->tools->check()){
            // check session for a custom toolbar
            $toolBar = $this->getSession('toolbar', NULL);
            // if set call class toolbar from module obtained from the session variable
            if($toolBar){
                $objToolMod = $this->getObject('newtoolbar', $toolBar);
                return $objToolMod->createToolbar();
            }

            $toolbarType = $this->objSysConfig->getValue('TOOLBAR_TYPE', 'toolbar');
            switch(strtolower($toolbarType)){
                case 'flat':
                    return $this->createFlatToolbar();
                case 'elearning':
                    $elearnToolbar = $this->getObject('toolbar_elearn');
                    return $elearnToolbar->show();
                default:
                    return $this->createToolbar();
            }
        }
        return '';
    }

    /**
    * Method to set or unset a session variable.
    * The session variable contains the name of a module. The module then creates a method called
    * createToolbar() in a class called newtoolbar. The method creates a modified toolbar to replace
    * the standard toolbar.
    * @param string $module The module containing the newtoolbar class.
    * @param bool $set Determines whether to set or unset the session.
    */
    function setToolbar($module, $set = TRUE)
    {
        if($set){
           $this->setSession('toolbar', $module);
        }else{
            $this->unsetSession('toolbar');
        }
    }

    /**
    * Method to create a flat toolbar
    *
    * @access private
    */
    private function createFlatToolbar()
    {
        $access = 2;

        if($this->securityContext->isSiteAdministrator()){
            $access = 1;
        }

        $modList = $this->dbmenu->getFlatModules($access, $this->context);
        $modules = array();

        // Check permissions
        if(!empty($modList)){
            foreach($modList as $item){
                if($this->tools->checkPermissions($item, $this->context)){
                    $modules[] = $item;
                }
            }
        }
        $menu = $this->buildFlatMenu($modules);

        $navbar = '<div id="menu">'.$menu.'</div>';
        return $navbar;
    }

    /**
    * Method to create the standard toolbar
    */
    function createToolbar()
    {
        $menu = FALSE;
        $iconList = array();

        // get slide out menus
        $modules = $this->menuBar();
        
        $menu = $this->buildMenu($modules);

        if(!$menu) {
            $menu='';
        }

        // Keep Logout inside the active menu so desktop and mobile share one
        // canonical POST/CSRF control. It is deliberately the final item.
        if ($this->securityContext->isAuthenticated()) {
            $logoutForm = $this->securityContext->logoutForm(
                $this->objLanguage->languageText(
                    'word_logout',
                    'system',
                    'Logout'
                ),
                'toolbar-menu-logout'
            );
            $logoutItem = '<li class="toolbar-logout-item">'
                . $logoutForm
                . '</li>';
            $menuEnd = strrpos($menu, '</ul>');
            if ($menuEnd !== false) {
                $menu = substr_replace($menu, $this->getObject('notificationmenu', 'toolbar')->show() . $logoutItem, $menuEnd, 0);
            }
        }

        // get breadcrumbs
        $crumbs=$this->tools->navigation();

        $bookmark = $this->tools->addBookmark();
        if($bookmark){
            $iconList[] = $bookmark;
        }

        /*
         * Legacy HTML instant messaging was retired in Milestone 12.
         *
         * A future Chisimba messaging facility should be service-backed,
         * mobile-capable, and suitable for integrations such as Discord.
         */

        $pause = $this->tools->addPause();
        if($pause){
            $iconList[] = $pause;
        }

        /*
         * The legacy toolbar Help icon was retired in Milestone 12.
         *
         * Help can return later as an accessible documentation and support
         * feature rather than as an isolated toolbar icon.
         */

        $iconsStr = '';
        $divider = '';

        foreach ($iconList as $icon)
        {
            $iconsStr .= $divider.$icon;
            $divider = '&nbsp;';
        }

        //removed id="menu" from the div because it looks crap
        $navbar = '<div id="menu">'.$menu.'</div>'
          . '<div id="tooliconslist">'.$iconsStr.'</div>'
          . $crumbs
          . $this->tools->addStatusbar();

        return $navbar;
    }

    /**
    * Method to get extra parameters
    *
    * @access public
    * @param array $headerParams The array of parameters added to the header
    * @param array $bodyOnload The array of parameters for body onload
    * @return
    */
    public function getParams(&$headerParams, &$bodyOnLoad)
    {
        $toolbarType = $this->objSysConfig->getValue('TOOLBAR_TYPE', 'toolbar');
        
        if ($toolbarType == 'dropdown')
        {
            // get from the tools class
            $params = $this->tools->params;
        }
        elseif ($toolbarType == 'elearning')
        {
            $tools = $this->getObject('toolbar_elearn');
            $params = $tools->params;
        }

        if(!empty($params)){
            foreach($params as $param){
                foreach ($param as $key => $item)
                {
                    // append new parameter
                    switch($key){
                        case 'headerParams':
                            if(!is_array($headerParams) || empty($headerParams)){
                                $headerParams = array();
                                $headerParams[] = $item;
                                break;
                            }
                            if (!in_array($item, $headerParams)){
                                $headerParams[] = $item;
                            }
                            break;

                        case 'bodyOnLoad':
                            if(!is_array($bodyOnLoad) || empty($bodyOnLoad)){
                                $bodyOnLoad = array();
                                $bodyOnLoad[] = $item;
                                break;
                            }
                            if (!in_array($item, $bodyOnLoad)){
                                $bodyOnLoad[] = $item;
                            }
                            break;
                    }
                }
            }
        }
        return '';
    }

    /**
     * Create the Menu navigation
     *
     * @access public
     * @return string
     */
    public function navigationMenu()
    {
        $str = '<ul id="nav">
                <li class="first"><a href="#">Home</a></li>
                <li class="active"><a href="#">User</a>
                    <ul>
                    <li class="first"><a href="#">Blog</a></li>
                    <li class="active"><a href="#">Chat</a></li>
                    <li><a href="#">Photo Gallery</a></li>
                    <li><a href="#">Mailing List</a></li>
                    <li><a href="#">Discussion Forum</a></li>

                    <li class="last"><a href="#">Internal Email</a></li>
                    </ul>
                </li>
                <li><a href="#">Resources</a>
                    <ul>
                    <li class="first"><a href="#">Discussion Forum</a></li>
                    <li class="last"><a href="#">Wiki</a></li>
                    </ul>
                </li>
                <li><a href="#">Admin</a>
                    <ul>
                    <li class="first"><a href="#">Maecenas</a></li>
                    <li><a href="#">Phasellus</a></li>
                    <li><a href="#">Mauris sollicitudin</a></li>
                    <li><a href="#">Phasellus</a></li>
                    <li><a href="#">Mauris sollicitudin</a></li>
                    <li><a href="#">Phasellus</a></li>
                    <li><a href="#">Mauris sollicitudin</a></li>
                    <li><a href="#">Phasellus</a></li>
                    <li><a href="#">Mauris sollicitudin</a></li>
                    <li><a href="#">Phasellus</a></li>
                    <li><a href="#">Mauris sollicitudin</a></li>
                    <li class="last"><a href="#">Mauris at enim</a></li>
                    </ul>
                </li>
                <li class="last"><a href="#">About</a>
                    <ul>

                    <li class="last"><a href="#">Credits</a></li>
                    </ul>
                </li>
                </ul>';

        return $str;

    }
}
?>
