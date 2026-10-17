<?php

/**
 * Logininterface class
 *
 * Class to render login box, register links, and do other pre login duties
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
 *
 * @category  Chisimba
 * @package   security
 * @author FSIU
 * @copyright 2004-2007, University of the Western Cape & AVOIR Project
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @link      http://avoir.uwc.ac.za
 */
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check

/**
 *
 * Class to render login box, register links, and do other pre login duties
 *
 * @version $Id$
 * @copyright 2003
 * */
class loginInterface extends ChisimbaObject {

    // Services populated during initialisation and rendering.
    public $objSysConfig;
    public $objHelp;
    public $objUser;
    public $objDbSysconfig;
    public $objMods;


    /**
     * @var object $objLanguage String to hold the language object
     */
    private $objLanguage;

    /**
     * Config object to check system config variables
     *
     * @var object
     */
    public $objConfig;

    /**
     * Icons object
     * 
     * @access public
     * @var object
     */
    var $objIcon;

    public function init() {
        try {
            // Create an instance of the language object
            $this->objLanguage = $this->getObject('language', 'language');
            //initialise config obect
            $this->objConfig = $this->getObject('altconfig', 'config');
            $this->objSysConfig = $this->getObject('dbsysconfig', 'sysconfig');
            $this->objHelp = $this->getObject('help', 'help');
            $this->objUser = $this->getObject('user', 'security');
            $this->objIcon = $this->getObject('geticon', 'htmlelements');
        } catch (Exception $e) {
            customException::cleanUp();
        }
    }

