<?php
/**
* Class adminmenu extends ChisimbaObject.
* @package toolbar
* @filesource
*/

// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']){
    die();
}

/**
* Admin Menu Class
* This class generates a context lefthand side admin navigation menu
*
* @author Tohir Solomons
* @author Paul Scott <pscott@uwc.ac.za>
* @copyright (c) 2004 University of the Western Cape
* @package toolbar
* @version 1
*/
class adminmenu extends ChisimbaObject
{
    /**
    * Constructor method to instantiate objects and get variables
    */
    public function init()
    {
        $this->objLanguage= $this->getObject('language','language');
        $this->moduleCheck=$this->newObject('modules','modulecatalogue');
        $this->securityContext = $this->getObject('toolbarsecuritycontext');
        $this->objUserPic = $this->getObject('imageupload', 'useradmin');
        $this->globalTable=$this->newObject('htmltable','htmlelements');
        $this->globalTable->cellpadding=5;
        $this->globalTable->width='99%';
        $this->globalTable->cssClass='adminmenu-table';
        $this->icons = $this->getObject('iconservice', 'ui');
    }

    /**
    * This method returns the finished menu
    *
    * @return string $menu - the finished menu
    */
    public function show()
    {
        $userTitle = $this->getObject('htmlheading', 'htmlelements');
        $userTitle->type=2;
        $userTitle->str=$this->securityContext->displayName();
        $menu =  $userTitle->show();
        $menu .= '<img class="userimage" src="'
            . $this->objUserPic->userpicture(
                $this->securityContext->userId()
            )
            . '" />';
        $menu .= $this->createMenuTable();
        return $menu;
    }

    /**
    * This method checks whether the modules are registered and available for the context, and then adds them to the menu
    * @return string $this->globalTable - the finished table of menu items
    */
    public function createMenuTable()
    {
        // Check if contextadmin is registered
        $module = $this->moduleCheck->getModuleInfo('contextadmin');
        if ($module['isreg']) {
            $this->addNavigationRow(ucwords($module['name']), 'book-open', 'contextadmin');
        }
        // Check if useradmin is registered
        $module = $this->moduleCheck->getModuleInfo('useradmin');
        if ($module['isreg']) {
            $this->addNavigationRow($module['name'], 'user-cog', 'useradmin');
        }
        // Check if groupadmin is registered
        $module = $this->moduleCheck->getModuleInfo('groupadmin');
        if ($module['isreg']) {
            $this->addNavigationRow($module['name'], 'users-round', 'groupadmin');
        }
        // Check if permissions is registered
        $module = $this->moduleCheck->getModuleInfo('permissions');
        if ($module['isreg']) {
            $this->addNavigationRow($module['name'], 'shield-check', 'permissions');
        }
        // Certificate designs and signers are shared site resources.
        $module = $this->moduleCheck->getModuleInfo('certificate-service');
        if ($module['isreg']) {
            $this->addNavigationRow($module['name'], 'scroll-text', 'certificate-service');
        }
        // Check if moduleadmin is registered
        $module = $this->moduleCheck->getModuleInfo('moduleadmin');
        if ($module['isreg']) {
            $this->addNavigationRow($module['name'], 'boxes', 'moduleadmin');
        }
        // Check if createlang is registered
        $module = $this->moduleCheck->getModuleInfo('createlang');
        if ($module['isreg']) {
            $this->addNavigationRow($module['name'], 'languages', 'createlang');
        }
        // Check if extensions is registered
        $module = $this->moduleCheck->getModuleInfo('extensions');
        if ($module['isreg']) {
            $this->addNavigationRow($module['name'], 'puzzle', 'extensions');
        }
        // Check if languagetext is registered
        $module = $this->moduleCheck->getModuleInfo('languagetext');
        if ($module['isreg']) {
            $this->addNavigationRow($module['name'], 'text', 'languagetext');
        }
        // Check if serverstatus is registered
        $module = $this->moduleCheck->getModuleInfo('serverstatus');
        if ($module['isreg']) {
            $this->addNavigationRow($module['name'], 'server-cog', 'serverstatus');
        }
        // Check if viewsource is registered
        $module = $this->moduleCheck->getModuleInfo('viewsource');
        if ($module['isreg']) {
            $this->addNavigationRow($module['name'], 'code-2', 'viewsource');
        }

        return $this->globalTable->show();
    }

    /**
    * Add one translated Site Administration destination.
    *
    * @param string $moduleName Translated module name.
    * @param string $iconName Curated semantic icon name.
    * @param string $moduleId Destination module.
    */
    public function addNavigationRow($moduleName, $iconName, $moduleId=null)
    {
        $this->loadClass('link', 'htmlelements');
        $this->globalTable->startRow();
        $iconMarkup = $this->icons->render(
            $iconName,
            array('decorative' => true, 'class' => 'adminmenu-icon')
        );
        $this->globalTable->addCell($iconMarkup, 20, 'absmiddle', 'center');
        $moduleLink = new link($this->uri(null, $moduleId));
        $moduleLink->link = $moduleName;
        $this->globalTable->addCell($moduleLink->show(), null, 'absmiddle');
        $this->globalTable->endRow();
    }
}
?>
