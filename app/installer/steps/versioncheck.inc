<?php

require_once INSTALL_DIR.'/step.inc';

require_once INSTALL_DIR.'/stepaction.inc';



/**

* VersionCheck

* *

* Checks all the required version numbers of the target system

*

*

* @author Prince Mbekwa

* @version $version$ - 1.0
* @todo Still to add XML_DTD check

* @package 5ive

* @subpackage installer

*/

class VersionCheck extends Step

{





    /**

    * Checks all version requirements

    *

    * @return void

    * @access public

    */

                /**
         * PHP 8 constructor wrapper for the legacy VersionCheck() constructor.
         */
        public function __construct()
        {
            call_user_func_array(
                [$this, 'VersionCheck'],
                func_get_args()
            );
        }

function VersionCheck()

    {
        //Register PHP check actions
        $this->actions[] = new PHPCheckAction(get_class($this));
        // Check the bundled runtime libraries.
        $this->actions[] = new RuntimeLibrariesCheckAction(get_class($this));

        $this->step_title = 'System Requirements.';

        $this->title_logo = 'version_check.gif';

        $this->auth_required = false;

        $this->conf_required = true;

        $this->help_topic = 'toc4';

        $this->step_help = "The Installer will check if you have all the required components installed to run Chisimba";



    }//end VersionCheck()

    public function process()
    {
        // Confirmation must not bypass requirements that have failed or changed.
        $this->errors = array();
        foreach ($this->actions as $action) {
            if (!$action->processAction()) {
                $this->success = false;
                $this->confirmed = false;
                $this->errors = array_merge($this->errors, $action->errors);
                return false;
            }
        }
        return parent::process();
    }






}//end class







/**

* PHPCheckAction

*

* Perform checks for the PHP version

*

*

*/

class PHPCheckAction extends StepAction

{

    /**

    * The minumum version that 5ive will install on.

    * @var $min_version as string

    */

    public $min_version = '8.2.0';



    /**

    * Holds an array about what requirements are needed.

    * array( setting => Array('version'=>vernum, 'available'=>boolean, 'message' => string) )

    * @var array

    */

    public $required_settings = Array(  'PHP' => Array('version'=>'8.2.0', 'available'=>true, 'message'=> ''),

                                        'GD'=>Array('version'=>'', 'available' => true, 'message'=> ''),

                                        'Zlib'=>Array('version'=>'', 'available' => true, 'message'=> ''),
                                        'zip'=>Array('version'=>'', 'available' => true, 'message'=> ''),

                                        'calendar'=>Array('version'=>'', 'available' => true, 'message'=> ''),

                                        'curl'=>Array('version'=>'7.10.5', 'available' => true, 'message'=> ''),

                                        'dom'=>Array('version'=>'', 'available' => true, 'message'=> ''),

                                        'libxml'=>Array('version'=>'2.6.0.', 'available' => true, 'message'=> ''),

                                        'mysqlclient'=>Array('version'=>'', 'available' => true, 'message'=> ''),

                                        'pgsql'=>Array('version'=>'', 'available' => true, 'message'=> ''),

                                        'imap'=>Array('version'=>'', 'available' => true, 'message'=> ''),

                                        'mbstring'=>Array('version'=>'', 'available' => true, 'message'=> ''),

                                        'SimpleXML'=>Array('version'=>'', 'available' => true, 'message'=> ''),

                                        'SOAP'=>Array('version'=>'', 'available' => true, 'message'=> ''),

                                        'xml'=>Array('version'=>'', 'available' => true, 'message'=> ''),

                                        'xmlreader'=>Array('version'=>'', 'available' => true, 'message'=> ''),

                                        'xmlwriter'=>Array('version'=>'', 'available' => true, 'message'=> ''),

                                    );






    /**

    * Figure out whether the current PHP version is sufficient

    *

    * @return boolean Whether this check succeeded or not.

    * @access public

    */