    /**
     * Method to render a login box
     * @returns string
     */
    public function renderLoginBox($module = NULL, array $state = array()) {
        try {
            //set the action for the login form
            if ($module != NULL) {
                $formAction = $this->objEngine->uri(array('action' => 'login', 'mod' => $module), 'security');
            } else {
                $formAction = $this->objEngine->uri(array('action' => 'login'), 'security');
            }
            $useHTTPS = $this->objSysConfig->getValue('MOD_SECURITY_HTTPS', 'security');
            if ($useHTTPS == '1') {
                $formAction = str_replace("http:", "https:", $formAction);
            }
            //Load up the various HTML classes
            $this->loadClass('button', 'htmlelements');
            $this->loadClass('textinput', 'htmlelements');
            $this->loadClass('checkbox', 'htmlelements');
            $this->loadClass('link', 'htmlelements');
            $this->loadClass('label', 'htmlelements');
            $this->loadClass('fieldset', 'htmlelements');
            $objBox = $this->newObject('alertbox', 'htmlelements');
            $authIcons = $this->getObject('iconservice', 'ui');

            // Use the canonical web composition so this embedded form receives
            // the same CSRF and signed abuse evidence as the native login page.
            $nativeAuthStack = $this->getObject(
                'nativeauthwebcomposition',
                'security'
            )->build();
            $nativeCsrf = $nativeAuthStack['csrf'];
            $nativeAbuseEvidence = $nativeAuthStack['abuse']
                ->issueFormEvidence('native.login');
            $escapeAuthValue = static function ($value) {
                return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            };
            $username = isset($state['username'])
                && !is_array($state['username'])
                && !is_object($state['username'])
                ? substr(trim((string) $state['username']), 0, 255)
                : '';
            $allowedFailureKeys = array(
                'mod_security_authenticationfailed',
            );
            $failure = '';
            if (isset($state['message_key'])
                && in_array($state['message_key'], $allowedFailureKeys, true)) {
                $failure = '<p class="auth-login-error" role="alert">'
                    . $escapeAuthValue($this->objLanguage->languageText(
                        $state['message_key'],
                        'security'
                    )) . '</p>';
            }
            // Create the native, CSRF-protected login form.
            $objForm = new form('loginform', $formAction);
            if ($failure !== '') {
                $objForm->addToForm($failure);
            }
            $objForm->addToForm('<input type="hidden" '
                . 'name="native_auth_begin" value="'
                . htmlspecialchars($nativeCsrf->issue('native_auth_begin'),
                    ENT_QUOTES, 'UTF-8') . '">');
            $currentRequest = isset($state['return_to'])
                && !is_array($state['return_to'])
                && !is_object($state['return_to'])
                && trim((string) $state['return_to']) !== ''
                ? (string) $state['return_to']
                : (isset($_GET['return_to'])&&!is_array($_GET['return_to'])&&!is_object($_GET['return_to'])
                ? (string)$_GET['return_to']
                : (isset($_SERVER['REQUEST_URI'])
                ? (string) $_SERVER['REQUEST_URI']
                : ''));
            $objForm->addToForm('<input type="hidden" name="return_to" value="'
                . $escapeAuthValue($currentRequest) . '">');
            $objForm->addToForm('<input type="hidden" name="abuse_issued_at" value="'
                . $escapeAuthValue($nativeAbuseEvidence['issued_at'] ?? '') . '">');
            $objForm->addToForm('<input type="hidden" name="abuse_nonce" value="'
                . $escapeAuthValue($nativeAbuseEvidence['nonce'] ?? '') . '">');
            $objForm->addToForm('<input type="hidden" name="abuse_signature" value="'
                . $escapeAuthValue($nativeAbuseEvidence['signature'] ?? '') . '">');
            $objForm->addToForm('<div hidden aria-hidden="true">'
                . '<label for="block-login-website">Website</label>'
                . '<input id="block-login-website" name="website" type="text" '
                . 'tabindex="-1" autocomplete="off"></div>');
            $objFields = new fieldset();
            $objFields->setLegend(' ');

            //--Create an element for the username
            $objInput = new textinput('username', $username, 'text', '15');
            $fieldLabel = $escapeAuthValue($this->objLanguage->languageText('word_username', 'system'));
            $objInput->extra = 'maxlength="255" autocomplete="username" aria-label="' . $fieldLabel . '" placeholder="' . $fieldLabel . '"';
            $objLabel = new label($this->objLanguage->languageText('word_username') . ': ', 'input_username');
            //Add validation for username
            $objForm->addRule('username', $this->objLanguage->languageText("mod_login_unrequired", 'security', 'Please enter a username. A username is required in order to login.'), 'required');

            //Add the username box to the form
            $objFields->addContent('<div class="auth-field">'
                . $authIcons->render('user', array('decorative' => true))
                . $objInput->show() . '</div>');
            //$objForm->addToForm();
            //--- Create an element for the password
            $objInput = new textinput('password', '', 'password', '15');
            $fieldLabel = $escapeAuthValue($this->objLanguage->languageText('word_password', 'system'));
            $objInput->extra = 'maxlength="255" autocomplete="current-password" aria-label="' . $fieldLabel . '" placeholder="' . $fieldLabel . '"';
            $objLabel = new label($this->objLanguage->languageText('word_password') . ': ', 'input_password');
            //Add the password box to the form
            //$objForm->addToForm();
            $objFields->addContent('<div class="auth-field">'
                . $authIcons->render('key-round', array('decorative' => true))
                . $objInput->show() . '</div>');
            //--- Create an element for the network login radio
            $objElement = new checkbox("useLdap");
            $objElement->setCSS("transparentbgnb");
            $objElement->label = $this->objLanguage->languageText("phrase_networkid") . ' ';
            $ldap = '';
            $objSysConfig = $this->getObject('dbsysconfig', 'sysconfig');
            $showLDAPCheckBox = $objSysConfig->getValue('show_ldap_checkbox', 'security');

            if ($this->objConfig->getuseLDAP() && $showLDAPCheckBox == 'true') {
                $ldap .= $objElement->label . ' ' . $objElement->show();
            }
            //--- Create an element for the remember me checkbox
            $objRElement = new checkbox("remember");
            $objRElement->setCSS("transparentbgnb noborder");
            $objRElement->label = $this->objLanguage->languageText("phrase_rememberme", "security");
            $rem = $objRElement->show() . "<br />";

            // Render a semantic button because the legacy button helper escapes
            // rich content supplied as its value.
            $loginLabel = htmlspecialchars(
                $this->objLanguage->languageText("word_login", "system"),
                ENT_QUOTES,
                'UTF-8'
            );
            $loginButton = '<button type="submit" name="submit" value="Login" '
                . 'class="auth-submit">'
                . $authIcons->render('log-in', array('decorative' => true))
                . '<span>' . $loginLabel . '</span></button>';
            // Add the button to the form
            $objFields->addContent($rem
                    . "<div class='loginbuttonwrap'>"
                    . $loginButton . '</div>');

            $resetLink = new Link($this->uri(
                array('action' => 'forgotpassword'),
                'registration-service'
            ));
            $resetLink->link = $this->objLanguage->languageText(
                'mod_security_forgotpassword',
                'security',
                'Forgot your password?'
            );
            $p = '<br />' . $resetLink->show() . '<br />';
            $objFields->addContent($p);
            $objForm->addToForm($objFields->show());

            return $objForm->show();
        } catch (Exception $e) {
            customException::cleanUp();
        }
    }

