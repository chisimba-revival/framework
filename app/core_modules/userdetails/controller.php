<?php
/**
 * 
 * userdetails
 * 
 * Provides an interface for users to update their details, as well as modify their user parameters (forthcoming feature)
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
 * @package   userdetails
 * @author    Kevin Cyster kcyster@gmail.com
 * @copyright 2011 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   0.001
 * @link      http://www.chisimba.com
 *
 */
 
// security check - must be included in all scripts
if (!
/**
 * The $GLOBALS is an array used to control access to certain constants.
 * Here it is used to check if the file is opening in engine, if not it
 * stops the file from running.
 * 
 * @global entry point $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 *         
 */
$GLOBALS['kewl_entry_point_run'])
{
        die("You cannot view this page directly");
}
// end security check

/**
* 
* Controller class for Chisimba for the module userdetails
*
* @author Kevin Cyster
* @package userdetails
*
*/
class userdetails extends controller
{
    
    /**
    * 
    * @var string $objConfig String object property for holding the 
    * configuration object
    * @access public;
    * 
    */
    public $objConfig;
    public $objOps;
    public $objUser;
    public $objModules;
    
    /**
    * 
    * @var string $objLanguage String object property for holding the 
    * language object
    * @access public
    * 
    */
    public $objLanguage;
    /**
    *
    * @var string $objLog String object property for holding the 
    * logger object for logging user activity
    * @access public
    * 
    */
    public $objLog;

    /**
    * 
    * Intialiser for the userdetails controller
    * @access public
    * 
    */
    public function init()
    {
        $this->objOps = $this->getObject('userdetailsops', 'userdetails');
        $this->objUser = $this->getObject('user', 'security');
        $this->objModules = $this->getObject('modules', 'modulecatalogue');
        $this->objLanguage = $this->getObject('language', 'language');
        // Create the configuration object
        $this->objConfig = $this->getObject('config', 'config');
        $this->appendArrayVar('headerParams',
            $this->getJavaScriptFile('userdetails.js?v=2012',
            'userdetails'));
        //Get the activity logger class
        $this->objLog=$this->newObject('logactivity', 'logger');
        //Log this module call
        $this->objLog->log();
    }
    
    
    /**
     * 
     * The standard dispatch method for the userdetails module.
     * The dispatch method uses methods determined from the action 
     * parameter of the  querystring and executes the appropriate method, 
     * returning its appropriate template. This template contains the code 
     * which renders the module output.
     * 
     */
    public function dispatch()
    {
        //Get action from query string and set default to view
        if (!$this->objUser->isLoggedIn()) { http_response_code(403); return null; }
        $action=$this->getParam('action', 'main');
        $actions = $this->getObject('profileactions', 'userdetails');
        if (in_array($action, ['ajaxChangeImage','ajaxResetImage','ajaxChangeGrade'], true)) {
            $response = $actions->handle($action, ['csrf_token'=>$this->getParam('csrf_token'), 'imageselect'=>$this->getParam('imageselect'), 'new_name'=>$this->getParam('new_name')], strtoupper($_SERVER['REQUEST_METHOD'] ?? ''));
            http_response_code($response['status']);
            header('Content-Type: application/json; charset=UTF-8');
            header('Cache-Control: no-store');
            echo json_encode($response); exit;
        }
        if ($action === 'findSchools') {
            header('Content-Type: application/json; charset=UTF-8');
            header('Cache-Control: no-store');
            $term=$this->getParam('term','');
            echo json_encode($actions->schools(is_string($term)?$term:'')); exit;
        }
        if (in_array($action, ['resetimage','ajaxResetSession'], true)) { http_response_code(410); return null; }

        /*
        * Convert the action into a method (alternative to 
        * using case selections)
        */
        $method = $this->__getMethod($action);
        // Set the layout template to compatible one
        $this->setLayoutTemplate('layout_tpl.php');
        /*
        * Return the template determined by the method resulting 
        * from action
        */
        return $this->$method();
    }
    
    
    /*------------- BEGIN: Set of methods to replace case selection ------------*/

