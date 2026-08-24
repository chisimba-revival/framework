<?php
/* -------------------- sysconfig class extends controller ----------------*/
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
/**
*
* Sysconfig module controller for KEWL.NextGen. The logger
* module is responsible for recording and displaying user
* activity.
*
*
*
* @author Derek Keats
*
*/
class sysconfig extends controller {

    /**
    * var object Property to hold sysconfig object
    */
    public $objSysConfig;

    /**
    * var object Property to hold text abstract object
    * @author Kevin Cyster
    */
    public $objAbstract;

    /**
    * var object Property to hold language object
    */
    public $objLanguage;

    /**
    * var object Property to hold user object
    */
    public $objUser;
    /**
     * object Porperty to hold config values
     *
     * @var object
     */
    public $config;
    /**
    * array variable to append search box configurations
    */
    public $searchBoxPr;
    /**
    * object for altCofig class for serach box
    */
     public $objAltconfig;
    /**
    * Standard init function
    */
    function init()
    {
        //Get an instance of the configuration object
        $this->objSysConfig =  $this->getObject('dbsysconfig');
        //Get an instance of the interface helper object
        $this->objInterface =  $this->getObject('sysconfiginterface');
        //Get an Instance of the language object
        $this->objLanguage = $this->getObject('language', 'language');
        //Get an instance of the user object
        $this->objUser =  $this->getObject('user', 'security');
        //Get the text abstract object
        //Kevin Cyster
        $this->objAbstract = $this->getObject('systext_facet', 'systext');
        
        
    }

