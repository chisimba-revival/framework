<?php
/**
 * This file houses modulecatalogue controller class.
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
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 */

/**
 * The modulecatalogue class extends the controller class and as such is the controller
 * for the modulecatalogue module. The main fucntions of this module are module administration
 * with a catalogue interface. Allows installation and Un-installation of modules
 * via a cagtalogue interface which groups similar modules. Also incorporates module patching.
 *
 * @category  Chisimba
 * @package   modulecatalogue
 * @author    Nic Appleby <nappleby@uwc.ac.za>
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 */

class modulecatalogue extends controller {
    /**
     * Object to connect to Module Catalogue table
     *
     * @var object $objDBModCat
     */
    protected $objDBModCat;

    /**
     * Object to read module information from register files
     *
     * @var object $objModFile
     */
    protected $objModFile;

    /**
     * Object to read catalogue configuration
     *
     * @var object $objCatalogueConfig
     */
    protected $objCatalogueConfig;

    /**
     * Side menu object
     *
     * @var object $objSideMenu
     */
    public $objSideMenu;

    /**
     * Logger object to log module calls
     *
     * @var object $objLog
     */
    public $objLog;

    /**
     * User object for security
     *
     * @var object $objUser
     */
    public $objUser;

    /**
     * Language object for multilingual support
     *
     * @var object $objLanguage
     */
    public $objLanguage;

    /**
     * ISO language codes object for multilingual support
     *
     * @var object $objLanguageCode
     */
    public $objLanguageCode;

    /**
     * The site configuration object
     *
     * @var object $config
     */
    public $config;

    /**
     * object to read/write module data to database
     *
     * @var object $objModule
     */
    protected $objModule;

    /**
     * object to read/write administrative module data to database
     *
     * @var object $objModuleAdmin
     */
    protected $objModuleAdmin;

    /**
     * object to check system configuration
     *
     * @var object $objSysConfig
     */
    protected $objSysConfig;

    /**
     * output varaiable to store user feedback
     *
     * @var string $output
     */
    protected $output;

    /**
     * object to manage module patches
     *
     * @var object $objPatch
     */
    protected $objPatch;

    public $tagCloud;

    protected $objTagCloud;

    protected $extzip = FALSE;

    private $ajaxInstall = FALSE;

    /**
     * Standard initialisation function
     */
    public function init() {
        try {
            set_time_limit ( 0 );
            $this->objUser = $this->getObject ( 'user', 'security' );
            $this->objConfig = $this->getObject ( 'altconfig', 'config' );
            $this->objLanguage = $this->getObject ( 'language', 'language' );
            $this->objLanguageCode = $this->getObject ( 'languagecode', 'language' );
            $this->objModuleAdmin = $this->getObject ( 'modulesadmin', 'modulecatalogue' );
            $this->objModule = $this->getObject ( 'modules' );
            //the class for reading register.conf files
            $this->objModFile = $this->getObject ( 'modulefile' );
            $this->objPatch = $this->getObject ( 'patch', 'modulecatalogue' );
            $this->objCatalogueConfig = $this->getObject ( 'catalogueconfig', 'modulecatalogue' );
            if (! file_exists ( $this->objConfig->getSiteRootPath () . 'config/catalogue.xml' )) {
                $this->objCatalogueConfig->writeCatalogue ();
            }
            $this->objSideMenu = $this->getObject ( 'catalogue', 'modulecatalogue' );
            // Check which zip thing we will be using
            if(extension_loaded('zip') && function_exists('zip_open')) {
                $this->extzip = TRUE;
            }
            $this->objSideMenu->addNodes ( array ('local updates', 'all', 'languages' ) );
            $sysTypes = $this->objCatalogueConfig->getCategories ();
            //$xmlCat = $this->objCatalogueConfig->getNavParam('category');
            //get list of categories
            //$catArray = $xmlCat['catalogue']['category'];
            //natcasesort($catArray);
            //$this->objSideMenu->addNodes($catArray);
            $this->objSideMenu->addNodes ( $sysTypes );
            $this->objTagCloud = $this->getObject ( 'tagcloud', 'utilities' );
            $this->tagCloud = $this->objCatalogueConfig->getModuleTags ();
            $tagscl = $this->processTags ();
            //var_dump($tagscl); die();
            if ($tagscl != NULL) {
                $this->objTagCloud = $this->objTagCloud->buildCloud ( $tagscl );
            } else {
                $this->objTagCloud = NULL;
            }
            //$this->tagCloud = $this->objTagCloud->exampletags();
            $this->objPOFile = $this->getObject ( 'pofile', 'modulecatalogue' );
            $this->objLog = $this->getObject ( 'logactivity', 'logger' );
            $this->objLog->log ();
        } catch ( customException $e ) {
            $this->errorCallback ( 'Caught exception: ' . $e->getMessage () );
            exit ();
        }
    }