    function processAction()

    {

        $this->success = true;
        $this->errors = array();





        if(version_compare(phpversion(), $this->required_settings['PHP']['version']) < 0) {

            $this->success = false;

            $this->required_settings['PHP']['message'] =  '<img src="./extra/failed.png" border="0"  alt="Failed" title="Failed" />';

        } else {

            $this->required_settings['PHP']['message'] =  '<img src="./extra/ok.png" border="0" alt="OK" title="OK"  />';

        }



        // check if gd available

        if (!function_exists('gd_info')) {

            $this->required_settings['GD']['available'] = false;

            $this->required_settings['GD']['message'] =  '<img src="./extra/failed.png" border="0" alt="Failed" title="Failed"  />';

            $this->errors[] = '<span class="errors">You must have GD installed to continue.</span>';

            $this->success = false;

        } else {

            $this->required_settings['GD']['message'] =  '<img src="./extra/ok.png" border="0" alt="OK" title="OK"  />';

        }

        // check if mbstring is available

        if (!function_exists('mb_get_info')) {

            $this->required_settings['mbstring']['available'] = false;

            $this->required_settings['mbstring']['message'] =  '<img src="./extra/failed.png" border="0" alt="Failed" title="Failed"  />';

            $this->errors[] = '<span class="errors">You must have the mbstring extension installed to continue.</span>';

            $this->success = false;

        } else {

            $this->required_settings['mbstring']['message'] =  '<img src="./extra/ok.png" border="0" alt="OK" title="OK"  />';

        }



        // check zlib

        if (!function_exists('gzopen')) {

            $this->required_settings['Zlib']['available'] = false;

            $this->required_settings['Zlib']['message'] =  '<img src="./extra/failed.png" border="0" alt="Failed" title="Failed"  />';

            $this->success = false;

        } else {

            $this->required_settings['Zlib']['message'] =  '<img src="./extra/ok.png" border="0" alt="OK" title="OK"  />';

        }

        // check zip

        if (!class_exists('ZipArchive')) {

            $this->required_settings['zip']['available'] = false;

            $this->required_settings['zip']['message'] =  '<img src="./extra/failed.png" border="0" alt="Failed" title="Failed"  />';

            $this->success = false;

        } else {

            $this->required_settings['zip']['message'] =  '<img src="./extra/ok.png" border="0" alt="OK" title="OK"  />';

        }

        // check calendar

        if (!function_exists('cal_info')) {

            $this->required_settings['calendar']['available'] = false;

            $this->required_settings['calendar']['message'] =  '<img src="./extra/failed.png" border="0" alt="Failed" title="Failed"  />';

            $this->errors[] = '<span class="errors">calendar not found. You can proceed with the install, however some functionality will not be available to you</span>';

        } else {

            $this->required_settings['calendar']['message'] =  '<img src="./extra/ok.png" border="0" alt="OK" title="OK"  />';

        }



        // check curl

        if (!function_exists('curl_version')) {

            $this->required_settings['curl']['available'] = false;

            $this->required_settings['curl']['message'] =  '<img src="./extra/failed.png" border="0" alt="Failed" title="Failed"  />';

            $this->errors[] = '<span class="errors">curl not found. You can proceed with the install, however some functionality will not be available to you</span>';

        } else {

            $this->required_settings['curl']['message'] =  '<img src="./extra/ok.png" border="0" alt="OK" title="OK"  />';

        }

        // check dom

        if (!extension_loaded('dom')) {

            $this->required_settings['dom']['available'] = false;
            $this->success = false;

            $this->required_settings['dom']['message'] =  '<img src="./extra/failed.png" border="0" alt="Failed" title="Failed"  />';

            $this->errors[] = '<span class="errors">DOM is required to read and write configuration files.</span>';

        } else {

            $this->required_settings['dom']['message'] =  '<img src="./extra/ok.png" border="0" alt="OK" title="OK"  />';

        }



        // check libxml

        if (!function_exists('libxml_get_errors')) {

            $this->required_settings['libxml']['available'] = false;

            $this->required_settings['libxml']['message'] =  '<img src="./extra/failed.png" border="0" alt="Failed" title="Failed"  />';

            $this->errors[] = '<span class="errors">libxml not found. You can proceed with the install, however some functionality will not be available to you</span>';

        } else {

            $this->required_settings['libxml']['message'] =  '<img src="./extra/ok.png" border="0" alt="OK" title="OK"  />';

        }


        // check IMAP

        if (!extension_loaded('imap')) {

            $this->required_settings['imap']['available'] = false;

            $this->required_settings['imap']['message'] =  '<img src="./extra/failed.png" border="0" alt="Failed" title="Failed"  />';

            $this->errors[] = '<span class="errors">imap not found. You can proceed with the install, however some functionality will not be available to you</span>';

        } else {

            $this->required_settings['imap']['message'] =  '<img src="./extra/ok.png" border="0" alt="OK" title="OK"  />';

        }

        // check SimpleXML

        if (!extension_loaded('SimpleXML')) {
            $this->success = false;

            $this->required_settings['SimpleXML']['available'] = false;

            $this->required_settings['SimpleXML']['message'] =  '<img src="./extra/failed.png" border="0" alt="Failed" title="Failed"  />';

            $this->errors[] = '<span class="errors">SimpleXML is required to load installation data.</span>';

        } else {

            $this->required_settings['SimpleXML']['message'] =  '<img src="./extra/ok.png" border="0" alt="OK" title="OK"  />';

        }

        /* check SOAP

        if (!extension_loaded('SOAP')) {

            $this->required_settings['SOAP']['available'] = false;

            $this->required_settings['SOAP']['message'] =  '<img src="./extra/failed.png" border="0" alt="Failed" title="Failed"  />';

            $this->errors[] = '<span class="errors">SOAP not found. You can proceed with the install, however some functionality will not be available to you</span>';

        } else {

            $this->required_settings['SOAP']['message'] =  '<img src="./extra/ok.png" border="0" alt="OK" title="OK"  />';

        }
        */
        // check xml

        if (!extension_loaded('xml')) {

            $this->required_settings['xml']['available'] = false;

            $this->required_settings['xml']['message'] =  '<img src="./extra/failed.png" border="0" alt="Failed" title="Failed"  />';

            $this->errors[] = '<span class="errors">xml not found. You can proceed with the install, however some functionality will not be available to you</span>';

        } else {

            $this->required_settings['xml']['message'] =  '<img src="./extra/ok.png" border="0" alt="OK" title="OK"  />';

        }

        // check xmlreader

        if (!extension_loaded('xmlreader')) {

            $this->required_settings['xmlreader']['available'] = false;

            $this->required_settings['xmlreader']['message'] =  '<img src="./extra/failed.png" border="0" alt="Failed" title="Failed"  />';

            $this->errors[] = '<span class="errors">xmlreader not found. You can proceed with the install, however some functionality will not be available to you</span>';

        } else {

            $this->required_settings['xmlreader']['message'] =  '<img src="./extra/ok.png" border="0" alt="OK" title="OK"  />';

        }

        // check xmlwriter

        if (!extension_loaded('xmlwriter')) {

            $this->required_settings['xmlwriter']['available'] = false;

            $this->required_settings['xmlwriter']['message'] =  '<img src="./extra/failed.png" border="0" alt="Failed" title="Failed"  />';

            $this->errors[] = '<span class="errors">xmlwriter not found. You can proceed with the install, however some functionality will not be available to you</span>';

        } else {

            $this->required_settings['xmlwriter']['message'] =  '<img src="./extra/ok.png" border="0" alt="OK" title="OK"  />';

        }

        // New installations use the shared native PDO MySQL connection.
        $available = class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers(), true);
        $_SESSION['allowed_db'] = array(
            'PDO_Driver_mysql' => false,
            'PDO_Driver_pgsql' => false,
            'PDO_Driver_mysqli' => $available,
        );
        $this->required_settings['mysqlclient']['available'] = $available;
        $this->required_settings['mysqlclient']['version'] = $available ? 'PDO MySQL' : '';
        $this->required_settings['mysqlclient']['message'] = $available
            ? '<img src="./extra/ok.png" alt="OK" />'
            : '<img src="./extra/failed.png" alt="Failed" />';
        if (!$available) {
            $this->success = false;
            $this->errors[] = 'Install PHP PDO with the MySQL driver (pdo_mysql).';
        }



