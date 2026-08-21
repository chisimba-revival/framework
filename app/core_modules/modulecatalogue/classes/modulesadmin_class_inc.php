<?php
/**
 * This file contains the modulesadmin class
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
 * @package   modulecatalogue
 * @author    Nic Appleby <nappleby@uwc.ac.za>
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 */

/* -------------------- dbTable class for dbmanagerdb ----------------*/
// security check - must be included in all scripts
if (!
/**
 * Description for $GLOBALS
 * @global unknown $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 */
$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check

/**
 * This class is used to for manipulating modules with administrative functionality.
 * Installation and uninstallation of modules is carried out through this module
 * Dividing the module class in two like this avoids loading this
 * file when only the basic user functionality is needed.
 *
 * @category  Chisimba
 * @package   modulecatalogue
 * @author    Nic Appleby <nappleby@uwc.ac.za>
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @see       modules class for non-administrative operations
*/

class modulesadmin extends dbTableManager
{
    /**
     * Current module ID
     *
     * @var string $module_id
     */
    private $module_id;

    /**
     * Current module's name
     *
     * @var string $module_name
     */
    private $module_name;

    /**
     * Descritpion of current module
     *
     * @var string $module_description
     */
    private $module_description;

    /**
     * Object interface to TableInfo class
     *
     * @var object objTableInfo
     */
    private $objTableInfo;

    /**
     * Code of last error encountered
     *
     * @var integer $_lastError
     */
    private $_lastError;


    /**
     * Description for public
     * @var    string
     * @access public
     */
    public $output;

    /**
     * object to manipulate the modules table
     *
     * @var object $objModules
     */
    protected $objModules;

    /**
     * User data object
     *
     * @var object $objUser
     */
    protected $objUser;

    /**
     * System configuration object
     *
     * @var object $objConfig
     */
    public $objConfig;

    /**
     * Property to handle the error reporting
     *
     * @var object $debug
     */
    public $debug;

    public $grId;

    /**
     * Standard initilisation method
     *
     */
    // MODULESADMIN_INIT_SIGNATURE_PHP82
    public function init($dbName = null, $pearDbManager = null, $errorCallback = 'globalPearErrorHandler')
    {
        try {
            parent::init('tbl_modules');
            $this->objLanguage = $this->getObject('language','language');
            $this->objConfig = $this->getObject('altconfig','config');
            $this->objModules = $this->getObject('modules');
            $this->objModFile = $this->getObject('modulefile');
            $this->objUser = $this->getObject('user','security');
            $this->objModuleBlocks = $this->getObject('dbmoduleblocks','modulecatalogue');
            $this->objSystext = $this->getObject('systext_facet','systext');
       } catch (Exception $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
     * Method to check whether a module dependency is registered
     *
     * @param  string     $moduleId the id of the dpendency
     * @return TRUE|FALSE
     */
    public function checkDependency($moduleId) {
        try {
            return $this->objModules->checkIfRegistered($moduleId);
        } catch (Exception $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * This is a method to register the module. It stores the module information in
    * the database table tbl_modules, creates any needed SQL tables,
    * adds languagetext elements, moves icons, etc. All based on info from
    * the module's 'register.conf' file.
    * @param   array $registerdata - all the info from the register.conf file.
    * @returns mixed OK | FALSE
    */
    public function installModule(&$registerdata,$update = FALSE) {
        try {
            $objGroups = $this->getObject('groupAdminModel', 'groupadmin');
            $allPresent = true;        //used to check if all modules are present on the system
            $this->_lastError = 0;
            if (isset($registerdata['MODULE_ID'])) {
                $moduleId=$registerdata['MODULE_ID'];
            } else {
                $this->_lastError = 1001;
                return FALSE; // If we can't find the name of the module we're supposed to be registering, what are we doing here?
            }
            $this->module_id=$registerdata['MODULE_ID'];
            $this->module_name=$registerdata['MODULE_NAME'];
            $this->module_description=$registerdata['MODULE_DESCRIPTION'];
            //$this->objModules->beginTransaction();

            //If the module already exists, do not register it, else register it
            if ($this->objModules->checkIfRegistered($moduleId) && !$update) {
                $this->_lastError = 1002;
                return FALSE;
            } else {
                // check for modules this one is dependant on
                if (isset($registerdata['DEPENDS'])) {
                    $missingModules = '';
                    foreach ($registerdata['DEPENDS'] as $depends) {
                        if (!$this->checkDependency($depends)) {
                            if (($fn = $this->objModFile->findRegisterFile($depends)) && (filesize($fn)>0)) {
                                $installed = $this->objLanguage->languageText('mod_modulecatalogue_notinstalled','modulecatalogue');
                            } else {
                                $installed = $this->objLanguage->languageText('mod_modulecatalogue_needdownload','modulecatalogue');
                                $allPresent = false;        //all modules not present
                            }
                            $missingModules .= "<b>$depends</b> - $installed<br />";
                            $this->_lastError = 1003;
                        }
                    }
                      if ($this->_lastError == 1003) {
                        $installDepsLink = &$this->getObject('link','htmlelements');
                        $installDepsLink->link($this->uri(array('action'=>'installwithdeps','mod'=>$registerdata['MODULE_ID'],
                                    'cat'=>$this->getParam('cat')),'modulecatalogue'));
                        $installDepsLink->link = str_replace('{MODULE}',$registerdata['MODULE_ID'],
                                    $this->objLanguage->languageText('mod_modulecatalogue_installdeps','modulecatalogue'));
                        $text = $this->objLanguage->languageText('mod_modulecatalogue_needmodule','modulecatalogue');
                        $text = str_replace('{MODULE}',"<b>{$registerdata['MODULE_ID']}</b>",$text);
                        $this->output = "<span id='confirm'>$text:</span><br />$missingModules";
                        if ($allPresent) {
                            $this->output .= $installDepsLink->show();
                        } else {
                            $this->output .= $this->objLanguage->languageText('mod_modulecatalogue_downloadmissing','modulecatalogue');
                        }
                        return FALSE;
                    }
                }
                // Now we add the tables
                if (isset($registerdata['TABLE'])) {
                    foreach ($registerdata['TABLE'] as $table) {
                        if (!$this->objModules->valueExists('tablename',$table,'tbl_modules_owned_tables')) {
                            $tableCreated = $this->makeTable($table);
                            if (!$tableCreated) {
                                $text=$this->objLanguage->languageText('mod_modulecatalogue_needinfo','modulecatalogue');
                                $text=str_replace('{MODULE}',$table,$text);
                                $this->output.='<b>'.$text.'</b><br />';
                                $this->_lastError = 1004;
                                return FALSE;
                            } else {
                                $sql="DELETE FROM tbl_modules_owned_tables WHERE kng_module='".$moduleId."' and tablename='".$table."'";
                                $this->objModules->query($sql);
                                // Add the table to the records.
                                $this->objModules->insert(array('kng_module' => $moduleId,'tablename' => $table),'tbl_modules_owned_tables');
                            }
                        }
                    }
                }
                // Here we load data into tables from files of SQL statements
                if (!$update) {
                    if (!$this->loadData($moduleId)) return FALSE;

                    // Canonical permission definitions own module areas.
                    $objPermissionService = $this->getObject(
                        'permissionservice',
                        'security'
                    );
                    $areaId = $objPermissionService->ensureArea(
                        'chisimba',
                        $moduleId
                    );
                    if (!is_int($areaId) || $areaId < 1) {
                        throw new RuntimeException(
                            'Canonical module permission area creation failed'
                        );
                    }
                }
            }
                // Create directory
                if(isset($registerdata['DIRECTORY'])){
                    foreach ($registerdata['DIRECTORY'] as $directory) {
                        $path =
                            $this->objConfig->getcontentBasePath()
                            .'/'.$directory
                            .'/';
                        if (!is_dir($path)) {
                            $objMkdir = $this->getObject('mkdir', 'files');
                            $objMkdir->mkdirs($path, 0777);
                        }
                    }
                }
                // Set up data for the site navigation: toolbar, sidemenus and pages
                $isAdmin = 0;
                $isContext = 0;
                $aclList = '';
                $permList = array();
                $groupArray = array();
                $groupArray2 = array();
                if(isset($registerdata['BLOCK'])) {
                    foreach ($registerdata['BLOCK'] as $block) {

                        $blockInfo = explode('|', $block);
                        if (!isset($blockInfo[1])) {
                            $blockInfo[1] = 'site';
                        }
                        //var_dump($blockInfo); die();

                       $this->objModuleBlocks->addBlock($moduleId, $blockInfo[0], 'normal', $blockInfo[1]);
                    }
                }
                if(isset($registerdata['WIDEBLOCK'])) {
                    foreach ($registerdata['WIDEBLOCK'] as $block) {

                        $blockInfo = explode('|', $block);
                        if (!isset($blockInfo[1])) {
                            $blockInfo[1] = 'site';
                        }

                        $this->objModuleBlocks->addBlock($moduleId, $blockInfo[0],'wide', $blockInfo[1]);
                    }
                }
                if(isset($registerdata['MODULE_ISADMIN'])){
                    $isAdmin = $registerdata['MODULE_ISADMIN'];
                }
                if(isset($registerdata['DEPENDS_CONTEXT'])){
                    $isContext = $registerdata['DEPENDS_CONTEXT'];
                }
                /*
                 * Toolbar owns menu authorization registration. This call
                 * defines one canonical module-scoped right, grants it through
                 * PermissionService, and returns only its positive identifier
                 * (or the empty public marker) for menu storage.
                 */
                $objToolbarRegister = $this->getObject(
                    'register',
                    'toolbar'
                );
                $aclList = $objToolbarRegister
                    ->canonicalRightForRegistration(
                        $registerdata,
                        $moduleId,
                        'default'
                    );

                /* Create a condition type
                if(isset($registerdata['CONDITION_TYPE'][0])){
                    $objType = $this->getObject('conditiontype','decisiontable');
                    $array = array(); $class = ''; $types = array();
                    foreach($registerdata['CONDITION_TYPE'] as $val){
                        $array = explode('|', $val);
                        $class = $array[0];
                        if(isset($array[1])){
                            $types = explode(',', $array[1]);
                            foreach($types as $type){
                                $objType->create($type, $class, $moduleId);
                                $objType->insert();
                            }
                        }
                    }
                }
*/
                /* Create conditions.
                    Create a condition in the decisiontable, returns the condition object.
                    Populate an array with condition objects for use in creating rules.
                */
                /*
                $conditions = array();
                if(isset($registerdata['CONDITION'][0])){
                    $array = array(); $list = ''; $paramList = array(); $name = ''; $params = '';
                    foreach($registerdata['CONDITION'] as $condition){
                        // $objCond = $this->newObject('condition','decisiontable');
                        $paramList = array(); $array = array(); $list = '';

                        $array = explode('|', $condition);

                        if(isset($array[2]) && !empty($array[2])){
                                    $list = explode(',', $array[2]);
                        }else{
                            $list = '';
                        }
                        $paramList = array();

                        if($array[1] == 'hasPermission'){
                            foreach($permList as $perm){
                                foreach($list as $val){
                                    if($perm == $val){
                                        $paramList[] = $perm;
                                    }
                                }
                            }
                        }else if($array[1] == 'isMember'){
                            foreach($list as $val){
                                foreach($groupArray as $perm){
                                    if($perm == $val){
                                        $paramList[] = $perm;
                                    }
                                }
                                foreach($groupArray2 as $perm2){
                                    if($perm2 == $val){
                                        $val = $perm2;
                                        $paramList[] = $val;
                                    }
                                }
                            }
                        }else{
                            $paramList = $list;
                        }

                        $name = $array[0];
                        if(!empty($paramList)){
                            $paramList2 = implode(',', $paramList);
                                    $params = $array[1].$objCond->_delimiterFunc.$paramList2;
                            }else{
                            $params = $array[1];
                        }

                        $conditions[$name] = $objCond->create($name, $params);
                        var_dump($conditions); die();
                    }
                }

                // Use existing conditions
                if(isset($registerdata['USE_CONDITION'][0])){
                    $objCond = $this->getObject('condition','decisiontable');
                    $name = ''; $array = array();
                    foreach($registerdata['USE_CONDITION'] as $condition){
                        $array = explode('|', $condition);
                        $name = $array[0];
                        $conditions[$name] = $objCond->create($name);
                    }
                }
*/
                /* Create rules.
                    Create the decisiontable for the module.
                    Create the action in the decisiontable, returns the action object.
                    Create the rule in the decisiontable, returns the rule object.
                    Add the action object to the rule object.
                    Add the condition object to the rule object.
                */
                if(isset($registerdata['RULE'][0])){
                    //$objDecisionTable = $this->getObject('decisiontable','decisiontable');
                    //$objAction = $this->getObject('action','decisiontable');
                    //$objAction->connect($objDecisionTable);
                    //$objRule = $this->getObject('rule','decisiontable');
                    //$objRule->connect($objDecisionTable);
                    $i = 1;
                    $ruleName = ''; $array = array(); $actionList = array(); $conditionList = array();

                    // Create the decision table
                    // $modTable = $objDecisionTable->create($moduleId);

                    foreach($registerdata['RULE'] as $rule){
                        // $ruleName = $moduleId.' rule '.$i++;
                        $array = explode('|', $rule);
                        $actionList = explode( ',', $array[0] );
                        //$conditionList = explode( ',', $array[1] );
                        // create the rules (rights).
                        $objPermissionService = $this->getObject(
                            'permissionservice',
                            'security'
                        );
                        $areaId = $objPermissionService->ensureArea(
                            'chisimba',
                            $moduleId
                        );
                        if (!is_int($areaId) || $areaId < 1) {
                            throw new RuntimeException(
                                'Canonical module permission area creation failed'
                            );
                        }
                        foreach ($actionList as $action) {
                            $rightId = $objPermissionService->ensureRight(
                                $areaId,
                                trim($action)
                            );
                            if (!is_int($rightId) || $rightId < 1) {
                                throw new RuntimeException(
                                    'Canonical module permission right creation failed'
                                );
                            }
                            if ($this->grId == null) {
                                $this->grId = $objGroups->getId($moduleId);
                            }
                            if (is_numeric($this->grId)
                                && (int) $this->grId > 0
                                && !$objPermissionService->ensureGroupGrant(
                                    (int) $this->grId,
                                    $rightId
                                )) {
                                throw new RuntimeException(
                                    'Canonical module permission group grant failed'
                                );
                            }
                        }



                        /* Create rule object and add to the decision table
                        //$rule = $objRule->create($ruleName);
                        // Add the rule to the decision table.
                        //$objDecisionTable->addRule( $rule );

                        // Create action object and add to decision table.
                        foreach( $actionList as $anAction ) {
                            $arrActions[$anAction] = $objAction->create($anAction);
                            // Add the action to the decision table.
                            $objDecisionTable->add( $arrActions[$anAction] );
                            // Add the rule to the action
                            $arrActions[$anAction]->add($rule);
                        }
                        // Add the condition to the rule
                        foreach( $conditionList as $aCondition ) {
                            if (array_key_exists($aCondition,$conditions)){
                                $rule->add($conditions[$aCondition]);
                            }
                        } */
                    }
                }

                // end Permissions and Security

                // Site Navigation
                // Use the same canonical registrar as Toolbar's full rebuild.
                // Replacement removes declarations which were renamed or
                // corrected instead of leaving stale menu rows behind.
                $objToolbarRegister->replaceData($registerdata);
                // end Site Navigation

                // Here we pass CONFIG data to the sysconfig module
                if (isset($registerdata['CONFIG']))
                {
                    $this->objSysConfig=$this->getObject('dbsysconfig','sysconfig');
                    $this->objSysConfig->updateFlag=TRUE;
                    $this->objSysConfig->registerModuleParams($moduleId,$registerdata['CONFIG']);
                }

                // Icons
                if (isset($registerdata['ICON'][0]))
                {
                    $this->moveIcons($moduleId,$registerdata['ICON']);
                }

                // Now the main data entry - building up arrays of the essential params
                isset($registerdata['MODULE_AUTHORS'])? $authors = $registerdata['MODULE_AUTHORS'] : $authors = '';
                isset($registerdata['MODULE_RELEASEDATE'])? $releasedate = $registerdata['MODULE_RELEASEDATE'] : $releasedate = '';
                isset($registerdata['MODULE_VERSION'])? $version = $registerdata['MODULE_VERSION'] : $version = '1.0';
                isset($registerdata['MODULE_PATH'])? $modPath = $registerdata['MODULE_PATH'] : $modPath = '';
                isset($registerdata['MODULE_HASADMINPAGE'])? $adminPage = $registerdata['MODULE_HASADMINPAGE'] : $adminPage = '0';
                isset($registerdata['ISADMIN'])? $adm = $registerdata['ISADMIN'] : $adm = 0;
                isset($registerdata['ISVISIBLE'])? $vis = $registerdata['ISVISIBLE'] : $vis = 1;
                $sql_arr = array(
                    'module_id' => $moduleId
                    ,'module_authors' => addslashes($authors)
                    // Begin JC
                    // Fixed bug in patching with MySQL 5.0
                    // mysql  Ver 14.12 Distrib 5.0.45, for Win32 (ia32).
                    ,'module_releasedate' => str_replace(' ','-',$releasedate)
                    // End JC
                    ,'module_version' => $version
                    ,'module_path' => $modPath
                    ,'isadmin' => $adm
                    ,'isvisible' => $vis
                    ,'hasadminpage' => $adminPage
                );
                if (isset($registerdata['CONTEXT_AWARE'])){
                    $sql_arr['iscontextaware']=$registerdata['CONTEXT_AWARE'];
                }
                if (isset($registerdata['DEPENDS_CONTEXT'])){
                    $sql_arr['dependscontext']=$registerdata['DEPENDS_CONTEXT'];
                }
                if (isset($registerdata['TAGS'])){
                    $tagsql = $registerdata['TAGS'];
                }
                else {
                    $tagsql = NULL;
                }
                if ($update) {
                    $this->objModules->update('module_id',$moduleId,$sql_arr,'tbl_modules');
                } elseif ($this->_lastError == 1004) {
                    // We have an error condition...
                    return FALSE;
                } else {
                    $status = $this->objModules->insert($sql_arr,'tbl_modules');
                    if (!$status) {
                           $this->_lastError = 1005;
                        return FALSE;
                    }
                    $this->objModules->insertTags($tagsql, $status, $moduleId);
                }
                //put the language information for name and description
                $this->registerModuleLanguageElements();
                $this->moduleText($registerdata['MODULE_ID'],'replace');
                if (isset($registerdata['DEPENDS'][0]))
                {
                    $this->registerDependentModules($moduleId,$registerdata['DEPENDS']);
                }

            //$this->objModules->commitTransaction(); //End the transaction;
        }

        catch (Exception $e) {
            //$this->objModules->rollbackTransaction();
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }

        return TRUE;
    }

     /**
    * This is a method to uninstall a module.
    * This method should check for modules that depend on the current module
    * and refuse to uninstall where there are dependencies. Instead of uninstalling
    * a module that has dependencies, it should give the option to remove the user
    * interface files and set the module isVisible flag to 0
    * @param   string $moduleId     the id of the module
    * @param   string $registerdata - array of info from the registration file
    * @returns boolean TRUE or FALSE
    */
    public function uninstallModule($moduleId,&$registerdata)
    {
        try {
            $objGroups = $this->getObject('groupAdminModel', 'groupadmin');
            $this->_lastError = 0;
            if (is_null($moduleId)) {
                $moduleId=$registerdata['MODULE_ID'];
            }

            // Mandatory baseline modules define a valid Chisimba installation
            // and may never enter the destructive uninstall sequence.
            $objMandatoryPolicy = $this->getObject(
                'mandatorymodulepolicy',
                'modulecatalogue'
            );
            if ($objMandatoryPolicy->isMandatory($moduleId)) {
                $this->output = 'Mandatory core module cannot be uninstalled: '
                    . $moduleId;
                $this->_lastError = 1007;
                return FALSE;
            }
            $this->objModuleBlocks->deleteModuleBlocks($moduleId);
            $modTitle="mod_{$moduleId}_name";
            $modDescription="mod_{$moduleId}_desc";
            //Check if there are modules that depend on this one
            $dependantModules=$this->objModules->getDependencies($moduleId);
            if (!empty($dependantModules)) {
                $str="<b>".$this->objLanguage->languageText('mod_modulecatalogue_hasdependants','modulecatalogue')."</b><br/>";
                foreach ($dependantModules as $dependantModule) {
                    $str.=$dependantModule."<br />";
                }
                $this->output = $str;
                $this->_lastError = 1003;
                return FALSE;
            } else {
                //$this->objModules->beginTransaction(); //Start a transaction;
                $this->objModules->delete('id',$modTitle,'tbl_en');
                $this->objModules->delete('code',$modTitle,'tbl_languagetext');
                $this->objModules->delete('id',$modDescription,'tbl_en');
                $this->objModules->delete('code',$modDescription,'tbl_languagetext');

                $texts=$this->listTexts($registerdata); // remove all specified texts
                if ($texts!==FALSE) {
                    foreach ($texts as $key=>$value) {
                        $this->removeText($key);
                    }
                }

                // Remove groups and acls for the module
                if(isset($registerdata['ACL'][0])){
                    //$objPerms = $this->getObject('permissions_model','permissions');
                    //$objGroups = $this->getObject('groupadminmodel','groupadmin');
                    foreach($registerdata['ACL'] as $perm){
                        $perms = explode('|', $perm);
                        var_dump($perms); die;
                        $aclId = $objPerms->getId($moduleId.'_'.$perms[0]);
                        $objPerms->deleteAcl($aclId);
                        if(isset($perms[1]) && !empty($perms[1])){
                            $groups = explode(',', $perms[1]);
                            foreach($groups as $group){
                                $groupId = $objGroups->getId($moduleId.'_'.$group);
                                $objGroups->deleteGroup($groupId);
                            }
                        }
                    }
                }

                /*// Remove decisiontable rules and actions
                $objDecisionTable = $this->getObject('decisiontable','decisiontable');
                $objDecisionTable->create($moduleId);
                $objDecisionTable->retrieve();
                $objDecisionTable->delete();

                // Remove module specific conditions
                if(isset($registerdata['CONDITION'])){
                    $objCond = $this->getObject('condition','decisiontable');
                    foreach($registerdata['CONDITION'] as $condition){
                        $array = explode('|', $condition);
                        $name = $array[0];
                        if(isset($array[2]) && !empty($array[2])){
                            $params = $array[1].'|'.$array[2];
                        } else {
                            $params = $array[1];
                        }
                        $conditions[$name] = $objCond->create($name, $params);
                        $conditions[$name]->retrieveId();
                        $conditions[$name]->delete();
                    }
                }*/

                // Remove navigation links
                $this->objModules->delete('module',$moduleId,'tbl_menu_category');

                // Here we remove CONFIG data from the sysconfig module
                $this->objSysConfig=$this->getObject('dbsysconfig','sysconfig');
                $this->objSysConfig->deleteModuleValues($moduleId);

                // Drop tables
                $droppedTables=$this->dropTables($moduleId);

                $this->objModules->delete('kng_module',$moduleId,'tbl_modules_owned_tables');
                $this->objModules->delete('module_id',$moduleId,'tbl_language_modules');
                $this->objModules->delete('module_id',$moduleId,'tbl_modules_dependencies');
                $this->objModules->delete('module_id',$moduleId,'tbl_modules');

                // finally clean up the module tags in tbl_tags
                $this->objModules->removeTags($moduleId);

                //$this->objModules->commitTransaction();//End the transaction;
                // clean up the permissions system
                // remove the module as an area from the Chisimba application
                $params = array('filters' => array('area_define_name' => $moduleId));
                $areas = $this->objLuAdmin->perm->getAreas($params);
                // var_dump($areas); die();
                if(is_array($areas) && !empty($areas)) {
                    $filters = array('area_id' => $areas[0]['area_id']);
                    $rmArea = $this->objLuAdmin->perm->removeArea($filters);
                    // get the group ID that we want to remove
                    $groupId = $objGroups->getId($moduleId);
                    // remove the group as well
                    $filters = array('group_id' => $groupId);
                    $rmGrp = $this->objLuAdmin->perm->removeGroup($filters);
                }
                else {
                    $rmArea = false;
                    $rmGrp = false;
                }

                if ($rmArea === false || $rmGrp == false) {
                    log_debug("Couldn't uninstall $moduleId");
                    $this->objLuAdmin->getErrors();
                }
                else {
                    log_debug("Uninstalling $moduleId");
                }
                return TRUE;
            }
        } catch (Exception $e) {
            //$this->objModules->rollbackTransaction();
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * This method looks at the registration data and tries to create any tables specified
    * @param   string $table    The name of the table to be created
    * @param   string $moduleId The id of the module
    * @returns boolean TRUE|FALSE
    */
    private function makeTable($table,$moduleId='NONE')
    {
        try {
            $this->objTableInfo = $this->newObject('tableinfo', 'modulecatalogue');
            if ($moduleId == 'NONE') {
                $moduleId = $this->module_id;
            }
            $this->objTableInfo->tablelist();
            if ($this->objTableInfo->checktable($table)) {
                return TRUE; // table already exists, don't try to create it over again!
            }
            if (!$sqlfile = $this->objModFile->findSqlFile($moduleId,$table)) {
                //for some reason the exception below results in a blank screen. return false instead.
                //throw new Exception($sqlfile.' '.$this->objLanguage->languageText('mod_modulecatalogue_sqlnotfound','modulecatalogue'));
                return FALSE;
            }
            include($sqlfile);
            if (!isset($tablename) || !isset($fields) || !isset($options)) {
                return FALSE;
            }

            $this->createTable($tablename, $fields, $options);
            if (isset($tableIndexes) && is_array($tableIndexes)) {
                foreach ($tableIndexes as $indexName => $indexDefinition) {
                    $this->createTableIndex($tablename, $indexName, $indexDefinition);
                }
            }
            if (isset($indexes)) {
                $name = array_keys($indexes['fields']);
                $name = $name[0];
                $this->createTableIndex($tablename, $name, $indexes);
            }
            return TRUE;
        } catch (Exception $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * This is a method to read data from a file and use it to populate (not create) a table.
    * @param  string  $moduleId the id of the module to be used
    * @return boolean TRUE or FALSE
    */
    public function loadData($moduleId) {
        try {
            $this->objLanguage = $this->getObject('language','language');
            if ($moduleId==null){
                $moduleId=$this->module_id;
            }
            $sqlfile=$this->objConfig->getModulePath()."$moduleId/sql/defaultdata.xml";
            if (!file_exists($sqlfile)){
                $sqlfile2=$this->objConfig->getSiteRootPath()."core_modules/$moduleId/sql/defaultdata.xml";

                // ensures that the default data is loaded once only by the installer
                // correct default data can only be loaded once
                if($moduleId == 'systext'){
                    $data = $this->objSystext->getSystemType('init_1');
                    if(!$data){
                        $sqlfile2=$this->objConfig->getSiteRootPath()."core_modules/$moduleId/sql/systextdata.xml";
                    }
                }

                if (!file_exists($sqlfile2)){
                    log_debug("No defaultdata found for module $moduleId");
                    //$this->_lastError = 1006;
                    return TRUE;
                }
                $sqlfile = $sqlfile2;
            }
            ini_set('max_execution_time','600');
            if (!$objXml = simplexml_load_file($sqlfile)) {
                throw new Exception($this->objLanguage->languageText('mod_modulecatalogue_badxml').' '.$sqlfile);
            }
            foreach ($objXml as $table=>$dummy) {
                $sqlArray = array();
                foreach ($dummy as $field=>$value) {
                    $sqlArray[$field]= $value;
                }

                // check that the record does not already exist
                if(!$this->objModules->getRow('id', $sqlArray['id'], $table)) {
                    if(!$this->objModules->insert($sqlArray,$table)) {
                        log_debug("Error inserting default data for $table");
                    }
                }

            }
            return TRUE;
        } catch (Exception $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * This is a method to move icons when registering
    * @param string $moduleId the module
    * @param array  $icons    the list of icons
    */
    private function moveIcons($moduleId,$icons) {
        try {
            if (file_exists($this->objConfig->getModulePath().$moduleId)) {
            $srcdir=$this->objConfig->getModulePath().$moduleId.'/icons/';
            } else {
                $srcdir = $this->objConfig->getSiteRootPath()."core_modules/$moduleId/icons/";
            }
            $destdir=$this->objConfig->getSkinRoot().$this->objConfig->defaultSkin().'/icons/';
            foreach ($icons as $icon)
            {
                copy($srcdir.$icon,$destdir.$icon);
            }
        } catch (Exception $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * This is a method to put the module information into the language table
    * It first inserts the name of the module and then inserts the
    * description of the module into the English column
    * @todo This must be moved into the language class
    */
    private function registerModuleLanguageElements() {
        try {
            $modTitle="mod_".$this->module_id."_name";
            $modDescription="mod_".$this->module_id."_desc";
            $this->objModules->delete('id',$modTitle,'tbl_en');
            $this->objModules->delete('id',$modDescription,'tbl_en');
            $this->objModules->delete('code',$modTitle,'tbl_languagetext');
            $this->objModules->delete('code',$modDescription,'tbl_languagetext');
            $userId = $this->objUser->userId();
            if($userId == '')
            {
                $userId = 0;
            }
            $time = $this->objModules->now();
            $titleArray = array('id'=>$modTitle,'en'=>addslashes($this->module_name),'pageid'=>addslashes($this->module_id),'isinnextgen'=>true,
                    'datecreated'=>$time,'creatoruserid'=>$userId,'datelastmodified'=>$time,'modifiedbyuserid'=>$userId);
            $descArray = array('id'=>$modDescription,'en'=>addslashes($this->module_description),'pageid'=>addslashes($this->module_id),'isinnextgen'=>true,
                    'datecreated'=>$time,'creatoruserid'=>$userId,'datelastmodified'=>$time,'modifiedbyuserid'=>$userId);
            $this->objModules->insert($titleArray,'tbl_en');
            $this->objModules->insert($descArray,'tbl_en');
            $this->objModules->insert(array('code'=>$modTitle,'description'=>$this->module_name),'tbl_languagetext');
            $this->objModules->insert(array('code'=>$modDescription,'description'=>$this->module_description),'tbl_languagetext');
        } catch (customException $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * This is a method to add language terms to the database
    * @param string $terms A comma delimited string of
    *                      terms that are used in the language database
    */
    private function registerModuleLanguageTerms($terms) {
        try {
            $terms_arr=explode(',', $terms);
            //$this->objModules->beginTransaction();
            foreach ($terms_arr as $term) {
                $this->objModules->insert(array('module_id$'=>$this->module_id,'code'=>$term),'tbl_language_modules');
            }
            //$this->objModules->commitTransaction();
        } catch (Exception $e) {
            //$this->objModules->rollbackTransaction();
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * Registers modules that this module depends on
    * @param string         $moduleId The module ID
    * @param $modulesNeeded array     The modules this module depends on
    */
    private function registerDependentModules($moduleId,$modulesNeeded) {
        try {
            //$this->objModules->beginTransaction();
            foreach ($modulesNeeded as $moduleNeeded) {
                $this->objModules->insert(array('module_id'=>$moduleId,'dependency'=>$moduleNeeded),'tbl_modules_dependencies');
            }
            //$this->objModules->commitTransaction();
        } catch (Exception $e) {
            //$this->objModules->rollbackTransaction();
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * This is a method to drop tables for the current module. This method
    * gets the list of owned tables from tbl_modules_owned_tables
    * and removes them one at a time
    * @param   string $moduleId
    * @returns array $droppedTables list of the dropped tables
    */
    private function dropTables($moduleId)
    {
        try {
            $sql = "SELECT tablename FROM tbl_modules_owned_tables WHERE kng_module='$moduleId'";
            $rs = $this->objModules->getArray( $sql );
            $rs_reversed=array_reverse($rs, TRUE);
            $droppedTables=array();
            foreach ($rs_reversed as $rec)
            {
                $table=$rec['tablename'];
                $droppedTables[]=$table;
                $this->dropTable($table);
            }
            return $droppedTables;
        } catch (Exception $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * This is a method to check for specified text entries from both tbl_languagetext and tbl_english
    * @param   $code
    * @returns array with elements flag = 0, 1, 10, or 11, content and desc
    */
    public function checkText($code) {
        try {
            $flag['flag']=0;
            $sql="SELECT * FROM tbl_en WHERE id='".$code."'";
            $arr=$this->objModules->getArray($sql);
            $flag1=0;
            $content='';
            foreach($arr as $el) {
                $flag1=1;
                $content=$el['en'];
            }
            $sql="SELECT * FROM tbl_languagetext WHERE code='".$code."'";
            $arr=$this->objModules->getArray($sql);
            $flag2=0;
            $description='';
            foreach($arr as $el) {
                $flag2=10;
                $description=$el['description'];
            }
            $flag['flag']=$flag1+$flag2;
            $flag['content']=$content;
            $flag['desc']=$description;
            return $flag;
        } catch (Exception $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * This method separates the array of text elements based on their type (TEXT|USES).
    * @param   array  $rdata
    * @param   string $index type of text to be added
    * @returns FALSE or array $texts
    */
    public function listTexts($rdata,$index='TEXT') {
        try {
            $texts=array();
            if (is_array($rdata) && array_key_exists($index,$rdata) && is_array($rdata[$index])) {
                foreach ($rdata[$index] as $line) {
                    //list($code,$description,$content) = explode('|',$line);
                    $cdc = explode('|',$line);
                    if ((is_countable($cdc) ? count($cdc) : 0) != 3) {
                        $error = str_replace('[MODULE]',$rdata['MODULE_ID'],$this->objLanguage->languageText('mod_modulecatalogue_regerror','modulecatalogue'));
                        $error = str_replace('[ELEMENT]',$cdc[0],$error);
                        throw new customException($error);
                    }
                    //echo "{$cdc[2]}<br/>";
                    if ($cdc[2]){
                        $texts[$cdc[0]]['content']=$cdc[2];
                        $texts[$cdc[0]]['desc']=$cdc[1];
                    } else {
                        $module=$rdata['MODULE_ID'];
                        $errorText = $this->objLanguage->languageText('mod_modulecatalogue_textproblem','modulecatalogue');
                        $errorText = str_replace("{MODULE}",$module,$errorText);
                        $errorText = str_replace("{CODE}",$cdc[0],$errorText);
                        $this->errorText .= $errorText;
                    }
                }
                return $texts;
            } else {
                return FALSE;
            }
        } catch (Exception $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * This is a method to add specified text entries from both tbl_languagetext and tbl_english
    * @author James Scoble
    * @param  $code,$description,$content
    */
    private function addText($code,$description,$content,$modname = 'system') {
        try {
            if ($modname == null) {
                $modname = $this->module_id;
            }
            if ($modname == null) {
                throw new customException("Null value for module name in addText for item $code|$description|$content");
            }
            //$this->objModules->beginTransaction();
            $this->removeText($code);
            if (!$this->objModules->valueExists('id',$code,'tbl_en')) {
                $code=addslashes($code);
                $description=addslashes($description);
                $content=addslashes($content);
                $this->objModules->insert(array('code'=>$code,'description'=>$description),'tbl_languagetext');
                $uid = $this->objUser->userId();
                $now = $this->objModules->now();
                $enArray = array('id'=>$code,'en'=>$content,'pageId'=>$modname,'isInNextgen'=>true,
                'dateCreated'=>$now,'creatorUserId'=>$uid,'dateLastModified'=>$now,'modifiedByUserId'=>$uid);
                $this->objModules->insert($enArray,'tbl_en');
            }
            //$this->objModules->commitTransaction();
        } catch (Exception $e) {
            //$this->rollbackTransaction();
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * This is a method to remove specified text entries from both tbl_languagetext and tbl_english
    * @param $code
    */
    private function removeText($code) {
        try {
            $code=addslashes($code);
            //$this->objModules->beginTransaction();
            $this->objModules->delete('id',$code,'tbl_en');
            $this->objModules->delete('code',$code,'tbl_languagetext');
            //$this->objModules->commitTransaction();
        } catch (Exception $e) {
            //$this->rollbackTransaction();
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

     /**
    * This is a method to look through list of texts specified for module,
    * and see if they are registered or not.
    * @author James Scoble
    * @param  string $modname
    * @param  string $action  - optional, if its 'fix' then the function tries
    *                         to add any texts that are missing.
    *                         returns array $mtexts
    */
    public function moduleText($modname,$action='readonly') {
        try {
            $mtexts = array();
            $filepath = $this->objModFile->findRegisterFile($modname);
            $rdata = $this->objModFile->readRegisterFile($filepath,FALSE);
            $text = $this->listTexts($rdata,'TEXT');
            $uses = $this->listTexts($rdata,'USES');
            if ($uses) {
                //$text = array_merge($texts,$uses);
                foreach ($uses as $code=>$data) {
                    $isreg=$this->checkText($code); // this gets an array with 3 elements - flag, content, and desc
                    $text_desc=$data['desc'];
                    $text_val=$data['content'];
                    if (($action=='fix')&&($isreg['flag']==0)) {
                        $this->addText($code,$text_desc,$text_val,'system');
                    }
                    if ($action=='replace') {
                        //if ($this->objModules->valueExists('id',$code,'tbl_en')) {
                        //    $this->removeText($code);
                        //}
                        $this->addText($code,$text_desc,$text_val,'system');
                    }
                    $mtexts[]=array('code'=>$code,'desc'=>$text_desc,'content'=>$text_val,'isreg'=>$isreg,'type'=>'TEXT');
                }
            }
            //$this->objModule->beginTransaction(); //Start a transaction;
            if (is_array($text)) {
                foreach ($text as $code=>$data) {
                    $isreg=$this->checkText($code); // this gets an array with 3 elements - flag, content, and desc
                    $text_desc=$data['desc'];
                    $text_val=$data['content'];
                    if (($action=='fix')&&($isreg['flag']==0)) {
                        $this->addText($code,$text_desc,$text_val,$modname);
                    }
                    if ($action=='replace') {
                        //if ($this->objModules->valueExists('id',$code,'tbl_en')) {
                        //    $this->removeText($code);
                        //}
                        $this->addText($code,$text_desc,$text_val,$modname);
                    }
                    $mtexts[]=array('code'=>$code,'desc'=>$text_desc,'content'=>$text_val,'isreg'=>$isreg,'type'=>'TEXT');
                }
            }
            //$this->objModule->commitTransaction(); //End the transaction;
            return $mtexts;
        } catch (Exception $e) {
            //$this->objModule->rollbackTransaction();
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * This is a method to update the text elements in all registered modules at once
    */
    public function updateAllText() {
        try {
            $modulesArray = $this->objModules->getAll();
            foreach ($modulesArray as $module) {
                $this->moduleText($module['module_id'],'replace');
            }
            return TRUE;
        } catch (Exception $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

     /**
     * Method to get the last error code
     *
     * @return int code of last error
     */
    public function getLastErrorCode() {
        try {
            return isset($this->_lastError)? $this->_lastError : FALSE;
        } catch (Exception $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
     * Method to get the last error
     *
     * @return string description of last error
     */
    public function getLastError() {
        try {
            switch ($this->_lastError) {
                case 1001:
                case 1002:
                case 1003:
                case 1004:
                case 1005:
                case 1006:
                    return $this->objLanguage->languageText("mod_modulecatalogue_error$this->_lastError",'modulecatalogue');
                default:
                    return $this->objLanguage->languageText('mod_modulecatalogue_defaulterror','modulecatalogue').": '$this->_lastError'";
            }
        } catch (Exception $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
     * Method to check whether a menu item exists in the database already
     *
     * @param  string $category the menu category to check in
     * @param  string $moduleId the module to look for
     * @return id     of the record|FALSE
     */
    function existsInMenu($category,$moduleId) {
        $sql = "SELECT id FROM tbl_menu_category WHERE category LIKE '$category' and module = '$moduleId'";
        $rs = $this->objModules->getArray($sql);
        if (!$rs) {
            $ret = false;
        } else {
            if (count($rs[0]) > 0) {
                $ret = $rs[0]['id'];
            } else {
                $ret = false;
            }
        }

        return $ret;
    }

    /**
     * Method to check whether a menu item exists in the toolbar menu
     *
     * @param  string $moduleId module to check for
     * @return id     of the record in the db|FALSE
     */
    function existsInToolbarMenu($moduleId,$category) {
        $sql = "SELECT id FROM tbl_menu_category WHERE module = '$moduleId' AND category = '$category' ";
        $rs = $this->objModules->getArray($sql);
        if (!$rs) {
            $ret = false;
        } else {
            if (count($rs[0]) > 0) {
                $ret = $rs[0]['id'];
            } else {
                $ret = false;
            }
        }

        return $ret;
    }

    /**
     * The error callback function, defers to configured error handler
     *
     * @param  string $exception
     * @return void
     */
    public function errorCallback($exception) {
        echo customException::cleanUp($exception);
    }


    /**
     * Resolve one module-declared site group through its canonical owner.
     *
     * register.conf remains the declarative policy source. GroupService alone
     * validates and persists the group, including identifier normalization.
     *
     * @param string $groupName Group declared by module registration metadata.
     * @param string $description Optional declaration description.
     * @return int Canonical group identifier.
     * @throws customException When the declaration cannot be provisioned.
     */
    private function ensureRegistrationGroup($groupName, $description)
    {
        $objGroupService = $this->getObject('groupservice', 'groupadmin');
        $result = $objGroupService->ensureGroups(
            array(
                array(
                    'name' => $groupName,
                    'description' => $description,
                ),
            )
        );

        if (!is_array($result)
            || empty($result['ok'])
            || !isset($result['groups'][$groupName])) {
            $code = is_array($result) && isset($result['code'])
                ? (string) $result['code']
                : 'group_registration_failed';
            throw new customException(
                'Module group provisioning failed: '.$code
            );
        }

        $groupId = $result['groups'][$groupName];
        if (!is_numeric($groupId) || (int) $groupId < 1) {
            throw new customException(
                'Module group provisioning failed: invalid_group_id'
            );
        }

        return (int) $groupId;
    }

}
?>