    /**
     * The dispatch function which handles the execution path od the module
     *
     * @return mixed template names to be displayed by the engine
     */
    public function dispatch() {
        try {
            $this->output = '';
            $action = $this->getParm ( 'action' );

            /*
             * Bounded canonical-services proof. This branch deliberately
             * precedes modulecatalogue's legacy administrator/page-shell
             * authorisation so it tests only the canonical authenticated
             * session and identity contracts.
             */
            if ($action === 'canonicalidentityproof') {
                $this->setLayoutTemplate(null);
                $this->setVar('pageSuppressToolbar', true);
                $proof = $this->getObject('canonicalidentityproof');
                $this->setVar('canonicalIdentityProof', $proof->resolve());
                return 'canonical_identity_proof_tpl.php';
            }

            if (($action != 'firsttimeregistration') && (! $this->objUser->isAdmin ())) { //no access to non-admin users
                return 'noaccess_tpl.php';
            }
            if (! isset ( $activeCat )) {
                $activeCat = $this->getParm ( 'cat', 'Local Updates' );
            }
            $this->setVar ( 'activeCat', $activeCat );

            /*
             * Remember the catalogue view independently of category and
             * install/uninstall actions. Supplying installedonly=0 is the
             * deliberate way to return to the complete local catalogue.
             */
            $installedOnlyParam = $this->getParam('installedonly', null);
            if ($installedOnlyParam !== null) {
                $installedOnly = ((string) $installedOnlyParam === '1');
                $this->setSession('modulecatalogue_installed_only', $installedOnly);
            } else {
                $installedOnly = (bool) $this->getSession('modulecatalogue_installed_only', false);
            }
            $this->setVar('installedOnly', $installedOnly);

            /*
             * Remember the catalogue view independently of category and
             * install/uninstall actions. Supplying installedonly=0 is the
             * deliberate way to return to the complete local catalogue.
             */
            $installedOnlyParam = $this->getParam('installedonly', null);
            if ($installedOnlyParam !== null) {
                $installedOnly = ((string) $installedOnlyParam === '1');
                $this->setSession('modulecatalogue_installed_only', $installedOnly);
            } else {
                $installedOnly = (bool) $this->getSession('modulecatalogue_installed_only', false);
            }
            $this->setVar('installedOnly', $installedOnly);
            //$this->setVar('letter',$this->getParam('letter','none'));
            $this->setLayoutTemplate ( 'cat_layout.php' );
            $this->setVar ( 'connected', false );
            switch ($action) { //check action
                case 'updatedeps' :
                    $this->updateDeps ( $this->getParam ( 'modname' ) );
                    return $this->nextAction ( 'list', array ('cat' => 'Updates', 'message' => $this->objLanguage->languageText ( 'mod_modulecatalogue_installeddeps', 'modulecatalogue' ) ) );
                case null :
                case 'list' :
                    /*
                     * CHISIMBA_MODULECATALOGUE_CONTROLLER_LOCAL_ALL
                     *
                     * The historical "All" category depended on an optional
                     * remote catalogue service. During restoration that
                     * service may be absent or unconfigured. In that case the
                     * module administration screen must still list everything
                     * installed locally and must not invoke RPC.
                     *
                     * Technical debt: restore a configurable repository
                     * service and make remote catalogue access explicitly
                     * optional.
                     */
                    if (strtolower ( trim ( $activeCat ) ) == 'all') {
                        $localModuleList = $this->objModFile->getLocalModuleList ();
                        $localModules = array ();

                        foreach ($localModuleList as $moduleKey => $moduleValue) {
                            /*
                             * Older modulefile implementations may return
                             * either a numeric list of IDs or an associative
                             * ID => name list. Normalise both forms.
                             */
                            if (is_int($moduleKey) || ctype_digit((string) $moduleKey)) {
                                $moduleId = (string) $moduleValue;
                                $moduleName = ucfirst($moduleId);
                            } else {
                                $moduleId = (string) $moduleKey;
                                $moduleName = is_scalar($moduleValue)
                                    ? (string) $moduleValue
                                    : ucfirst($moduleId);
                            }

                            if ($moduleId !== '') {
                                $localModules[$moduleId] = $moduleName;
                            }
                        }

                        ksort($localModules, SORT_NATURAL | SORT_FLAG_CASE);

                        /*
                         * front_tpl.php consumes this session value as search
                         * results and therefore skips getCategoryList('all'),
                         * which is the path that invokes the dead repository.
                         */
                        $this->setSession('modcatsearchresults', $localModules);
                        $this->setVar('result', $localModules);
                        $this->setVar('connected', false);

                        log_debug(
                            'Module catalogue All category: displaying '
                            . (is_countable($localModules) ? count($localModules) : 0)
                            . ' local modules; remote repository skipped.'
                        );

                        return 'front_tpl.php';
                    }

                    if (strtolower ( $activeCat ) == 'local updates') {
                        $this->setVar ( 'patchArray', $this->objPatch->checkModules () );
                        return 'updates_tpl.php';
                    } else if (strtolower ( $activeCat == 'languages' )) {
                        return 'languages_tpl.php';
                    } else {
                        return 'front_tpl.php';
                    }
                case 'uninstall' :

                    $srchStr = $this->getParam ( 'srchstr' );
                    $lastAction = $this->getParam ( 'lastaction' );
                    $srchType = $this->getParam ( 'srchtype' );

                    $error = false;
                    $mod = $this->getParm ( 'mod' );
                    if ($this->uninstallModule ( $mod )) {
                        $this->output = str_replace ( '[MODULE]', $mod, $this->objLanguage->languageText ( 'mod_modulecatalogue_uninstallsuccess', 'modulecatalogue' ) );
                    } else {
                        if ($this->output == '') {
                            $this->output = $this->objModuleAdmin->output;
                        }
                        $error = $this->objModuleAdmin->getLastErrorCode ();
                        if (! $error)
                            $error = - 1;
                    }
                    $this->setSession ( 'output', $this->output );

                    if ($lastAction != NULL) {
                        return $this->nextAction ( 'search', array ('cat' => $activeCat, 'lastError' => $error, 'srchtype' => $srchType, 'srchstr' => $srchStr ) );
                    }
                    return $this->nextAction ( null, array ('cat' => $activeCat, 'lastError' => $error ) );

                case 'install' :
                    $error = false;
                    $mod = $this->getParm ( 'mod' );
                    $srchStr = $this->getParam ( 'srchstr' );
                    $lastAction = $this->getParam ( 'lastaction' );
                    $srchType = $this->getParam ( 'srchtype' );
                    $ins = $this->getPatchObject ( $mod );
                    if ($ins !== null && method_exists ( $ins, 'preinstall' )) {
                        $ins->preinstall ();
                    }

                    $regResult = $this->installModule ( trim ( $mod ) );
                    if ($regResult) {
                        $this->output = str_replace ( '[MODULE]', $mod, $this->objLanguage->languageText ( 'mod_modulecatalogue_installsuccess', 'modulecatalogue' ) ); //success
                    } else {
                        $error = $this->objModuleAdmin->getLastErrorCode ();
                        if (! $error)
                            $error = - 1;
                        if ($this->output == '') {
                            $this->output = isset ( $this->objModuleAdmin->output ) ? $this->objModuleAdmin->output : $this->objModuleAdmin->getLastError ();
                        }
                    }
                    // run the postinstall script(s)
                    if ($ins !== null && method_exists ( $ins, 'postinstall' )) {
                        $ins->postinstall ();
                    }

                    $this->setSession ( 'output', $this->output );
                    if ($lastAction != NULL) {
                        return $this->nextAction ( 'search', array ('cat' => $activeCat, 'lastError' => $error, 'srchtype' => $srchType, 'srchstr' => $srchStr ) );
                    }
                    return $this->nextAction ( null, array ('cat' => $activeCat, 'lastError' => $error ) );
                case 'installwithdeps' :
                    $error = false;
                    $mod = trim ( $this->getParam ( 'mod' ) );
                    $regResult = $this->smartRegister ( $mod );
                    if ($regResult) {
                        $this->output = str_replace ( '[MODULE]', $mod, $this->objLanguage->languageText ( 'mod_modulecatalogue_installsuccess', 'modulecatalogue' ) ); //success
                    } else {
                        if ($this->output == '') {
                            $this->output = $this->objModuleAdmin->output;
                        }
                        $error = $this->objModuleAdmin->getLastErrorCode ();
                        if (! $error)
                            $error = - 1;
                    }
                    $this->setSession ( 'output', $this->output );
                    return $this->nextAction ( null, array ('cat' => $activeCat, 'lastError' => $error ) );
                case 'info' :
                    $filepath = $this->objModFile->findRegisterFile ( $this->getParm ( 'mod' ) );
                    if ($filepath) { // if there were no file it would be FALSE
                        $this->registerdata = $this->objModFile->readRegisterFile ( $filepath );
                        if ($this->registerdata) {
                            return 'info_tpl.php';
                        }
                    } else {
                        $this->setVar ( 'output', $this->objLanguage->languageText ( 'mod_modulecatalogue_noinfo', 'modulecatalogue' ) );
                        return 'front_tpl.php';
                    }
                case 'textelements' :
                    $texts = $this->objModuleAdmin->moduleText ( $this->getParm ( 'mod' ) );
                    $this->setVar ( 'moduledata', $texts );
                    $this->setVar ( 'modname', $this->getParm ( 'mod' ) );
                    return 'textelements_tpl.php';
                case 'addtext' :
                    $modname = $this->getParm ( 'mod' );
                    $texts = $this->objModuleAdmin->moduleText ( $modname, 'fix' );
                    return $this->nextAction ( 'textelements', array ('mod' => $modname, 'cat' => $this->getParam ( 'cat' ), 'message' => 'textadded' ) );

                /*
                        // Redirect back to textelements action

                    $texts = $this->objModuleAdmin->moduleText($modname);
                    $this->output=$this->objModule->output;
                    $this->setVar('output',$this->output);
                    $this->setVar('moduledata',$texts);
                    $this->setVar('modname',$modname);
                    return 'textelements_tpl.php';
                    */
                case 'replacetext' :
                    $modname = $this->getParm ( 'mod' );
                    $texts = $this->objModuleAdmin->moduleText ( $modname, 'replace' );

                    return $this->nextAction ( 'textelements', array ('mod' => $modname, 'cat' => $this->getParam ( 'cat' ), 'message' => 'textreplaced' ) );

                /*
                        // Redirect back to textelements action

                    $texts=$this->objModuleAdmin->moduleText($modname);
                    $this->output=$this->objModule->output;
                    $this->setVar('output',$this->output);
                    $this->setVar('moduledata',$texts);
                    $this->setVar('modname',$modname);
                    return 'textelements_tpl.php';
                    */

                case 'batchinstall' :
                    $error = false;
                    $selectedModules = $this->getArrayParam ( 'arrayList' );
                    if ((is_countable($selectedModules) ? count($selectedModules) : 0) > 0) {
                        if (! $this->batchRegister ( $selectedModules )) {
                            $error = - 1;
                            if (! $this->output)
                                $this->output = $this->objModuleAdmin->output;
                        }
                    } else {
                        $error = - 2;
                        $this->output = '<b>' . $this->objLanguage->languageText ( 'mod_modulecatalogue_noselect', 'modulecatalogue' ) . '</b>';
                    }
                    $this->setSession ( 'output', $this->output );
                    return $this->nextAction ( 'list', array ('cat' => $activeCat, 'lastError' => $error ) );
                case 'batchuninstall' :
                    $error = false;
                    $selectedModules = $this->getArrayParam ( 'arrayList' );
                    if ((is_countable($selectedModules) ? count($selectedModules) : 0) > 0) {
                        if (! $this->batchDeregister ( $selectedModules )) {
                            $error = - 1;
                            if (! $this->output)
                                $this->output = $this->objModuleAdmin->output;
                        }
                    } else {
                        $error = - 2;
                        $this->output = '<b>' . $this->objLanguage->languageText ( 'mod_modulecatalogue_noselect', 'modulecatalogue' ) . '</b>';
                    }
                    $this->setSession ( 'output', $this->output );
                    return $this->nextAction ( 'list', array ('cat' => $activeCat, 'lastError' => $error ) );

                case 'updateall' :
                    ini_set ( 'max_execution_time', '6000' );
                    set_time_limit ( 0 );
                    $this->objModuleAdmin->updateAllText ();
                    return $this->nextAction ( 'list' );

                case 'firsttimeregistration' :
                    $this->ajaxInstall = $this->getParam ( 'ajax', 'false' ) == 'true';
                    $this->objSysConfig = $this->getObject ( 'dbsysconfig', 'sysconfig' );
                    $sysType = $this->getParam ( 'sysType', 'Basic System Only' );
                    $check = $this->objSysConfig->getValue ( 'firstreg_run', 'modulecatalogue' );
                    if (! $check) {
                        log_debug ( 'Modulecatalogue controller - performing first time registration' );
                        $this->firstRegister ( $sysType );
                        log_debug ( 'First time registration complete' );
                        if ($this->ajaxInstall) {
                            header('Content-Type: application/json; charset=UTF-8');
                            echo json_encode(array(
                                'ok' => true,
                                'code' => 'first_registration_complete'
                            ));
                            exit();
                        }
                    } else {
                        log_debug ( 'First time registration has already been performed on this system. Aborting' );
                    }

                    if (!$this->ajaxInstall)
                    {
                        $url = array ('username' => 'admin', 'password' => 'a', 'mod' => 'modulecatalogue' );
                        return $this->nextAction ( 'login', $url, 'security' );
                    }
                    else
                    {
                        die();
                    }

                case 'update' :
                    $patchver = $this->getParam ( 'patchver' );
                    $modname = $this->getParam ( 'mod' );
                    $ins = $this->getPatchObject ( $modname );
                    if ($ins !== null && method_exists ( $ins, 'preinstall' )) {
                        $ins->preinstall ($patchver);
                    }
                    if (($this->output = $this->objPatch->applyUpdates ( $modname )) === FALSE) {
                        $this->setVar ( 'error', str_replace ( '[MODULE]', $modname, $this->objLanguage->languageText ( 'mod_modulecatalogue_failed', 'modulecatalogue' ) ) );
                    } else {
                        $this->setVar ( 'output', $this->output );
                    }
                    // postinstall
                    $ins = $this->getPatchObject ( $modname );
                    if ($ins !== null && method_exists ( $ins, 'postinstall' )) {
                        $ins->postinstall ($patchver);
                    }

                    $this->setVar ( 'patchArray', $this->objPatch->checkModules () );
                    return 'updates_tpl.php';

                case 'patchall' :
                    $mods = $this->objPatch->checkModules ();
                    $this->output = array ();
                    $error = '';
                    $success = true;
                    foreach ( $mods as $mod ) {
                        $success = true;
                        if (($this->output  = $this->objPatch->applyUpdates ( $mod ['module_id'] )) === FALSE) {
                            $success = false;
                            $error .= str_replace ( '[MODULE]', $mod ['module_id'], $this->objLanguage->languageText ( 'mod_modulecatalogue_failed', 'modulecatalogue' ) ) . "<br />";
                        }
                    }
                    //var_dump($error);
                    //var_dump($this->output);
                    if (! $success) {
                        $this->setVar ( 'error', $error );
                    }
                    $this->setVar ( 'output', $this->output );
                    $this->setVar ( 'patchArray', $this->objPatch->checkModules () );
                    return 'updates_tpl.php';

                case 'makepatch' :
                    return 'makepatch_tpl.php';

                case 'reloaddefaultdata' :
                    $moduleId = $this->getParam ( 'moduleid' );
                    $this->objModuleAdmin->loadData ( $moduleId );
                    return $this->nextAction ( 'list', array ('cat' => $activeCat ) );

                case 'search' :
                    $str = $this->getParam ( 'srchstr' );
                    $type = $this->getParam ( 'srchtype' );
                    $result = $this->objCatalogueConfig->searchModuleList ( $str, $type );
                    $this->setSession('modcatsearchresults', $result);
                    $this->setVar ( 'result', $result );
                    return 'front_tpl.php';

                case 'updatexml' :
                    $summary = $this->objCatalogueConfig->writeCatalogue ();
                    $message = $this->objLanguage->languageText ( 'mod_modulecatalogue_xmlupdated', 'modulecatalogue' );
                    if (is_array($summary)) {
                        $detail = $this->objLanguage->languageText(
                            'mod_modulecatalogue_refreshsummary',
                            'modulecatalogue'
                        );
                        $detail = str_replace('{DISCOVERED}', (string) $summary['discovered'], $detail);
                        $detail = str_replace('{REMOVED}', (string) count($summary['removed']), $detail);
                        $message .= ' ' . $detail;
                        if (empty($summary['reconciled'])) {
                            $message .= ' ' . $this->objLanguage->languageText(
                                'mod_modulecatalogue_reconcileskipped',
                                'modulecatalogue'
                            );
                        } elseif (!empty($summary['removed'])) {
                            $message .= ' ' . htmlspecialchars(implode(', ', $summary['removed']), ENT_QUOTES, 'UTF-8');
                        }
                    }
                    return $this->nextAction ( null, array ('message' => $message ) );

                case 'uploadarchive' :
                    $file = $_FILES ['archive'] ['name'];
                    $module = substr ( $file, 0, strpos ( $file, '.' ) );
                    $tmpFile = $_FILES ['archive'] ['tmp_name'];
                    var_dump ( $_FILES );
                    if ($_FILES ['archive'] ['size'] == 0) {
                        $this->setSession ( "output", $this->objLanguage->languageText ( 'mod_modulecatalogue_notfound', 'modulecatalogue' ) );
                        $this->setVar ( 'error', 1 );
                        return 'front_tpl.php';
                    }
                    if (is_dir ( $this->objConfig->getModulePath () . $module )) {
                        $this->setSession ( 'output', $this->objLanguage->languageText ( 'mod_modulecatalogue_directoryexists', 'modulecatalogue' ) );
                        $this->setVar ( 'error', 1 );
                        return 'front_tpl.php';
                    }
                    if (! file_exists ( $file )) {
                        $this->setSession ( "output", $this->objLanguage->languageText ( 'mod_modulecatalogue_transferfailed', 'modulecatalogue' ) );
                        $this->setVar ( 'error', 1 );
                        return 'front_tpl.php';
                    }
                    if (strtolower ( substr ( $file, strlen ( $file ) - 4, 4 ) ) == '.zip') {
                        $objZip = $this->getObject ( 'wzip', 'utilities' );
                        if (! $objZip->unZipArchive ( $tmpFile, $this->objConfig->getModulePath () )) {
                            $this->setSession ( "output", $this->objLanguage->languageText ( 'mod_modulecatalogue_unziperror', 'modulecatalogue' ) . "<br /> $objZip->error" );
                            $this->setVar ( 'error', 1 );
                            return 'front_tpl.php';
                        }
                    } else {
                        require_once ($this->getPearResource ( 'Archive/Tar.php' ));
                        $objArchive = new Archive_Tar ( $tmpFile );
                        if (! $objArchive->extract ( $this->objConfig->getModulePath () )) {
                            $this->setSession ( "output", $this->objLanguage->languageText ( 'mod_modulecatalogue_untarerror', 'modulecatalogue' ) );
                            $this->setVar ( 'error', 1 );
                            return 'front_tpl.php';
                        }
                    }
                    return $this->nextAction ( 'install', array ('cat' => $activeCat, 'mod' => $module ) );
                case 'downloadpo' :
                    $this->setPageTemplate ( NULL );
                    $this->setLayoutTemplate ( NULL );
                    $langName = $_POST ['langname'];
                    header ( "Content-type: text/plain" );
                    //header("Content-length: 0");
                    header ( "Content-Disposition: attachment; filename=$langName.po" );
                    //header("Content-Description: PHP Generated Data");
                    $this->objPOFile->export ( $langName );
                    break;
                default :
                    throw new customException ( $this->objLanguage->languageText ( 'mod_modulecatalogue_unknownaction', 'modulecatalogue' ) . ': ' . $action );
                    break;
            }
        } catch ( customException $e ) {
            $this->errorCallback ( 'Caught exception: ' . $e->getMessage () );
            exit ();
        }
    }