    public function fbButton() {
        $this->objMods = $this->getObject('modules', 'modulecatalogue');
        $this->objDbSysconfig = $this->getObject('dbsysconfig', 'sysconfig');
        $show = $this->objDbSysconfig->getValue('show_fbconnect_auth', 'security');
        if ($this->objMods->checkIfRegistered('facebookapps') && strtolower($show) == 'true') {
            include($this->getResourcePath('facebook.php', 'facebookapps'));
            $apikey = $this->objDbSysconfig->getValue('apikey', 'facebookapps');
            $secret = $this->objDbSysconfig->getValue('apisecret', 'facebookapps');
            $appId = $this->objDbSysconfig->getValue('apid', 'facebookapps');
            // Create our Application instance (replace this with your appId and secret).
            $facebook = new Facebook(array(
                'appId' => $appId,
                'secret' => $secret,
                'cookie' => true,
            ));

            $session = $facebook->getSession();
            $fbappid = $facebook->getAppId();
            $jsess = json_encode($session);
            $reloadurl = $this->uri(array('module' => '_default'));
            $onloginurl = $this->uri(array('module' => 'security', 'action' => 'fbconnect'));
            $fb = '<div id="fb-root"></div>';
            $fb .= '<script>
                         window.fbAsyncInit = function() {
                             FB.init({
                                 appId   : \'' . $fbappid . '\',
                                 session : ' . $jsess . ',
                                 status  : true, 
                                 cookie  : true, 
                                 xfbml   : true 
                             });

                             // whenever the user logs in, we refresh the page
                             FB.Event.subscribe(\'auth.login\', function() {
                                 window.location.(' . $reloadurl . ');
                             });
                         };

                         (function() {
                             var e = document.createElement(\'script\');
                             e.src = document.location.protocol + \'//connect.facebook.net/en_US/all.js\';
                             e.async = true;
                             document.getElementById(\'fb-root\').appendChild(e);
                         }());
                     </script>';
            $fb .= '<fb:login-button autologoutlink="false" perms="email,read_stream" onlogin="window.location = \'' . $onloginurl . '\'"></fb:login-button>';
            return $fb;
        }
    }

    public function fbConnect() {
        $this->objMods = $this->getObject('modules', 'modulecatalogue');
        $this->objDbSysconfig = $this->getObject('dbsysconfig', 'sysconfig');
        $show = $this->objDbSysconfig->getValue('show_fbconnect_auth', 'security');
        if ($this->objMods->checkIfRegistered('facebookapps') && strtolower($show) == 'true') {
            include($this->getResourcePath('facebook.php', 'facebookapps'));
            $apikey = $this->objDbSysconfig->getValue('apikey', 'facebookapps');
            $secret = $this->objDbSysconfig->getValue('apisecret', 'facebookapps');
            $appId = $this->objDbSysconfig->getValue('apid', 'facebookapps');
            // Create our Application instance (replace this with your appId and secret).
            $facebook = new Facebook(array(
                'appId' => $appId,
                'secret' => $secret,
                'cookie' => true,
            ));

            // We may or may not have this data based on a $_GET or $_COOKIE based session.
            //
             // If we get a session here, it means we found a correctly signed session using
            // the Application Secret only Facebook and the Application know. We dont know
            // if it is still valid until we make an API call using the session. A session
            // can become invalid if it has already expired (should not be getting the
            // session back in this case) or if the user logged out of Facebook.
            $session = $facebook->getSession();
            $me = NULL;
            // Session based API call.
            if ($session) {
                try {
                    $uid = $facebook->getUser();
                    $me = $facebook->api('/me');
                    if ($me) {
                        $this->fbAuth($me);
                    }
                } catch (FacebookApiException $e) {
                    log_debug($e);
                }
            }

            // login or logout url will be needed depending on current user state.
            if ($me) {
                $logoutUrl = $facebook->getLogoutUrl();
            } else {
                $loginUrl = $facebook->getLoginUrl(array('req_perms' => 'email,read_stream'));
            }
        } else {
            return NULL;
        }
    }

    /**
     * this function handles all the facebook login
     * @return string
     */
    function doFacebookLogin() {
        //  session_start();
        require_once "facebook.php";

        $this->objDbSysconfig = $this->getObject('dbsysconfig', 'sysconfig');

        $apiId = $this->objDbSysconfig->getValue('facebook_app_id', 'security');
        $secret = $this->objDbSysconfig->getValue('facebook_app_secret', 'security');


        $facebook = new Facebook(array(
            'appId' => $apiId,
            'secret' => $secret,
        ));


        $fbuser = $facebook->getUser();

        if (!$fbuser) {
            $loginUrl = $facebook->getLoginUrl();

            $result = array("action" => "fblogin", "data" => $loginUrl);
            return $result;
        } else {
            try {
                $fbme = $facebook->api('/me');
                //die("$fbme  is the thin");
                if ($fbme) {
                    //      die("yes fbne");

                    $me = array();
                    $me['username'] = $fbme['username'];
                    $me['email'] = $fbme['id'] . '@' . $fbme['username'] . 'com';
                    $me['first_name'] = $fbme['first_name'];
                    $me['last_name'] = $fbme['last_name'];
                    $sex = $fbme['gender'];
                    if ($sex == 'male') {
                        $sex = 'M';
                    } else {
                        $sex = 'F';
                    }
                    $me['gender'] = $sex;
                    $me['id'] = mt_rand(1000, 9999) . date('ymd');
                    return $this->openIdAuth($me, TRUE);
                } else {
                    //                     die("no fb me");
                    return $this->nextAction('error', array('message' => 'no_fbconnect'));
                }
            } catch (FacebookApiException $e) {
                error_log($e);
            }
        }
        //  die("not here no wat");
    }

    public function nextAction($action, $params = array(), $module = NULL) {
        // list($template, $_) = $this->_dispatch($action, $this->_moduleName);
        $params['action'] = $action;
        header('Location: ' . html_entity_decode($this->uri($params, $module)));
        return NULL;
    }

}

?>
