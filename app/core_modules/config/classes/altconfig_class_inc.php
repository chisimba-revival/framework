<?php

/**
 * System configuration
 *
 * System configuration for Chisimba
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
 * @package   config
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2007 Paul Scott
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */

/**
 * Class to manipulate system configs
 *
 * The altconfig class manipulates system configurations stored in the config.xml file in the config directory of the root
 * of the application.
 *
 * @category  Chisimba
 * @package   config
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2007 Paul Scott
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
require_once __DIR__ . '/configurationdocument.php';

class altconfig extends ChisimbaObject {

    private $siteDocument;
    private $siteDestination;
    private $propertiesDocument;
    private $propertiesDestination;
    private $lastWriteError;

    /** Safe diagnostic code/message; never contains configuration contents. */
    public function getLastWriteError() { return $this->lastWriteError; }

    public function getRevision() { return $this->siteDocument()->revision(); }

    private function siteDocument($allowMissing = false)
    {
        $this->_path = $this->_path ?? 'config/';
        $path = ($this->_path === '' ? '.' : rtrim($this->_path, '/')) . '/config.xml';
        if ($path[0] !== '/') { $path = getcwd() . '/' . $path; }
        if ($this->siteDocument === null || $this->siteDestination !== $path) {
            $this->siteDocument = null;
            $this->_root = null;
            $document = new ChisimbaConfigurationDocument($path, 'Settings', $allowMissing);
            $this->siteDocument = $document;
            $this->siteDestination = $path;
            $this->_root = $document->root;
        }
        return $this->siteDocument;
    }

    private function propertiesDocument($path = null, $allowMissing = false)
    {
        $path = $path === null && $this->propertiesDestination !== null
            ? $this->propertiesDestination
            : rtrim($path ?? $this->_path ?? 'config', '/') . '/sysconfig_properties.xml';
        if ($path[0] !== '/') { $path = getcwd() . '/' . $path; }
        if ($this->propertiesDocument === null || $this->propertiesDestination !== $path) {
            $this->propertiesDocument = null;
            $this->_property = null;
            $this->propertiesDestination = $path;
            $document = new ChisimbaConfigurationDocument($path, 'sysConfigSettings', $allowMissing);
            $this->propertiesDocument = $document;
            $this->propertiesDestination = $path;
            $this->_property = $document->root;
        }
        return $this->propertiesDocument;
    }

    // State populated during initialisation and service calls.
    public $SettingsDirective;


    /**
     * The path of the files to be read or written
     *
     * @access public
     * @var    string
     */
    public $_path = null;

    /**
     * The root object for configs read
     *
     * @access private
     * @var    string
     */
    protected $_root;

    /**
     * The root object for properties read
     *
     * @access private
     * @var    string
     */
    protected $_property;

    /**
     * The options value for altconfig read / write
     *
     * @access private
     * @var    string
     */
    protected $_options;

    /**
     * The sysconfig object for sysconfig storage
     *
     * @access private
     * @var    array
     */
    protected $_sysconfigVars;

    /**
     * languagetext object
     *
     * @var object
     */
    public $Text;

    /**
     * The global error callback for altconfig errors
     *
     * @access public
     * @var    string
     */
    public $_errorCallback;

    /**
     * Constructor
     *
     * This object needs external construction
     *
     * @return void
     * @access public
     * @throws customException Exception description (if any) ...
     */
    public function __construct($objEngine = null, $moduleName = null) {
        // Documents own parsing and persistence; no PEAR bootstrap is needed.

    }

    /**
     * Method to parse config options.
     * For use when reading configuration options
     *
     * @access protected
     * @param  string    $config   xml file or PHPArray to parse
     * @param  string    $property used to set property value of incoming config string
     *                             $property can either be:
     *                             1. PHPArray
     *                             2. XML
     * @return boolean   True/False result.
     *
     */
    public function readConfig($config, $property) {
        // Historically the first argument is ignored: this service owns config.xml.
        if (!in_array(strtoupper($property), array('XML', 'PHPARRAY'), true)) {
            throw new RuntimeException('Unsupported configuration format.');
        }
        return $this->siteDocument()->root;
    }

    public function writeConfig($values, $property) {
        $this->lastWriteError = null;
        try {
            if (strtoupper($property) !== 'XML') { throw new RuntimeException('Unsupported configuration format.'); }
            $document = $this->siteDocument(true);
            $document->save($document->fromArray($values));
            $this->_root = $document->root;
            return true;
        } catch (RuntimeException $e) {
            $this->lastWriteError = $e->getMessage();
            return false;
        }
    }

    /**
     * Public method to append arbitrary arrays of additional parameters to the config file
     *
     * @param  array   $newsettings
     * @return boolean
     */
    public function appendToConfig($newsettings) {
        $this->lastWriteError = null;
        try {
            $values = $this->readConfig('', 'XML')->toArray()['root']['Settings'];
            if (!is_array($newsettings)) { throw new RuntimeException('Configuration values must be an array.'); }
            return $this->writeConfig(array_merge($values, $newsettings), 'XML');
        } catch (RuntimeException $e) {
            $this->lastWriteError = $e->getMessage();
            return false;
        }
    }

    /**
     * Method to get a system configuration parameter.
     *
     * @var    string $pvalue The value code of the config item
     * @var    string $pname The name of the parameter being set, use UPPER_CASE
     * @return string $value The value of the config parameter
     */
    public function getItem($pname) {
        try {
            $this->readConfig ( FALSE, 'XML' );
            if (!is_object($this->_root)) {
                return FALSE;
            }
            //Lets get the parent node section first
            $Settings = & $this->_root->getItem ( "section", "Settings" );
            if (!is_object($Settings)) {
                return FALSE;
            }
            //Now onto the directive node
            //check to see if one of them isset to search by
            if (isset ( $pname )) {
                $this->SettingsDirective = & $Settings->getItem ( "directive", "{$pname}" );
                if ($this->SettingsDirective == false) {
                    return FALSE;
                } else {
                    $value = $this->SettingsDirective->getContent ();
                    return $value;
                }
            }

        } catch ( Exception $e ) {
            throw new customException ( $e->getMessage () );
            exit ();
        }
    }

    /**
     * Method to get a system configuration parameter.
     *
     * @var    string $pvalue The value code of the config item
     * @var    string $pname The name of the parameter being set, use UPPER_CASE
     * @return string $value The value of the config parameter
     */
    public function setItem($pname, $pvalue) {
        $this->lastWriteError = null;
        try {
            $document = $this->siteDocument();
            $candidate = $document->copy();
            $settings = $candidate->getItem('section', 'Settings');
            $directive = $settings->getItem('directive', $pname);
            if (!$directive) { throw new RuntimeException('Configuration directive is missing.'); }
            $directive->setContent($pvalue);
            $document->save($candidate);
            $this->_root = $document->root;
            return true;
        } catch (RuntimeException $e) {
            $this->lastWriteError = $e->getMessage();
            return false;
        }
    }

    /**
     * Method to read sysconfig Properties options.
     * For use when reading sysconfig Properties options
     *
     * @access public
     * @param  string  path     to the properties config
     * @param  string  property used to set property value of incoming config string
     *                          $property can either be:
     *                          1. PHPArray
     *                          2. XML
     * @return boolean TRUE for success / FALSE fail .
     *
     */
    public function readProperties($path = null, $property = 'XML') {
        if (strtoupper($property) !== 'XML') { return false; }
        try { return $this->propertiesDocument($path)->root; }
        catch (RuntimeException $e) { return false; }
    }

    /**
     * Method to write sysconfig Properties options.
     * For use when writing sysconfig Properties options
     *
     * @access public
     * @param  PHParray $propertyValues which consists of :
     * @var    string   $pmodule The module code of the module owning the config item
     * @var string $pname The name of the parameter being set, use UPPER_CASE
     * @var string $plabel A label for the config parameter, usually a language string
     * @var string $value The value of the config parameter
     * @var boolean $isAdminConfigurable TRUE | FALSE Whether the parameter is admin configurable or not
     * @param  string   property        used to set property value of incoming config string
     *                                  $property can either be:
     *                                  1. PHPArray
     *                                  2. XML
     * @return boolean  TRUE for success / FALSE fail .
     *
     */
    public function writeProperties($propertyValues, $property) {
        $this->lastWriteError = null;
        try {
            if (strtoupper($property) !== 'XML') { throw new RuntimeException('Unsupported configuration format.'); }
            $document = $this->propertiesDocument(null, true);
            $document->save($document->fromArray($propertyValues));
            $this->_property = $document->root;
            return true;
        } catch (RuntimeException $e) {
            $this->lastWriteError = $e->getMessage();
            return false;
        }
    }

    /**
     * Method to update a configuration parameter.
     *
     * @var string  $pmodule The module code of the module owning the config item
     * @var string  $pname The name of the parameter being set, use UPPER_CASE
     * @var string  $pvalue The value of the config parameter
     * @var boolean $isAdminConfigurable TRUE | FALSE Whether the parameter is admin configurable or not
     */
    public function updateParam($pname, $pmodule, $pvalue, $isAdminConfigurable = false, $expectedRevision = null) {
        try {
            if ($expectedRevision !== null && (!is_string($expectedRevision)
                || !hash_equals($this->getRevision(), $expectedRevision))) {
                $this->lastWriteError = 'Configuration changed; reload before saving.';
                return false;
            }
            return $this->setItem($pname, $pvalue);
        } catch (RuntimeException $e) {
            $this->lastWriteError = $e->getMessage();
            return false;
        }
    }

    /**
     * Method to get a system configuration parameter.
     *
     * @var    string $pmodule The module code of the module owning the config item
     * @var    string $pname The name of the parameter being set, use UPPER_CASE
     * @return string $value The value of the config parameter
     */
    public function getParam($pname, $pmodule = null) {
        if ($this->_property === null && $this->readProperties() === false) { return false; }
        $settings = $this->_property->getItem('section', 'sysConfigSettings');
        $directive = $settings->getItem('directive', $pmodule ?? $pname);
        return $directive ? $directive->getContent() : false;
    }

    /**
     * Method to read a configuration parameter. This is the preferred
     * method for routine lookups.
     *
     * @public string $module The module code of the module owning the config item
     * @public string $name The name of the parameter being set, use UPPER_CASE
     *
     * @return only the value of the parameter
     */
    public function getValue($pname, $pmodule = "_site_") {
        if (! isset ( $this->$pname )) {
            //$this->getParam('',$pmodule);
        }
        if (isset ( $this->$pname )) {
            return $this->$pname;
        } else if (defined ( $pname )) {
            $defValue = constant ( $pname );
            $this->insertParam ( $pname, $pmodule, $defValue, TRUE );
            return $defValue;
        } else {
            return NULL;
        }
    }

    /**
     * The property get name of the getSiteName
     *
     * @access public
     * @return the    name of the site as string
     */
    public function getSiteName() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SITENAME" );
        //finally unearth whats inside
        $siteName = $SettingsDirective->getContent ();
        return $siteName;
        // KEWL_SITENAME;
    }

    /**
     * The property set name of the getSiteName
     *
     * @access public
     * @param  value  of the change to be made
     * @return bool   true / false
     */
    public function setSiteName($value) {
        return $this->setItem('KEWL_SITENAME', $value);
    }

    /**
     * The property get name of the System type
     *
     * @access public
     * @return the    name of the systemtype as string
     */
    public function getSystemType() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SYSTEM_TYPE" );
        //finally unearth whats inside
        $systemtype = $SettingsDirective->getContent ();
        return $systemtype;
    }

    /**
     * The property set name of the Systemtype
     *
     * @access public
     * @param  value  of the change to be made
     * @return bool   true / false
     */
    public function setSystemType($value) {
        return $this->setItem('KEWL_SYSTEM_TYPE', $value);
    }

    /**
     * Get short name of the institutionShortName
     *
     * @access public
     * @return the    short name of the site as string
     */
    public function getinstitutionShortName() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_INSTITUTION_SHORTNAME" );
        //finally unearth whats inside
        $institutionShortName = $SettingsDirective->getContent ();
        return $institutionShortName;
        // KEWL_INSTITUTION_SHORTNAME;
    }

    /**
     * Set short name of the institutionShortName
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setinstitutionShortName($value) {
        return $this->setItem('KEWL_INSTITUTION_SHORTNAME', $value);
    }

    /**
     * Get name of the institution
     *
     * @access public
     * @return the    short name of the institution as string
     */
    public function getinstitutionName() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_INSTITUTION_NAME" );
        //finally unearth whats inside
        $institutionName = $SettingsDirective->getContent ();
        return $institutionName;
        // KEWL_INSTITUTION_NAME;
    }

    /**
     * Set name of the institution
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setinstitutionName($value) {
        return $this->setItem('KEWL_INSTITUTION_NAME', $value);
    }

    /**
     * The email address of the website
     *
     * @access public
     * @return the    email address for the site as string
     */
    public function getsiteEmail() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SITEEMAIL" );
        //finally unearth whats inside
        $getsiteEmail = $SettingsDirective->getContent ();
        return $getsiteEmail;
        // KEWL_SITEEMAIL;
    }

    /**
     * The email address of the website
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setsiteEmail($value) {
        return $this->setItem('KEWL_SITEEMAIL', $value);
    }

    /**
     * The script timeout
     *
     * @access public
     * @return the    script timout in seconds
     */
    public function getsystemTimeout() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SYSTEMTIMEOUT" );
        //finally unearth whats inside
        $getsystemTimeout = $SettingsDirective->getContent ();
        return $getsystemTimeout;
        // KEWL_SYSTEMTIMEOUT;
    }

    /**
     * The script timeout
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setsystemTimeout($value) {
        return $this->setItem('KEWL_SYSTEMTIMEOUT', $value);
    }

    /**
     * Get prelogin module
     *
     * @access public
     * @return the    system prelogin module settings
     */
    public function getPrelogin() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_PRELOGIN_MODULE" );
        //finally unearth whats inside
        $getPrelogin = $SettingsDirective->getContent ();
        return $getPrelogin;

    }

    /**
     * Set prelogin module
     *
     * @access public
     * @return the    system prelogin module settings
     */
    public function setPrelogin($value) {
        return $this->setItem('KEWL_PRELOGIN_MODULE', $value);
    }

    /**
     * The URL path of the site
     *
     * @access public
     * @return the    the site path, normally / as string
     */
    public function getSitePath() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SITEROOT" );
        //finally unearth whats inside
        $getsitePath = $SettingsDirective->getContent ();

        return $getsitePath;
        // KEWL_SITEROOT;
    }

    /**
     * The URL root of the site
     *
     * @access public
     * @return the    the site root, normally / as string
     */
    public function getsiteRoot() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SITE_ROOT" );
        //finally unearth whats inside
        $getsiteRoot = $SettingsDirective->getContent ();

        return $getsiteRoot;
        // KEWL_SITE_ROOT;
    }

    /**
     * The URL root of the site
     *
     * @access public
     * @param value of the change to be made
     * @return bool true / false
     */
    public function setsiteRoot($value) {
        return $this->setItem('KEWL_SITE_ROOT', $value);
    }

    /**
     * The folder name of the default skin
     *
     * @access public
     * @return the    default skin name (normally default)
     *                leading and trailing forward slash (/)  as string
     */
    public function getdefaultSkin() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_DEFAULT_SKIN" );
        //finally unearth whats inside
        $getdefaultSkin = $SettingsDirective->getContent ();
        return $getdefaultSkin;
        // KEWL_DEFAULT_SKIN;
    }

    /**
     * The folder name of the default skin
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setdefaultSkin($value) {
        return $this->setItem('KEWL_DEFAULT_SKIN', $value);
    }

    /**
     * The skin root
     *
     * @access public
     * @return the    skin root (normally /skin/)
     *                leading and trailing forward slash (/)  as string
     */
    public function getskinRoot() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SKIN_ROOT" );
        //finally unearth whats inside
        $getskinRoot = $SettingsDirective->getContent ();
        return $getskinRoot;

    // KEWL_SKINROOT;
    }

    /**
     * Set skin root
     *
     * @param  $value     -string
     * @access public
     * @return TRUE/FALSE
     */
    public function setskinRoot($value) {
        return $this->setItem('KEWL_SKIN_ROOT', $value);
    }

    /**
     * The name of the default language (normally english)
     *
     * @access public
     * @return the    name of the default language as string
     */
    public function getdefaultLanguage() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_DEFAULT_LANGUAGE" );
        //finally unearth whats inside
        $getdefaultLanguage = $SettingsDirective->getContent ();
        return $getdefaultLanguage;
        // KEWL_DEFAULT_LANGUAGE;
    }

    /**
     * The name of the default language (normally english)
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setdefaultLanguage($value) {
        return $this->setItem('KEWL_DEFAULT_LANGUAGE', $value);
    }

    /**
     * The abbreviation of the default language (normally EN)
     *
     * @access public
     * @return the    abbreviation of the default language as string
     */
    public function getdefaultLanguageAbbrev() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_DEFAULT_LANGUAGE_ABBREV" );
        //finally unearth whats inside
        $getdefaultLanguageAbbrev = $SettingsDirective->getContent ();

        return $getdefaultLanguageAbbrev;
        // KEWL_DEFAULT_LANGUAGE_ABBREV;
    }

    /**
     * The abbreviation of the default language (normally EN)
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setdefaultLanguageAbbrev($value) {
        return $this->setItem('KEWL_DEFAULT_LANGUAGE_ABBREV', $value);
    }

    /**
     * The default extension for banners (jpg, gif, png)
     *
     * @access public
     * @return default extension for banners (jpg, gif, png) as string
     */
    public function getbannerExtension() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_BANNER_EXT" );
        //finally unearth whats inside
        $getbannerExtension = $SettingsDirective->getContent ();

        return $getbannerExtension;
        // KEWL_BANNER_EXT;
    }

    /**
     * The default extension for banners (jpg, gif, png)
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setbannerExtension($value) {
        return $this->setItem('KEWL_BANNER_EXT', $value);
    }

    /**
     * The default site root path as string
     *
     * @access public
     * @return default site root path as string
     */
    public function getsiteRootPath() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SITEROOT_PATH" );
        //finally unearth whats inside
        $getsiteRootPath = $SettingsDirective->getContent ();
        return $getsiteRootPath;
        // KEWL_SITEROOT_PATH;
    }

    /**
     * The default site root path as string
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setsiteRootPath($value) {
        return $this->setItem('KEWL_SITEROOT_PATH', $value);
    }

    /**
     * Whether to allow users to register themselves
     *
     * @access public
     * @param  value  to be changed
     * @return TRUE   or FALSE
     */
    public function setallowSelfRegister($value) {
        return $this->setItem('KEWL_ALLOW_SELFREGISTER', $value);
    }

    /**
     * Whether to allow users to register themselves
     *
     * @return TRUE or FALSE
     */
    public function getallowSelfRegister() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_ALLOW_SELFREGISTER" );
        //finally unearth whats inside
        $getallowSelfRegister = $SettingsDirective->getContent ();
        return $getallowSelfRegister;
        // KEWL_ALLOW_SELFREGISTER;
    }

    /**
     * Returns name of post-login module
     *
     * @access public
     * @return name   of post-login module
     */
    public function getdefaultModuleName($moduleType="POSTLOGIN") {
        $moduleType = strtoupper($moduleType);
        // Force it to default to postlogin
        if ($moduleType !== "POSTLOGIN" && $moduleType !== "PRELOGIN") {
            $moduleType="POSTLOGIN";
        }
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem (
          "directive", "KEWL_" . $moduleType . "_MODULE" 
        );
        //finally unearth whats inside
        $getdefaultModuleName = $SettingsDirective->getContent ();
        return $getdefaultModuleName;
        // KEWL_POSTLOGIN_MODULE;
    }

    /**
     * Method to set the default module name
     *
     * @access public
     * @param  value  to be changed
     * @return TRUE   or FALSE
     */
    public function setdefaultModuleName($value) {
        return $this->setItem('KEWL_POSTLOGIN_MODULE', $value);
    }

    /**
     * Method to get Value of LDAP
     *
     * @access  PUBLIC
     * @Returns whether LDAP functionality should be used
     */
    public function getuseLDAP() {
        $this->_root = $this->readConfig ( '', 'XML' );
        if (function_exists ( "ldap_connect" )) {
            //Lets get the parent node section first
            $Settings = & $this->_root->getItem ( "section", "Settings" );
            //Now onto the directive node
            $SettingsDirective = & $Settings->getItem ( "directive", "LDAP_USED" );
            //finally unearth whats inside
            $getuseLDAP = $SettingsDirective->getContent ();
            if ($getuseLDAP == "FALSE") {
                $getuseLDAP = FALSE;
            }
            return $getuseLDAP;
        } else {
            return FALSE;
        }
    }

    /**
     * Method to set LDAP as used
     *
     * @access public
     * @param  value  to be changed
     * @return TRUE   or FALSE
     */
    public function setuseLDAP($value) {
        return $this->setItem('LDAP_USED', $value);
    }

    /**
     * Check if system is an alumni systemtype
     *
     *
     * @return boolean Return description (if any) ...
     * @access public
     */
    public function isAlumni() {
        //I dont know what this does, so am just setting it to false now
        return FALSE;
    }

    /**
     * Returns the country 2-letter code
     *
     * Defaults to 'ZA'
     *
     * @access  public
     * @returns string $code
     */
    public function getCountry() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SERVERLOCATION" );
        //finally unearth whats inside
        $getCountry = $SettingsDirective->getContent ();

        if ($getCountry == NULL) {
            $getCountry = 'ZA';

        }
        return $getCountry;
    }

    /**
     * ---------------- FILE SYSTEM PROPERTIES -----------*
     */

    /**
     * Returns the base path for all user files
     *
     * @access public
     * @return base   path for user files
     */
    public function getcontentBasePath() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_CONTENT_BASEPATH" );
        //finally unearth whats inside
        $getcontentBasePath = $SettingsDirective->getContent ();

        return $getcontentBasePath;
        // KEWL_CONTENT_BASEPATH;
    }

    /**
     * Returns the path for content files
     *
     * @access public
     */
    public function getcontentPath() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_CONTENT_PATH" );
        //finally unearth whats inside
        $getcontentPath = $SettingsDirective->getContent ();

        return $getcontentPath;
    }

    /**
     * Set the path for content files
     *
     * @access public
     * @param  value  to be changed
     * @return TRUE   or FALSE
     */
    public function setcontentPath($value) {
        return $this->setItem('KEWL_CONTENT_PATH', $value);
    }

    /**
     * Returns the root path for content files
     *
     * @access public
     * @return content root path
     */
    public function getcontentRoot() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_CONTENT_PATH" );
        //finally unearth whats inside
        $getcontentRoot = $SettingsDirective->getContent ();

        return $getcontentRoot;
        // KEWL_CONTENT_PATH;
    }

    /**
     * Set the root path for content files
     *
     * @access public
     * @param  value  to be changed
     * @return TRUE   or FALSE
     */
    public function setcontentRoot($value) {
        return $this->setItem('KEWL_CONTENT_PATH', $value);
    }

    /**
     * Gets error reporting Setting
     *
     * @access public
     * @return geterror_reporting setting
     */
    public function geterror_reporting() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_ERROR_REPORTING" );
        //finally unearth whats inside
        $geterror_reporting = $SettingsDirective->getContent ();

        return $geterror_reporting;

    }

    /**
     * Gets flag to disable XML
     *
     * @access public
     * @returns string
     */
    public function getNoXML() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "NO_XML" );
        //var_dump($SettingsDirective);
        if ($SettingsDirective == FALSE) {
            // Little hack here to get around a strange quirk with the config
            if (! defined ( 'NO_XML_FLAG' )) {
                $newsettings = array ("NO_XML" => "1" );
                $this->appendToConfig ( $newsettings );
            } else {
                define ( 'NO_XML_FLAG', 1 );
            }
            return FALSE;
        }
        //finally unearth whats inside
        $noXML = $SettingsDirective->getContent ();

        return $noXML;

    }

    /**
     * Gets enable memcache Setting
     *
     * @access public
     * @return getenable adm setting
     */
    public function getenable_dbabs() {
        $this->_root = $this->readConfig ( '', 'XML' );

        //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "DATABASE_ABSTRACTION" );
        //var_dump($SettingsDirective);
        if($SettingsDirective == FALSE)
        {
                $newsettings = array("DATABASE_ABSTRACTION" => "MDB2");
                $this->appendToConfig($newsettings);
                return "MDB2";
        }
        //finally unearth whats inside
        $databaseAbstraction = $SettingsDirective->getContent();

        return $databaseAbstraction;
    }

    /**
     * Gets enable APC Setting
     *
     * @access public
     * @return getenable APC setting
     */
    public function getenable_apc() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "ENABLE_APC" );
        //var_dump($SettingsDirective);
        if ($SettingsDirective == FALSE) {
            $newsettings = array ("ENABLE_APC" => "FALSE" );
            $this->appendToConfig ( $newsettings );
            return FALSE;
        }
        //finally unearth whats inside
        $getenable_apc = $SettingsDirective->getContent ();

        return $getenable_apc;
    }

    /**
     * Gets cache TTL Setting
     *
     * @access public
     * @return getenable adm setting
     */
    public function getcache_ttl() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "CACHE_TTL" );
        //var_dump($SettingsDirective);
        if ($SettingsDirective == FALSE) {
            $newsettings = array ("CACHE_TTL" => "3600" );
            $this->appendToConfig ( $newsettings );
            return FALSE;
        }
        //finally unearth whats inside
        $cache_ttl = $SettingsDirective->getContent ();

        return $cache_ttl;
    }
    
    /**
     * Gets language cache Setting
     *
     * @access public
     * @return getenable langcache setting
     */
    public function getlangcache() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "LANGCACHE" );
        //var_dump($SettingsDirective);
        if ($SettingsDirective == FALSE) {
            $newsettings = array ("LANGCACHE" => "TRUE" );
            $this->appendToConfig ( $newsettings );
            return TRUE;
        }
        //finally unearth whats inside
        $langcache = $SettingsDirective->getContent ();

        return $langcache;
    }

    /**
     * Gets enable proxy Setting
     *
     * @access public
     * @return getenable adm setting
     */
    public function getProxy() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_PROXY" );
        //var_dump($SettingsDirective);
        if ($SettingsDirective == FALSE) {
            $newsettings = array ("KEWL_PROXY" => "NULL" );
            $this->appendToConfig ( $newsettings );
            return NULL;
        }
        //finally unearth whats inside
        $getProxy = $SettingsDirective->getContent ();

        return $getProxy;
    }

    /**
     * Method to return the modulepath setting from the config file
     *
     * @param  void
     * @return string
     */
    public function getModulePath() {
        $this->_root = $this->readConfig ( '', 'XML' );

        try {
            //Lets get the parent node section first
            $Settings = & $this->_root->getItem ( "section", "Settings" );
            //Now onto the directive node
            $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_MODULE_PATH" );
            if (! ($SettingsDirective)) {
                throw new Exception ( 'Module path is missing' );
            }
            //finally unearth whats inside
            $modulePath = $SettingsDirective->getContent ();
        } catch ( Exception $e ) {
            throw new customException ( $e->getMessage () );
            exit ();
        }

        return $modulePath;
    }

    /**
     * Method to return the moduleURI setting from the config file
     *
     * @param  void
     * @return string
     */
    public function getModuleURI() {
        $this->_root = $this->readConfig ( '', 'XML' );

        try {
            //Lets get the parent node section first
            $Settings = & $this->_root->getItem ( "section", "Settings" );
            //Now onto the directive node
            $SettingsDirective = & $Settings->getItem ( "directive", "MODULE_URI" );
            if (! ($SettingsDirective)) {
                throw new Exception ( 'Module URI is missing' );
            }
            //finally unearth whats inside
            $moduleURI = $SettingsDirective->getContent ();
        } catch ( Exception $e ) {
            throw new customException ( $e->getMessage () );
            exit ();
        }

        return $moduleURI;
    }

    /**
     * Set error reporting Settings
     * @access public
     * @param  value  to be changed
     * @return TRUE   or FALSE
     */
    public function seterror_reporting($value) {
        return $this->setItem('KEWL_ERROR_REPORTING', $value);
    }

    /**
     * Set dsn settings
     * @access public
     * @param  $value -this is the value we want to inset
     * @return $bool  - TRUE /FALSE
     */
    public function setDsn($value) {
        return $this->setItem('KEWL_DB_DSN', $value);
    }

    /**
     * Get dsn settings
     *
     * @access public
     * @return $Dsn
     */
    public function getDsn() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_DB_DSN" );
        //finally unearth whats inside
        $Dsn = KEWL_DB_DSN; //$SettingsDirective->getContent();


        return $Dsn;
    }

    /**
     * Get Second dsn settings
     *
     * @access public
     * @return $Dsn2
     */
    public function getDsn2() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_DB2_DSN" );
        //finally unearth whats inside
        $Dsn2 = $SettingsDirective->getContent ();

        return $Dsn2;

    }

    /**
     * Set dsn2 settings
     *
     * @access public
     * @param  $value -this is the value we want to inset
     * @return $bool  - TRUE /FALSE
     */
    public function setDsn2($value) {
        return $this->setItem('KEWL_DB2_DSN', $value);
    }

    /**
     * Return the stable site name used for cache and temporary-file names.
     */
    public function serverName() {
        $this->_root = $this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SERVERNAME" );
        //finally unearth whats inside
        $serverName = $SettingsDirective->getContent ();
        if ($serverName != null) {
            return $serverName;
        } else {
            return 'default';
        }
    }

    /**
     * Whether to enable logging or not
     *
     * @access public
     * @return string true or false
     */
    public function getenable_logging() {
        $getlogging = $this->getItem('KEWL_ENABLE_LOGGING');

        return $getlogging === FALSE ? FALSE : $getlogging;
    }

    /**
     * Whether to show the search box or not
     *
     * @access public
     * @return string true or false
     */
    public function getenable_searchBox() {
        $getsearch = $this->getItem ( "SHOW_SEARCH_BOX" );

        return $getsearch;
    }

    /**
     * The error callback function, defers to configured error handler
     *
     * @param  string $error
     * @return void
     * @access public
     */
    public function errorCallback($exception) {
        throw new customException ( $exception );
        exit ();
    }

    /**
     * Method to parse the DSN
     *
     * @access public
     * @param string $dsn
     * @return void
     */
    public function parseDSN($dsn) {
        $parsed = NULL;
        $arr = NULL;
        if (is_array ( $dsn )) {
            $dsn = array_merge ( $parsed, $dsn );
            return $dsn;
        }
        //find the protocol
        if (($pos = strpos ( $dsn, '://' )) !== false) {
            $str = substr ( $dsn, 0, $pos );
            $dsn = substr ( $dsn, $pos + 3 );
        } else {
            $str = $dsn;
            $dsn = null;
        }
        if (preg_match ( '|^(.+?)\((.*?)\)$|', $str, $arr )) {
            $parsed ['protocol'] = $arr [1];
            $parsed ['protocol'] = ! $arr [2] ? $arr [1] : $arr [2];
        } else {
            $parsed ['protocol'] = $str;
            $parsed ['protocol'] = $str;
        }

        if (! (is_countable($dsn) ? count($dsn) : 0)) {
            return $parsed;
        }
        // Get (if found): username and password
        if (($at = strrpos ( $dsn, '@' )) !== false) {
            $str = substr ( $dsn, 0, $at );
            $dsn = substr ( $dsn, $at + 1 );
            if (($pos = strpos ( $str, ':' )) !== false) {
                $parsed ['user'] = rawurldecode ( substr ( $str, 0, $pos ) );
                $parsed ['pass'] = rawurldecode ( substr ( $str, $pos + 1 ) );
            } else {
                $parsed ['user'] = rawurldecode ( $str );
            }
        }
        //server
        if (($col = strrpos ( $dsn, ':' )) !== false) {
            $strcol = substr ( $dsn, 0, $col );
            $dsn = substr ( $dsn, $col + 1 );
            if (($pos = strpos ( $strcol, '/' )) !== false) {
                $parsed ['server'] = rawurldecode ( substr ( $strcol, 0, $pos ) );
            } else {
                $parsed ['server'] = rawurldecode ( $strcol );
            }
        }
        //now we are left with the port and mailbox so we can just explode the string and clobber the arrays together
        $pm = explode ( "/", $dsn );
        $parsed ['port'] = $pm [0];
        $parsed ['mailbox'] = $pm [1];
        $dsn = NULL;

        return $parsed;
    }

    /**
     * Function to determine if a property exist in the config file or not, true if exist/false if doesn't exist - in a config file
     * @param  string $propertyName is the property name eg SHOW_SEARCH_BOX
     * @author Emmanuel Natalis
     */
      public function isPropertyExist($propertyName)
      {

        if($this->getItem($propertyName)=="")
        {
           return 'FALSE';
        } else
        {
           return 'TRUE';
        }
      }

      /**
     * Function to get propaerty value returns false if doesn't exist in a config file
     * @param  string $propertyName is the property name eg SHOW_SEARCH_BOX
     * @author Emmanuel Natalis
     */
      public function getPropertyValue($propertyName)
      {
         return $this->getItem($propertyName);
      }

    /**
     * Destructor
     */
    public function __destruct() {

    }
}

?>