    /**
     * This method is a 'wrapper' function - it takes info from the
     * 'register.conf' file provided by the module to be registered,
     * and passes it to its namesake function in the modulesadmin
     * class - which is where the SQL entries actually happen.
     * @author James Scoble
     * @param  string $modname the module_id of the module to be used
     * @return string $regResult
     */
    private function installModule($modname, $upgrade = FALSE) {
        try {
            $filepath = $this->objModFile->findRegisterFile ( $modname );
            if ($filepath) { // if there were no file it would be FALSE
                $this->registerdata = $this->objModFile->readRegisterFile ( $filepath );

                if ($this->registerdata) {
                    // Added 2005-08-24 as extra check
                    if (isset ( $this->registerdata ['WARNING'] ) && ($this->getParm ( 'confirm' ) != '1')) {
                        $this->output = $this->registerdata ['WARNING'];
                        return FALSE;
                    }
                    // var_dump($this->registerdata); die();
                    if ($upgrade == TRUE) {
                        return $this->objPatch->applyUpdates ( $modname );
                    } else {
                        return $this->objModuleAdmin->installModule ( $this->registerdata );
                    }
                }
            } else {
                $this->output = $this->objLanguage->languageText ( 'mod_modulecatalogue_errnofile', 'modulecatalogue' );
                return FALSE;
            }
        } catch ( customException $e ) {
            $this->errorCallback ( 'Caught exception: ' . $e->getMessage () );
            exit ();
        }
    } // end of function