     return $this->success;



    }//end processAction()





    /**

    * Display the result of this check.

    *

    * @param

    * @return void

    * @access public

    */

    function paintAction()

    {

        $tpl = new Template(INSTALL_DIR.'/templates/versioncheck.tpl');



        $tpl->set('checking', 'PHP Modules');

        $tpl->set('step_name', $this->parent_step);

        $tpl->set('required', $this->required_settings);



        echo $tpl->fetch();



    }//end paintAction()





    /**

    * Show the confirmation of what passed and what failed.

    *

    * @return void

    * @access public

    */

    function confirmAction()

    {

        $tpl = new Template(INSTALL_DIR.'/templates/versioncheck.tpl');

        $tpl->set('step_name', $this->parent_step);

        $tpl->set('checking', 'PHP Modules');

        $tpl->set('required', $this->required_settings);



        echo $tpl->fetch();



    }//end confirmAction()





}//end class

/** Validate the packaged runtime, without a system PEAR registry or MDB2. */
class RuntimeLibrariesCheckAction extends StepAction
{
    public $required_packages = array();

    function processAction()
    {
        $this->errors = array();
        $this->required_packages = array();
        $root = rtrim((string) ($_SESSION['sys_root'] ?? dirname(INSTALL_DIR)), '/\\');
        // This is the same bundled directory used by the application bootstrap.
        // Never accept a posted library path or fall back to an unrelated system copy.
        unset($_SESSION['pear_path']);
        $files = array(
            'Native configuration' => 'core_modules/config/classes/configurationdocument.php',
            'Native translation' => 'core_modules/language/classes/nativetranslation.php',
            'Native XML values' => 'core_modules/utilities/classes/nativexmlvalue.php',
            'Native BBCode' => 'core_modules/utilities/classes/nativebbcode.php',
            'Mail library' => 'core_modules/mail/vendor/phpmailer/src/PHPMailer.php',
            'Mail SMTP transport' => 'core_modules/mail/vendor/phpmailer/src/SMTP.php',
            'Mail exceptions' => 'core_modules/mail/vendor/phpmailer/src/Exception.php',
            'Media metadata' => 'core_modules/files/resources/getid3/getid3.php',
        );
        foreach ($files as $label => $file) {
            $available = is_file($root . '/' . $file)
                && is_readable($root . '/' . $file);
            $message = $available ? 'Available' : 'Missing bundled file: ' . $file;
            $this->required_packages[$label] = array(
                'version' => 'Bundled', 'available' => $available, 'message' => $message,
            );
            if (!$available) {
                $this->errors[] = $message . '. Restore the complete application libraries before continuing.';
            }
        }
        $this->success = !$this->errors;
        return $this->success;
    }

    function paintAction()
    {
        $tpl = new Template(INSTALL_DIR . '/templates/versioncheck.tpl');
        $tpl->set('step_name', $this->parent_step);
        $tpl->set('required', $this->required_packages);
        echo $tpl->fetch();
        echo '<p>New installations use native PDO MySQL and bundled application libraries. '
            . 'No system PEAR installation or PEAR registry is required.</p>';
    }

    function confirmAction()
    {
        $this->paintAction();
    }
}
