<?php

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

/**
 * textare class to use to make textarea inputs.
 *
 * @package   htmlelements
 * @category  HTML Controls
 * @author    Wesley Nitsckie
 * @copyright 2004, University of the Western Cape & AVOIR Project
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General
  Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @todo      -c HTML Editor that will extend this object
 */
//require_once("htmlbase_class_inc.php");
class htmlarea extends ChisimbaObject {

    /**
     * CHISIMBA EDITOR COMPATIBILITY BOUNDARY
     *
     * Application modules must request rich-text editing through this class
     * in the htmlelements core module. They must not choose or instantiate a
     * vendor editor implementation directly.
     *
     * The historical class name is retained as Chisimba's stable server-side
     * contract. A later modern editor adapter may replace the implementation
     * behind this boundary without requiring module-level rewrites.
     *
     * @var string
     */
    const EDITOR_COMPATIBILITY_BOUNDARY = 'htmlelements/htmlarea';


    /**
     *
     * @var string $siteRootPath: The path to the site
     */
    var $siteRootPath;
    /**
     *
     * @var string $cols: The number of columns the textare will have
     */
    var $cols;
    /**
     *
     * @var string $rows: The number of rows the textare will have
     */
    var $rows;
    /**
     *
     * @var string $label: The label of the editor
     */
    var $label;
    /**
     *
     * @var string $cssClass: The style sheet class
     */
    var $cssClass;
    /**
     * @var string css Id property
     */
    var $cssId;
    /**
     *
     * @var string $height: The height of the editor
     */
    var $height;
    /**
     *
     * @var string $width: The width of the editor
     */
    var $width;
    /**
     *
     * @var string $toolbarSet: The toolbarSet of the editor : either Default or Basic
     */
    var $toolbarSet;
    /**
     * @var boolean $context Are we in a context aware mode.
     */
    var $context;
    /**
     * @var string $fckVersion Which version of FCKEditor to load (2.5.1 vs 2.6.3)
     */
    public $fckVersion;
    /**
     * @var string $sysEditor Legacy compatibility value; editoradapter selects TinyMCE
     */
    public $sysEditor;
    /**
     * @var string $templatePath Path to fckeditor templates
     */
    public $templatePath;
    /**
     * this var stores the instance of ckeditor when its created
     * @var <String>
     */
    var $editor;
    /**
     * This var holds custom plugins
     * @var <type>
     */
    var $extraPlugins;
    /**
     * This enables/disables  in editor
     * @var <type> 
     */
    var $disableSpellChecker;
    /**
     * Time to auto save contents in editor
     * @var <type> 
     */
    var $autoSaveTime;
    /**
     * config to turn on/off auto save
     * @var <type>
     */
    var $enableAutoSave;

    /**
     * Method to establish the default values
     */
    function init($name=null, $value=null, $rows=4, $cols=50, $context=false) {
        $this->sysConf = $this->getObject('dbsysconfig', 'sysconfig');
        // Editor implementation is selected only by editoradapter.
        $this->autoSaveTime = $this->sysConf->getValue('AUTOSAVE_TIME', 'htmlelements');
        $this->enableAutoSave = $this->sysConf->getValue('ENABLE_AUTOSAVE', 'htmlelements');
        $this->disableSpellChecker = $this->sysConf->getValue('DISABLE_SPELLCHECKER', 'ckeditor', true);
        $this->height = '400px';
        $this->width = '100%';
        $this->toolbarSet = 'Default';
        $this->name = $name;
        $this->value = $value;
        $this->rows = $rows;
        $this->cols = $cols;
        $this->css = 'textarea';
        $this->cssClass = 'textarea';
        $this->cssId = $name;
        $this->templatePath = ''; //will load the default template path
        //$siteRootPath = "http://".$_SERVER['HTTP_HOST']."/nextgen/";
        //$this->setSiteRootPath($siteRoot);
        //$this->_objConfig =& $this->getObject('config', 'config');
        //$siteRootPath = $this->_objConfig->siteRootPath();
        $objConfig = $this->getObject('altconfig', 'config');
        $this->siteRoot = $objConfig->getsiteRoot();
        //$this->siteRoot=$this->getSiteRoot();
        $this->sitePath = $objConfig->getsitePath();
        $this->context = $context;
        $this->toolbarSet = 'advanced';
    }

    /**
     * Method to set the version of FCKEditor to load (2.5.1 vs 2.6.3)
     *
     */
    public function setVersion($fckVersion) {
        $this->fckVersion = $fckVersion;
    }

    /**
     * function to set the root path
     *
     * @var string $siteRootPath: The site path
     */
    function setSiteRootPath($siteRootPath) {
        $this->siteRootPath = $siteRootPath;
    }

    /**
     * sets the extra plugins to appear in tool bar
     */
    function setExtraPlugins($plugins) {
        $this->extraPlugins = "contexttools";
    }