    /**
     * This method is a 'wrapper' function - it takes info from the 'register.conf'
     * file provided by the module to be registered, and passes it to its namesake
     * function in the modulesadmin class - which is where the SQL entries actually
     * happen. It uses file() to load the register.php file into an array, then
     * chew through it line by line, looking for keywords.
     *
     * @author  James Scoble
     * @param   string $modname the module_id of the module to be used
     * @returns boolean TRUE or FALSE
     */
    private function uninstallModule($modname) {
        try {
            // find all available applications
            $applications = $this->objLuAdmin->perm->getApplications();
            //var_dump($applications); die();
            $filepath = $this->objModFile->findRegisterFile ( $modname );
            $this->registerdata = $this->objModFile->readRegisterFile ( $filepath );
            if (is_array ( $this->registerdata )) {
                return $this->objModuleAdmin->uninstallModule ( $modname, $this->registerdata );
            }
            else {
                $this->output = $this->objLanguage->languageText ( 'mod_modulecatalogue_errnofile', 'modulecatalogue' );
                return FALSE;
            }
        } catch ( customException $e ) {
            $this->errorCallback ( 'Caught exception: ' . $e->getMessage () );
            exit ();
        }
    }

    /**
     * Method to handle registration of multiple modules at once
     * @param array $modArray
     */
    private function batchRegister($modArray) {
        try {
            foreach ( $modArray as $line ) {
                if ($line != 'on') {
                    if (! $this->smartRegister ( $line )) {
                        //$this->output = str_replace('[MODULE]',$line,$this->objLanguage->languageText('mod_modulecatalogue_failed','modulecatalogue'));
                        return FALSE;
                    }
                }
            }
            return TRUE;
        } catch ( customException $e ) {
            $this->errorCallback ( 'Caught exception: ' . $e->getMessage () );
            exit ();
        }
    }