    /**
    * Dispatch method for sysconfig class. It selects the steps in
    */
    function dispatch()
    {
        //Require the user to be admin
        if (!$this->objUser->isAdmin()) {
         $this->setVar('str', $this->objLanguage->languageText("mod_sysconfig_reqadmin",'sysconfig'));
            return 'main_tpl.php';
        }
        $action = $this->getParam('action', NULL);
        $title=$this->objLanguage->languageText("help_sysconfig_about_title",'sysconfig');
        switch ($action) {
            case NULL:
            case 'step1':
                 // Create page title
                $pgTitle = $this->getObject('htmlheading', 'htmlelements');
                $pgTitle->type = 1;
                $pgTitle->str = $this->objLanguage->languageText("mod_sysconfig_firstep",'sysconfig');
                //Set the title for the table
                $this->setVar('title', $pgTitle->show());
                //Set the text instructions for the table
                $this->setVar('step1', $this->objLanguage->languageText("mod_sysconfig_step1",'sysconfig'));
                //Get list of registered modules
                $this->objMods =  $this->getObject('modules', 'modulecatalogue');
                //Return an array of all modules
                $this->setVar('ary', $this->objMods->getModules(1));
                //Set the action for the form
                $formaction = $this->uri(array('action' => 'step2'));
                $this->setVar('formaction', $formaction);

                // Get the list of modules with parameters
                $modulesList = $this->objSysConfig->getModulesParamList();
                $this->setVarByRef('modules', $modulesList);

                return "step1_tpl.php";
                break;
                case 'step2':
      //Append search box property if it doesn't exit in the configuration file
      //By Emmanuel Natalis-udsm     
      $this->searchBoxPr=array('SHOW_SEARCH_BOX'=>'TRUE');
      $this->objAltconfig=$this->getObject('altconfig','config');
      if($this->objAltconfig->isPropertyExist('SHOW_SEARCH_BOX')=='FALSE')
       {
          //property doesn't exist, we append it
          $this->objAltconfig->appendToConfig($this->searchBoxPr);
        }
                $pmodule = $this->getParam('pmodule_id', NULL);

                if ($this->objSysConfig->getValue("add_disabled", "sysconfig")=="TRUE") {
                    $this->setVar('disableadd',TRUE);
                } else {
                    $this->setVar('disableadd',FALSE);
                }
                if ($pmodule !== NULL) {
                    //Set the pmodule code in the template
                    $this->setVar('pmodule', $pmodule);

                    // SYSCONFIG_RELOAD_MODULE_PARAMS feedback
                    $reloadStatus = $this->getParam('reload_status', NULL);
                    if ($reloadStatus === 'success') {
                        $reloadMessage = $this->objLanguage->languageText(
                                'mod_sysconfig_reloadparams_success', 'sysconfig');
                        $reloadMessage = str_replace(
                                array('[ADDED]', '[PRESERVED]', '[LANGUAGE]'),
                                array(
                                    (int) $this->getParam('reload_added', 0),
                                    (int) $this->getParam('reload_preserved', 0),
                                    (int) $this->getParam('reload_language', 0)
                                ),
                                $reloadMessage);
                        $this->setVar('reloadMessage', $reloadMessage);
                        $this->setVar('reloadMessageClass', 'success');
                    } elseif ($reloadStatus === 'invalid') {
                        $this->setVar(
                                'reloadMessage',
                                $this->objLanguage->languageText(
                                    'mod_sysconfig_reloadparams_invalid',
                                    'sysconfig'));
                        $this->setVar('reloadMessageClass', 'error');
                    } elseif ($reloadStatus === 'missing') {
                        $this->setVar(
                                'reloadMessage',
                                $this->objLanguage->languageText(
                                    'mod_sysconfig_reloadparams_missing',
                                    'sysconfig'));
                        $this->setVar('reloadMessageClass', 'error');
                    }

                    //Get an array of data for the module whose params are being set
                    $ary = $this->objSysConfig->getProperties($pmodule);
                    //Set the text instructions for the table
                    $this->setVar('step2', $this->objLanguage->languageText("mod_sysconfig_step2",'sysconfig'));
                    if ((is_countable($ary) ? count($ary) : 0) >=1) {
                        //Send through the array
                        $this->setVarByRef('ary', $ary);
                    } else {
                        $this->setVar('str', "<h3>" .
                        $this->objLanguage->languageText("mod_sysconfig_noconfprop",'sysconfig')
                        ."</h3>");
                    }
                    // update the session variable 'systext' if $pmodule = systext
                    // Kevin Cyster
                    if($pmodule == 'systext'){
                        $this->objAbstract->updateSession();
                    }
                    return "step2_tpl.php";
                } else {
                    $this->setVar('str', $this->objLanguage->languageText("mod_sysconfig_nomoduleset",'sysconfig'));
                    return "dump_tpl.php";
                }
                break;
            case 'reloadmoduleparams':
                /*
                 * SYSCONFIG_RELOAD_MODULE_PARAMS
                 * Parse the selected module's current register.conf and add
                 * missing CONFIG entries without modifying existing values.
                 */
                $pmodule = trim($this->getParam('pmodule_id', ''));
                if (!preg_match('/^[A-Za-z0-9_-]+$/', $pmodule)
                        || $pmodule === '_site_') {
                    return $this->nextAction(
                            'step2',
                            array(
                                'pmodule_id' => $pmodule,
                                'reload_status' => 'invalid'
                            ));
                }

                $objModuleFile = $this->getObject(
                        'modulefile', 'modulecatalogue');
                $registerFile = $objModuleFile->findRegisterFile($pmodule);
                if ($registerFile === FALSE) {
                    return $this->nextAction(
                            'step2',
                            array(
                                'pmodule_id' => $pmodule,
                                'reload_status' => 'missing'
                            ));
                }

                $registerData = $objModuleFile->readRegisterFile($registerFile);
                if (!is_array($registerData)
                        || !isset($registerData['MODULE_ID'])
                        || $registerData['MODULE_ID'] !== $pmodule) {
                    return $this->nextAction(
                            'step2',
                            array(
                                'pmodule_id' => $pmodule,
                                'reload_status' => 'invalid'
                            ));
                }

                $configEntries = isset($registerData['CONFIG'])
                        ? $registerData['CONFIG'] : array();
                $languageEntries = isset($registerData['TEXT'])
                        && is_array($registerData['TEXT'])
                        ? $registerData['TEXT'] : array();
                $reloadResult = $this->objSysConfig
                        ->registerMissingModuleParams(
                            $pmodule, $configEntries);

                /*
                 * SYSCONFIG_RELOAD_MODULE_LANGUAGE
                 * Refresh language elements only for Sysconfig and the
                 * selected module. This makes new parameter descriptions and
                 * this maintenance action's own labels immediately available.
                 */
                $objModuleAdmin = $this->getObject(
                        'modulesadmin', 'modulecatalogue');
                $objModuleAdmin->moduleText('sysconfig', 'replace');
                if ($pmodule !== 'sysconfig') {
                    $objModuleAdmin->moduleText($pmodule, 'replace');
                }

                return $this->nextAction(
                        'step2',
                        array(
                            'pmodule_id' => $pmodule,
                            'reload_status' => 'success',
                            'reload_added' => $reloadResult['added'],
                            'reload_preserved' => $reloadResult['preserved'],
                        'reload_language' => count($languageEntries)
                        ));
                break;

            case 'save':
                //Get the module for the parameter
                $pmodule = TRIM($_POST['pmodule']);

                if ($pmodule=='_site_') {
                    $this->save();
                }
                $this->objSysConfig->updateSingle();

                $checkobject = $this->getObject('checkobject', 'utilities');

                $record = $this->objSysConfig->getRow('id', $_POST['id']);

                // THIS IS NOT WORKING - Commented out for the time being
                // if ($checkobject->objectFileExists('sysconfig_'.strtolower($record['pname']), $pmodule)) {
                    // $customSysConfig = $this->getObject(strtolower('sysconfig_'.$record['pname']), $pmodule);
                    // $customSysConfig->postUpdateActions();
                // }
                return $this->nextAction('step2', array('pmodule_id'=> $pmodule));
                break;
            case 'edit':
                //Get the module for the parameter
                $pmodule = $this->getParam("pmodule", NULL);
                //Set the text instructions for the table
                $this->setVar('step', $this->objLanguage->languageText("mod_sysconfig_edlabel",'sysconfig'));
                //Set the mode variable to edit
                $this->setVar('mode', 'edit');
                //Get the form

                $this->setVar('str', $this->objInterface->showEditAddForm($pmodule,"edit"));
                //Return the edit template
                return "edit_add_tpl.php";
                break;
            default:
                die($this->objLanguage->languageText("phrase_actionunknown").": ".$action);
                break;
        } #switch
      
    }

    private function save()
    {
        $this->objConfig =  $this->getObject('altconfig','config');
        $result = $this->objConfig->updateParam($this->getParam('id'),'',$this->getParam('pvalue'));
        return $result;

    }


} # end of class

?>