    /**
    * 
    * Method corresponding to the view action. It shows the default
    * dynamic canvas template, showing you how to create block based
    * view templates
    * @access private
    * 
    */
    /** Edit the authenticated user's public author biography. */
    private function __biography()
    {
        $service = $this->getObject('authorbiographyservice', 'userdetails');
        $value = $service->forUser($this->objUser->userId());
        $errors = array();
        $saved = (bool)$this->getSession('authorBiographySaved', false);
        $this->setSession('authorBiographySaved', false);
        $csrf = $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf'];
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST') {
            $token = $this->getParam('csrf_token', '');
            if (!is_string($token) || !$csrf->consume('author_biography', $token)) {
                $errors['form'] = 'bio_csrf';
                $text = $this->getParam('biography', '');
                $links = $this->getParam('links', array());
                $value = array('biography' => is_string($text) ? $text : '', 'links' => is_array($links) ? array_slice($links, 0, 5) : array());
            } else {
                $input = array('biography' => $this->getParam('biography', ''), 'links' => $this->getParam('links', array()));
                $result = $service->saveOwn($input);
                $errors = $result['errors'];
                $value = $result['value'];
                // Retain invalid link entries so validation never silently drops the user's work.
                if ($errors && is_array($input['links'])) $value['links'] = array_slice($input['links'], 0, 5);
                if (!$errors) {
                    $this->setSession('authorBiographySaved', true);
                    return $this->nextAction('biography');
                }
            }
        }
        $this->setVar('biographyValue', $value);
        $this->setVar('biographyErrors', $errors);
        $this->setVar('biographySaved', $saved);
        $this->setVar('biographyToken', $csrf->issue('author_biography'));
        return 'biography_tpl.php';
    }

    private function __main()
    {
        // All the action is in the blocks
        return "main_tpl.php";
    }   
    
    /**
     * 
     * Method corresponding to the validate action.
     * 
     * @access private
     * @return VOID
     */
    private function __validate()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            return 'main_tpl.php';
        }
        $check = $this->objModules->checkIfRegistered('schoolusers');
        
        $data = array();
        $data['title'] = $this->getParam('title');
        $data['first_name'] = $this->getParam('first_name');
        $data['last_name'] = $this->getParam('last_name');
        $data['gender'] = $this->getParam('gender');
        $data['country'] = $this->getParam('country');
        $data['email_address'] = $this->getParam('email_address');
        $data['contact_number'] = $this->getParam('contact_number');
        $data['password'] = $this->getParam('password');
        $data['confirm_password'] = $this->getParam('confirm_password');
        if ($check)
        {
            $data['school_id'] = $this->getParam('school_id');
            $data['school'] = $this->getParam('school','');
            $data['middle_name'] = $this->getParam('middle_name');
            $data['date_of_birth'] = $this->getParam('date_of_birth');
            $data['address'] = $this->getParam('address');
            $data['city'] = $this->getParam('city');
            $data['state'] = $this->getParam('state');
            $data['postal_code'] = $this->getParam('postal_code');
            $data['description'] = $this->getParam('description');
        }
        else
        {
            $data['staffnumber'] = $this->getParam('staffnumber');
        }
     
        foreach ($data as $key => $value) {
            if (in_array($key, ['date_of_birth','address'], true)) {
                $length=$key==='address'?2:3;
                $data[$key]=array_slice(array_pad(is_array($value)?array_map(static fn($part)=>is_scalar($part)?(string)$part:'',$value):[], $length, ''),0,$length);
            } else $data[$key]=is_scalar($value)?(string)$value:'';
        }
        $actions = $this->getObject('profileactions', 'userdetails');
        $token = $this->getParam('csrf_token');
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
            || !is_string($token) || !$actions->csrf()->consume('profile_details', $token)) {
            $data['password'] = $data['confirm_password'] = '';
            $this->setSession('errors', ['data'=>$data,'errors'=>['form'=>htmlspecialchars($this->objLanguage->languageText('mod_userdetails_profile_csrf','userdetails'),ENT_QUOTES,'UTF-8')]]);
            http_response_code(403);
            return 'main_tpl.php';
        }
        $errorsFound = $this->objOps->validate($data);
        if ($errorsFound == FALSE)
        {
            $this->setSession('errors', array());
            try {
                $this->objOps->save($data);
            } catch (Throwable $error) {
                $data['password'] = $data['confirm_password'] = '';
                $this->setSession('success', []);
                $this->setSession('errors', ['data'=>$data,'errors'=>['form'=>htmlspecialchars($this->objLanguage->languageText('mod_userdetails_profile_uncertain','userdetails'),ENT_QUOTES,'UTF-8')]]);
                http_response_code(422);
                return 'main_tpl.php';
            }
            return $this->nextAction('main');
        }
        else
        {
            return $this->nextAction('main');
        }        
    }
    
    /**
    * 
    * Method to return an error when the action is not a valid 
    * action method
    * 
    * @access private
    * @return string The dump template populated with the error message
    * 
    */
    private function __actionError()
    {
        return 'dump_tpl.php';
    }
    
    /**
    * 
    * Method to check if a given action is a valid method
    * of this class preceded by double underscore (__). If it __action 
    * is not a valid method it returns FALSE, if it is a valid method
    * of this class it returns TRUE.
    * 
    * @access private
    * @param string $action The action parameter passed byref
    * @return boolean TRUE|FALSE
    * 
    */
    function __validAction(& $action)
    {
        if (method_exists($this, "__".$action)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    /**
    * 
    * Method to convert the action parameter into the name of 
    * a method of this class.
    * 
    * @access private
    * @param string $action The action parameter passed byref
    * @return stromg the name of the method
    * 
    */
    function __getMethod(& $action)
    {
        if ($this->__validAction($action)) {
            return "__" . $action;
        } else {
            return "__actionError";
        }
    }
    
    /*------------- END: Set of methods to replace case selection ------------*/
    


    /**
    *
    * This is a method to determine if the user has to 
    * be logged in or not. Note that this is an example, 
    * and if you use it view will be visible to non-logged in 
    * users. Delete it if you do not want to allow annonymous access.
    * It overides that in the parent class
    *
    * @return boolean TRUE|FALSE
    *
    */
    public function requiresLogin($action = null)
    {
        $action=$this->getParam('action', 'NULL');
        switch ($action)
        {
            default:
                return TRUE;
                break;
        }
     }
}
?>