    /**
     * This method is designed to handle the registeration of multiple modules at once.
     * @param string $modname
     */
    private function smartRegister($modname) {
        try {
            $isReg = $this->objModule->checkIfRegistered ( $modname, $modname );
            if ($isReg) {
                return TRUE;
            }
            $filepath = $this->objModFile->findRegisterFile ( $modname );

            if ($filepath) { //if there were no file it would be FALSE
                $registerdata = $this->objModFile->readRegisterFile ( $filepath );
                if ($registerdata) {
                    if (isset ( $registerdata ['DEPENDS'] )) {
                        foreach ( $registerdata ['DEPENDS'] as $line ) {
                            $result = $this->smartRegister ( $line );
                            if ($result == FALSE) {
                                $this->output = $this->objModuleAdmin->output . "\n";
                                $this->output .= str_replace ( '{MODULE}', $line, $this->objLanguage->languageText ( 'mod_modulecatalogue_needmodule', 'modulecatalogue' ) ) . "\n";
                                return FALSE;
                            }
                        }
                    }
                    $regResult = $this->objModuleAdmin->installModule ( $registerdata );
                    if ($regResult) {
                        $this->output .= str_replace ( '[MODULE]', $modname, $this->objLanguage->languageText ( 'mod_modulecatalogue_regconfirm', 'modulecatalogue' ) ) . "\n";
                    }
                    return $regResult;
                }
            } else {
                $this->output .= $this->objLanguage->languageText ( 'mod_modulecatalogue_errnofile', 'modulecatalogue' ) . "\n";
                return FALSE;
            }
        } catch ( customException $e ) {
            $this->errorCallback ( 'Caught exception: ' . $e->getMessage () );
            exit ();
        }
    }