    /**
     * function to set the value of one of the properties of this class
     *
     * @var string $name: The name of the textare
     */
    function setName($name) {
        $this->name = $name;
    }
    
    /**
     * @access public
     * @param type $value The css class value
     */
    function setCssClass($value){
        $this->cssClass = $value;
    }

    /**
     * function to set the amount of rows
     * @var string $Rows: The number of rows of the textare
     *
     */
    function setRows($rows) {
        $this->rows = $rows;
    }

    /**
     * function to set the amount of cols
     * @var string $cols: The number of cols of the textare
     *
     */
    function setColumns($cols) {
        $this->cols = $cols;
    }

    /**
     * function to set the content
     * @var string $content: The content of the textare
     */
    function setContent($value=null) {
        $this->value = $value;
    }

    /**
     * Method to display the WYSIWYG Editor
     */
    function show() {
        $adapter = $this->newObject('editoradapter', 'htmlelements');

        $result = $adapter->render(array(
            'name' => $this->name,
            'id' => $this->cssId,
            'value' => $this->value,
            'cssClass' => $this->cssClass,
            'height' => $this->height,
            'width' => $this->width,
            'toolbar' => $this->toolbarSet,
            'siteRoot' => $this->siteRoot,
            'sitePath' => $this->sitePath,
            'tinymceUri' => $this->getResourceUri(
                'tinymce8/tinymce.min.js',
                'htmlelements'
            ),
            'disableSpellChecker' => $this->disableSpellChecker,
        ));

        if (isset($result['headers']) && is_array($result['headers'])) {
            foreach ($result['headers'] as $header) {
                $this->appendArrayVar('headerParams', $header);
            }
        }

        return isset($result['html']) ? $result['html'] : '';
    }

    /**
     * Method to show the FCKEditor
     * @return string
     */
    function showFCKEditor($version = '2.6.3') {
        // Legacy public entry point retained for module compatibility.
        return $this->show();
    }

    /**
     * Method to load JS to fix FCKEditor refusing to focus
     * @author Tohir Solomons
     *
     *         Taken from: http://www.tohir.co.za/2006/06/fckeditor-doesnt-want-to-focus-in.html
     */
    function showFCKEditorWakeupJS() {
        // Retained as a harmless compatibility method.
        return;
    }

    /**
     * Method to show the tinyMCE Editor
     * @return string
     */
    function showTinyMCE() {
        // Legacy public entry point retained for module compatibility.
        return $this->show();
    }

    /**
     * Method to set the toolbar set to basic
     * meaning that only the basic commands are available of the editor
     */
    function setBasicToolBar() {
        $this->toolbarSet = 'simple';
    }

    /**
     * Method to toolbar set to default
     */
    function setDefaultToolBarSet() {
        $this->toolbarSet = 'advanced';
    }

    /**
     * Method to toolbar set to default without the save button
     */
    function setDefaultToolBarSetWithoutSave() {
        $this->toolbarSet = 'DefaultWithoutSave';
    }

    /**
     * sets toolbar to be MCQ specific
     */
    function setMCQToolBar() {
        $this->toolbarSet = 'mcq';
    }

    /**
     * Method to toolbar set to CMS Specific
     */
    function setCMSToolBar() {
        $this->toolbarSet = 'cms';
    }

    /**
     * Method to toolbar set to Form Builder 'forms' Specific
     */
    function setFormsToolBar() {
        $this->toolbarSet = 'forms';
    }

    /**
     * Method to load the Content Templates
     * Loads content templates from usrfiles/cmstemplates/fcktemplates.xml
     *
     * This file gets manipulated via the cmsadmin template manager
     */
    function loadCMSTemplates() {
        $objConfig = $this->newObject('altconfig', 'config');
        $this->templatePath = $objConfig->getSitePath() . $objConfig->getcontentPath() . 'cmstemplates/' . $this->fckVersion . '/fcktemplates.xml';
    }

    /**
     * Method to get the javascript files
     * @return string
     */
    public function getJavaScripts() {
        // Assets are now registered by editoradapter.
        return '';
    }

    /**
     * Gets the site root (equivalent to:
     *   $objConfig=$this->getObject('altconfig','config');
     *   ... $objConfig->getsiteRoot());
     * )
     * Caters for server aliases, which the altconfig class does not cater for.
     * @author Jeremy O'Connor
     * @return string The site root
     */
    private function getSiteRoot() {
        $https = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] == 'off' ? FALSE : TRUE : FALSE;
        $http_host = $_SERVER ['HTTP_HOST'];
        $php_self = $_SERVER['PHP_SELF'];
        $path = str_replace('index.php', '', $php_self);
        return ($https ? 'https://' : 'http://') . $http_host . $path;
    }

}

?>