    /**
     * Method to handle deregistration of multiple modules at once
     * @param array $modArray
     */
    private function batchDeregister($modArray) {
        try {
            foreach ( $modArray as $line ) {
                if (! $this->smartDeregister ( $line )) {
                    return false;
                }
            }
            return TRUE;
        } catch ( customException $e ) {
            $this->errorCallback ( 'Caught exception: ' . $e->getMessage () );
            exit ();
        }
    }

    /**
     * This method is designed to handle the deregisteration of multiple modules at once.
     * @param string $modname
     */
    private function smartDeregister($modname) {
        try {
            $isReg = $this->objModule->checkIfRegistered ( $modname, $modname );
            if ($isReg == FALSE) {
                return TRUE;
            }
            $filepath = $this->objModFile->findRegisterFile ( $modname );
            if ($filepath) { // if there were no file it would be FALSE
                $registerdata = $this->objModFile->readRegisterFile ( $filepath );
                if ($registerdata) {
                    // Here we get a list of modules that depend on this one
                    $depending = $this->objModule->getDependencies ( $modname );
                    if ((is_countable($depending) ? count($depending) : 0) > 0) {
                        foreach ( $depending as $line ) {
                            $result = $this->smartDeregister ( $line );
                            if ($result == FALSE) {
                                return FALSE;
                            }
                        }
                    }

                    $regResult = $this->objModuleAdmin->uninstallModule ( $modname, $registerdata );
                    if ($regResult) {
                        $this->output [] = str_replace ( '[MODULE]', $modname, $this->objLanguage->languageText ( 'mod_modulecatalogue_deregconfirm', 'modulecatalogue' ) );
                    }
                    return $regResult;
                }
            } else {
                $this->output = $this->objLanguage->languageText ( 'mod_modulecatalogue_errnofile', 'modulecatalogue' );
                return FALSE;
            }
        } catch ( customException $e ) {
            $this->errorCallback ( 'Caught exception: ' . $e->getMessage () );
            exit ();
        }
    }

    /**
     * Method to install newly added depenedencies of a module
     *
     * @param string $moduleId the module whose dependencies must be updated
     */
    private function updateDeps($moduleId) {
        $rData = $this->objModFile->readRegisterFile ( $this->objModFile->findRegisterFile ( $moduleId ) );
        foreach ( $rData ['DEPENDS'] as $dep ) {
            if (! $this->smartRegister ( trim ( $dep ) )) {
                throw new customException ( "Error installing dependency $dep: {$this->objModuleAdmin->output} {$this->objModuleAdmin->getLastError()}" );
            }
        }
    }

     /**
     * The filename of the progress file.
     *
     * @var string $progress_fn
     */
    private $progress_fn = "progress.xml";

     /**
     * The content that is written to the progress file.
     *
     * @var string $progress_content
     */
    private $progress_content = "";

     /**
     * The current step.
     *
     * @var mixed $progress_step
     */
    private $progress_step = false;

     /**
     * The total number of steps.
     *
     * @var mixed $progress_totalSteps
     */
    private $progress_totalSteps = false;

    /**
     * Reset the current step.
     *
     */
    private function reset_progess_step()
    {
        $this->progress_step = false;
    }

    /**
     * Set the current step.
     *
     * @param integer $step The current step
     */
    private function set_progess_step($step)
    {
        $this->progress_step = $step;
    }

    /**
     * Increment the current step.
     *
     */
    private function increment_progess_step()
    {
        ++$this->progress_step;
    }

    /**
     * Reset the total number of steps.
     *
     */
    private function reset_progess_totalSteps()
    {
        $this->progress_totalSteps = false;
    }

    /**
     * Set the total number of steps.
     *
     * @param integer $totalSteps The total number of steps
     */
    private function set_progess_totalSteps($totalSteps)
    {
        $this->progress_totalSteps = $totalSteps;
    }

    /**
     * Output status messages to the progress file during first-time registration. The progress file is read by the installer.
     *
     * @param string $status The status message
     */
    private function update_progess($status)
    {
        // Check if the install method is the AJAX method.
        if ($this->ajaxInstall) {
            if ($this->progress_totalSteps === false) {
                $percentage = 0;
            }
            else {
                if ($this->progress_step === false) {
                    $percentage = 0;
                }
                else {
                    $percentage = (int)($this->progress_step*100/$this->progress_totalSteps);
                }
            }

            // Append the status message to the content.
            $this->progress_content .= $status;
            // Write the content to the progress file.
            // xml:lang=\"EN\"
            $contents = <<<EOT
<?xml version="1.0" encoding="ISO-8859-1"?>
<data>
    <percentage>{$percentage}</percentage>
    <message>{$this->progress_content}</message>
</data>
EOT;
            if (file_put_contents($this->progress_fn, $contents) === FALSE) {
                //echo "Failure!";
                //exit(0);
            }
        }
    }
    /**
     * This is a method to handle first-time registration of the basic modules
     *
     * @param string sysType The type of system to install
     */
    private function firstRegister($sysType) {
        try {
            $this->reset_progess_totalSteps();
            $this->reset_progess_step();
            log_debug ( "Installing system, type: $sysType" );
            //$this->update_progess("\n");
            $this->update_progess("Installing system, type: $sysType\n");
            $root = $this->objConfig->getsiteRootPath ();
            if (! file_exists ( $root . 'config/config.xml' )) {
                throw new customException ( "could not find config.xml! tried {$root}config/config.xml" );
            }
            if (! file_exists ( $root . 'installer/dbhandlers/systemtypes.xml' )) {
                throw new customException ( "could not find systemtypes.xml! tried {$root}installer/dbhandlers/default_modules.txt" );
            }
            $objXml = simplexml_load_file ( $root . 'installer/dbhandlers/systemtypes.xml' );
            // Compute number of modules that will be installed.
            // BEGIN >>
            $total = 0;
            $coreList = $objXml->xpath ( "//category[categoryname='Basic System Only']" );
            $total += count($coreList [0]->module);
            if ($sysType != "Basic System Only") {
                $specificList = $objXml->xpath ( "//category[categoryname='$sysType']" );
                $total += count($specificList [0]->module);
            }
            $this->set_progess_totalSteps($total);
            // << END
            // Set step to zero.
            $this->set_progess_step(0);
            /*
             * Provision the canonical administrator identity and baseline
             * groups before module registration. This deliberately creates
             * no login session; normal authentication remains mandatory.
             */
            $objInitialAdminProvisioning = $this->getObject(
                'initialadminprovisioningservice',
                'security'
            );
            $adminProvisioning = $objInitialAdminProvisioning
                ->ensureInitialAdministrator('1');
            if (!is_array($adminProvisioning) || empty($adminProvisioning['ok'])) {
                $code = is_array($adminProvisioning)
                    && isset($adminProvisioning['code'])
                    ? $adminProvisioning['code']
                    : 'unknown_failure';
                throw new customException(
                    'Initial administrator baseline provisioning failed: ' . $code
                );
            }

            log_debug ( 'Installing core modules' );
            $this->update_progess("Installing core modules\n");
            $coreList = $objXml->xpath ( "//category[categoryname='Basic System Only']" );
            foreach ( $coreList [0]->module as $module ) {
                $this->update_progess("$module...");
                if (! $this->smartRegister ( trim ( $module ) )) {
                    throw new customException ( "Error installing module $module: {$this->objModuleAdmin->output} {$this->objModuleAdmin->getLastError()}" );
                }
                $this->increment_progess_step();
                $this->update_progess("OK\n");
            }
            if ($sysType != "Basic System Only") {
                log_debug ( 'Installing system specific modules' );
                $this->update_progess("Installing system specific modules\n");
                $specificList = $objXml->xpath ( "//category[categoryname='$sysType']" );
                foreach ( $specificList [0]->module as $module ) {
                    $this->update_progess("$module...");
                    if (! $this->smartRegister ( trim ( $module ) )) {
                        throw new customException ( "Error installing module $module: {$this->objModuleAdmin->output} {$this->objModuleAdmin->getLastError()}" );
                    }
                    $this->increment_progess_step();
                    $this->update_progess("OK\n");
                }
            }
            $objInitialAdminProvisioning = $this->getObject(
                'initialadminprovisioningservice',
                'security'
            );
            $adminProvisioning = $objInitialAdminProvisioning
                ->ensureInitialAdministrator('1');

            if (!is_array($adminProvisioning) || empty($adminProvisioning['ok'])) {
                $code = is_array($adminProvisioning) && isset($adminProvisioning['code'])
                    ? $adminProvisioning['code']
                    : 'unknown_failure';
                throw new customException(
                    'Initial administrator provisioning failed: ' . $code
                );
            }

            // Flag first-time registration complete only after provisioning succeeds.
            $this->objSysConfig->insertParam ( 'firstreg_run', 'modulecatalogue', TRUE, 'mod_modulecatalogue_firstreg_run_desc' );
            log_debug ( 'first time registration performed, variable set. First time registration cannot be performed again unless system variable \'firstreg_run\' is unset.' );
            $this->update_progess("Installation complete.\n");
        } catch ( customException $e ) {
            if ($this->ajaxInstall) {
                http_response_code(500);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(array(
                    'ok' => false,
                    'code' => 'first_registration_failed',
                    'message' => $e->getMessage()
                ));
            } else {
                $this->errorCallback ( 'Caught exception: ' . $e->getMessage () );
            }
            exit ();
        }
    }

    /**
     * The error callback function, defers to configured error handler
     *
     * @param  string $exception
     * @return void
     */
    public function errorCallback($exception) {
        echo customException::cleanUp ( $exception );
    }

    /**
     * Method to determine whether the module requires the user to be logged in.
     *
     * @return TRUE|FALSE false if the user is carrying out first time module registration, else true.
     */
    public function requiresLogin($action = null) {
        try {
            $action = $this->getParm ( 'action' );
            if (
                $action == 'firsttimeregistration'
                || $action == 'canonicalidentityproof'
            ) {
                return FALSE;
            } else {
                return TRUE;
            }
        } catch ( customException $e ) {
            $this->errorCallback ( 'Caught exception: ' . $e->getMessage () );
            exit ();
        }
    }

    /**
     * kind of a hack wrapper method to get the messed up params from the header via getParam in the engine
     *
     * @param  string $name parameter name
     * @param  string $def  default param value
     * @return string Parameter value or default if it doesnt exist
     */
    public function getParm($name, $def = null) {
        try {
            if (($res = $this->getParam ( $name )) == null) {
                return $this->getParam ( 'amp;' . $name, $def );
            } else {
                return $res;
            }
        } catch ( customException $e ) {
            $this->errorCallback ( 'Caught exception: ' . $e->getMessage () );
            exit ();
        }
    }

    public function processTags() {
        foreach ( $this->tagCloud as $arrs ) {
            if (! empty ( $arrs ['tags'] )) {
                $tagarr [] = explode ( ',', preg_replace('~ +~', '', $arrs ['tags'] ) );
            }
        }
        $tags = NULL;
        if (empty ( $tagarr )) {
            return NULL;
        }
        foreach ( $tagarr as $tagger ) {
            foreach ( $tagger as $tagged ) {
                $tags .= $tagged . ",";
            }
        }
        $tags = str_replace ( ',,', ',', $tags );
        $tagarray = explode ( ',', $tags );
        $basetags = array_unique ( $tagarray );
        foreach ( $basetags as $q ) {
            $numbers = array_count_values ( $tagarray );
            $weight = $numbers [$q];
            $entry [] = array ('name' => $q, 'url' => $this->uri ( array ('action' => 'search', 'srchstr' => $q, 'srchtype' => 'tags', 'cat' => 'all' ), 'modulecatalogue' ), 'weight' => $weight * 1000, 'time' => time () );
        }
        // var_dump($entry); die();
        return $entry;
    }

    public function deltree($f) {
        if (is_dir ( $f )) {
            foreach ( scandir ( $f ) as $item ) {
                if (! strcmp ( $item, '.' ) || ! strcmp ( $item, '..' )) {
                    continue;
                }
                $this->deltree ( $f . "/" . $item );
            }
            rmdir ( $f );
        } else {
            unlink ( $f );
        }
    }

    private function handleZip($zipfile, $modtype) {
        if($this->extzip == TRUE) {
            $zip = new ZipArchive;
            if($modtype == 'core') {
                $zip->open("core.zip");
                if (! $zip->extractTo( $this->objConfig->getsiteRootPath ()."classes/" )) {
                        //log_debug($zip->error);
                        $zip->close();
                        return FALSE;
                }
                else {
                    $zip->close();
                    return TRUE;
                }
            }
            // now for a core_module
            elseif($modtype == 'core_modules') {
                $zip->open($zipfile.".zip");
                if (! $zip->extractTo( $this->objConfig->getsiteRootPath () . 'core_modules/'.$zipfile )) {
                        //log_debug($zip->error);
                        $zip->close();
                        return FALSE;
                }
                else {
                    $zip->close();
                    return TRUE;
                }
            }
            // last but not least the regular modules
            elseif($modtype == 'modules') {
                $zip->open($zipfile.".zip");
                if (! $zip->extractTo( $this->objConfig->getModulePath ().$zipfile )) {
                        //log_debug($zip->error);
                        $zip->close();
                        return FALSE;
                }
                else {
                    $zip->close();
                    return TRUE;
                }
            }
            elseif($modtype == 'skin') {
                $zip->open($zipfile.".zip");
                if (! $zip->extractTo( $this->objConfig->getSkinRoot ().$zipfile )) {
                        //log_debug($zip->error);
                        $zip->close();
                        return FALSE;
                }
                else {
                    $zip->close();
                    return TRUE;
                }
            }
            else {
                log_debug("Unknown module type!");
                return FALSE;
            }
        }
        else {
            // we are using the wzip PHP implementation an you are probably using MAMP
            $zip = $this->getObject ( 'wzip', 'utilities' );
            if($modtype == 'core') {
                if (! $zip->unZipArchive( 'core.zip', $this->objConfig->getsiteRootPath ()."classes/" )) {
                        //log_debug($zip->error);
                        return FALSE;
                }
                else {
                    return TRUE;
                }
            }
            // now for a core_module
            elseif($modtype == 'core_modules') {
                if (! $zip->unZipArchive( $zipfile.".zip", $this->objConfig->getsiteRootPath () . 'core_modules/'.$zipfile )) {
                        //log_debug($zip->error);
                        return FALSE;
                }
                else {
                    return TRUE;
                }
            }
            // last but not least the regular modules
            elseif($modtype == 'modules') {
                if (! $zip->unZipArchive( $zipfile.".zip", $this->objConfig->getModulePath ().$zipfile )) {
                        //log_debug($zip->error);
                        return FALSE;
                }
                else {
                    return TRUE;
                }
            }
            elseif($modtype == 'skin') {
                if (! $zip->unZipArchive( $zipfile.".zip", $this->objConfig->getSkinRoot ().$zipfile )) {
                        log_debug($zip->error);
                        return FALSE;
                }
                else {
                    return TRUE;
                }
            }
            else {
                log_debug("Unknown module type!");
                return FALSE;
            }
        }

    }
}
?>
