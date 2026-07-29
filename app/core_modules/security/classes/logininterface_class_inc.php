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
    public function renderLoginBox($module = NULL) {
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
            $objIcon = $this->getObject('geticon', 'htmlelements');

            // prepare the link for the oAuth providers 
            $box = $this->oauthDisp();
            $fb = $this->fbButton(); //fbConnect();
            require_once dirname(__FILE__)
                . '/nativeauth/csrftokenservice.php';
            $nativeCsrf = new CsrfTokenService($this);
            // Create the native, CSRF-protected login form.
            $objForm = new form('loginform', $formAction);
            $objForm->addToForm('<input type="hidden" '
                . 'name="native_auth_begin" value="'
                . htmlspecialchars($nativeCsrf->issue('native_auth_begin'),
                    ENT_QUOTES, 'UTF-8') . '">');
            $objFields = new fieldset();
            $objFields->setLegend(' ');

            //--Create an element for the username
            $objInput = new textinput('username', '', 'text', '15');
            $objInput->extra = 'maxlength="255" placeholder="' . $this->objLanguage->languageText('word_username', 'system') . '"';
            $objLabel = new label($this->objLanguage->languageText('word_username') . ': ', 'input_username');
            //Add validation for username
            $objForm->addRule('username', $this->objLanguage->languageText("mod_login_unrequired", 'security', 'Please enter a username. A username is required in order to login.'), 'required');

            //Add the username box to the form
            $this->objIcon->setIcon('user');
            $objFields->addContent($this->objIcon->show());
            $objFields->addContent($objInput->show() . '<br />');
            //$objForm->addToForm();
            //--- Create an element for the password
            $objInput = new textinput('password', '', 'password', '15');
            $objInput->extra = 'maxlength="255" placeholder="' . $this->objLanguage->languageText('word_password', 'system') . '"';
            $objLabel = new label($this->objLanguage->languageText('word_password') . ': ', 'input_password');
            //Add the password box to the form
            //$objForm->addToForm();
            $this->objIcon->setIcon('key');
            $objFields->addContent($this->objIcon->show());
//                        $objFields->addContent($objLabel->show() . '<br />');
            $objFields->addContent($objInput->show());
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

            //--- Create a submit button
            $objButton = new button('submit', $this->objLanguage->languageText("word_login"));
            // Add the login icon
            $objButton->setIconClass("user");
            // Set the button type to submit
            $objButton->setToSubmit();
            // Add the button to the form
            $objFields->addContent($rem
                    . "<div class='loginbuttonwrap'>"
                    . $objButton->show() . '</div>');

            $notice = $this->objLanguage->languageText('mod_security_forgotpassword');
            $helpText = strtoupper($this->objLanguage->languageText('mod_security_helpmelogin', 'security', 'Yes, please help me to login'));
            $resetLink = new Link($this->uri(array('action' => 'needpassword'), 'security'));
            $resetLink->link = $helpText;
            // the help link
            $p = '<br />' . $notice . '<br/>' . $resetLink->show() . '<br />';
            $objFields->addContent($p);
            $objForm->addToForm($objFields->show());

            return $objForm->show() . $openidlink;
        } catch (Exception $e) {
            customException::cleanUp();
        }
    }

    public function oauthDisp() {
        // displays a set of oAuth providers
        $this->objDbSysconfig = $this->getObject('dbsysconfig', 'sysconfig');
        $show = $this->objDbSysconfig->getValue('show_twitter_auth', 'security');
        if (strtolower($show) == 'true') {
            $objIcon = $this->getObject('geticon', 'htmlelements');
            $objIcon->alt = "Sign in with Twitter";
            $this->consumer_key = $this->objDbSysconfig->getValue('twitter_consumer_key', 'security');
            $this->consumer_secret = $this->objDbSysconfig->getValue('twitter_consumer_secret', 'security');
            // create a link to log in with twitter
            $this->objEpiWrapper = $this->getObject('epiwrapper');
            $twitterObj = new EpiTwitter($this->consumer_key, $this->consumer_secret);
            $twiticon = $objIcon->getLinkedIcon($twitterObj->getAuthenticateUrl(), 'Sign-in-with-Twitter-lighter', 'png');
            $twitter = $twiticon;
            return $twitter . '<br />';
        } else {
            return NULL;
        }
    }

    public function doTwitterLogin() {

        // Get the currently valid auth package.
        $loadPath = $this->getResourcePath('twitteroauth2', 'security');
        $loadPath = str_replace('//', '/', $loadPath . '/twitteroauth.php');
        require_once $loadPath;
        
        // Get the required keys
        $this->objDbSysconfig = $this->getObject('dbsysconfig', 'sysconfig');
        $objAltConfig = $this->getObject('altconfig', 'config');
        $consumer_key = $this->objDbSysconfig->getValue('twitter_consumer_key', 'security');
        $consumer_secret = $this->objDbSysconfig->getValue('twitter_consumer_secret', 'security');
        $access_token = $this->objDbSysconfig->getValue('twitter_access_token', 'security');
        $token_secret = $this->objDbSysconfig->getValue('twitter_token_secret', 'security');
        try {
            
            /* Create a TwitterOauth object with consumer/user tokens. */
            $connection = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $token_secret);
            
            
            /* Create a TwitterOauth object with consumer/user tokens. */
            //KEEP CRUFT FOR NOW$connection = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $token_secret);
            $twitterInfo = $connection->get('account/verify_credentials');
            $userid = $twitterInfo->id;
            $username = $twitterInfo->screen_name;
            $fullname = $twitterInfo->name;
            $name = explode(" ", $fullname);
            $surname = '';
            if ((is_countable($name) ? count($name) : 0) > 1) {
                $surname = $name[1];
            }

            $me = array();

            $updateDetailsPhrase = $this->objLanguage->languageText("mod_security_updateprofile", 'security');
            $me['username'] = $userid;
            $me['email'] = $username;
            $me['first_name'] = $updateDetailsPhrase;
            $me['last_name'] = '';
            $me['gender'] = 'male';
            $me['id'] = mt_rand(1000, 9999) . date('ymd');

            return $this->openIdAuth($me);
        } catch (customException $e) {
            customException::cleanUp();
            exit;
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
     * This initiates openid connection. This method can also used for google/yahoo
     * signin, since those support openid.
     * @param type $auth_site 
     */
    function openIdConnect($auth_site) {
        //required includes
        //session_start();
        require_once "Auth/OpenID/Consumer.php";
        require_once "Auth/OpenID/FileStore.php";
        require_once "Auth/OpenID/SReg.php";
        require_once "Auth/OpenID/AX.php";


        $objAltConfig = $this->getObject('altconfig', 'config');
        $this->objDbSysconfig = $this->getObject('dbsysconfig', 'sysconfig');

        $modPath = $objAltConfig->getModulePath();
        $moduleUri = $objAltConfig->getModuleURI();
        $siteRoot = $objAltConfig->getSiteRoot();

        $DEFAULT_REDIRECT_ULR = $siteRoot . '?module=security&action=openidloginresult';
        $OPENID_AUTH_PAGE = $siteRoot . '?module=security&action=openidlogin';
        $OPENID_CALLBACK_PAGE = $siteRoot . '?module=security&action=openidlogin';

        $_SESSION['AUTH'] = false;

        switch ($auth_site) {

            case "google":
                $oid_identifier = 'https://www.google.com/accounts/o8/id';
                $_SESSION['auth_site'] = "Google";
                break;
            case "yahoo":
                $oid_identifier = 'https://yahoo.com';
                $_SESSION['auth_site'] = "Yahoo!";
                break;
            case "openid":
                if (!$_POST['openIDField']) {
                    header('Location: ' . $siteRoot . '?module=security&action=error&message=no_openidconnect&msg=' . $this->objLanguage->languageText("mod_security_invalidopenid", 'security'));
                } else {
                    $oid_identifier = $_POST['openIDField'];
                    $_SESSION['openid_user'] = $oid_identifier;
                    $_SESSION['auth_site'] = "OpenID";
                }
                break;
            default:
                header('Location: ' . $siteRoot . '?module=security&action=error&message=no_openidconnect&msg=' . $this->objLanguage->languageText("mod_security_invalidorunsupportedopenidprovider", 'security'));
        }

        //Here starts the authentication process
        // Create file storage area for OpenID data
        $openidPath = $this->objConfig->getcontentBasePath() . '/openid';
        $objMkDir = $this->getObject('mkdir', 'files');
        $objMkDir->mkdirs($openidPath);

        $store = new Auth_OpenID_FileStore($openidPath);

        // Create OpenID consumer

        $consumer = new Auth_OpenID_Consumer($store);

        // Create an authentication request to the OpenID provider
        $auth = $consumer->begin($oid_identifier);

        //checks for errors
        if (!$auth)
            header('Location: ' . $siteRoot . '?module=security&action=error&message=no_openidconnect&msg=' . $this->objLanguage->languageText("mod_security_errorconnectingtoprovider", 'security') . ': ' . $auth_provider);


        //configuring atributtes to be retrieved
        $attribute[] = Auth_OpenID_AX_AttrInfo::make('http://axschema.org/contact/email', 1, 1, 'email');

        // Create AX fetch request
        $ax = new Auth_OpenID_AX_FetchRequest;

        if (!$ax)
            header('Location: ' . $siteRoot . '?module=security&action=error&message=no_openidconnect&msg=' . $this->objLanguage->languageText("mod_security_errorconnectingtoprovider", 'security') . ': ' . $auth_provider);

        foreach ($attribute as $attr) {
            $ax->add($attr);
        }
        if (!$auth) {
            header('Location: ' . $siteRoot . '?module=security&action=error&message=no_openidconnect&msg=' . $this->objLanguage->languageText("mod_security_errorconnectingtoprovider", 'security') . ': ' . $auth_provider);
        }
        try {
            // Add AX fetch request to authentication request 
            $auth->addExtension($ax);
        } catch (Exception $ex) {
            header('Location: ' . $siteRoot . '?module=security&action=error&message=no_openidconnect&msg=' . $this->objLanguage->languageText("mod_security_errorconnectingtoprovider", 'security') . ': ' . $auth_provider);
        }
        // redirect to the OpenID provider's website for authentication
        $url = $auth->redirectURL($siteRoot, $OPENID_CALLBACK_PAGE);

        header('Location: ' . $url);
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

    /**
     * Using this option, we need to redirect to user profile after login
     * to update user details as we have no other way of getting this info
     * @return type
     */
    function openIdLogin() {
        // session_start();
        require_once "Auth/OpenID/Consumer.php";
        require_once "Auth/OpenID/FileStore.php";
        require_once "Auth/OpenID/SReg.php";
        require_once "Auth/OpenID/AX.php";

        $objAltConfig = $this->getObject('altconfig', 'config');
        $modPath = $objAltConfig->getModulePath();
        $moduleUri = $objAltConfig->getModuleURI();
        $siteRoot = $objAltConfig->getSiteRoot();
        $DEFAULT_REDIRECT_ULR = $siteRoot . '?module=security&action=openidloginresult';
        $OPENID_AUTH_PAGE = $siteRoot . '?module=security&action=openidlogin';
        $OPENID_CALLBACK_PAGE = $siteRoot . '?module=security&action=openidlogin';

        $objMkDir = $this->getObject('mkdir', 'files');

        $openidPath = $this->objConfig->getcontentBasePath() . '/openid';
        $objMkDir->mkdirs($openidPath);
        $store = new Auth_OpenID_FileStore($openidPath);

        $consumer = new Auth_OpenID_Consumer($store);
        $response = $consumer->complete($OPENID_CALLBACK_PAGE);

        if ($response->status == Auth_OpenID_SUCCESS) {
            $ax = new Auth_OpenID_AX_FetchResponse();
            $obj = $ax->fromSuccessResponse($response);
            $data = $obj->data;
            //this is a workaround because some openid providers, like myopenid.com,
            //does not fully support attribute exchange just yet. They're working on it.
            //I may update it in the future.

            if ($_SESSION['openid_user']) {
                $email = $_SESSION['openid_user'];
                $_SESSION['openid_user'] = null;
            }
            else
                $email = $data['http://axschema.org/contact/email']['0'];

            $_SESSION['user'] = $email;
            $_SESSION['AUTH'] = true;

            $me = array();

            $updateDetailsPhrase = $this->objLanguage->languageText("mod_security_updateprofile", 'security');
            $me['username'] = $email;
            $me['email'] = $email;
            $me['first_name'] = $updateDetailsPhrase;
            $me['last_name'] = '';
            $me['gender'] = 'male';
            $me['id'] = mt_rand(1000, 9999) . date('ymd');

            return $this->openIdAuth($me);
        } else {
            $_SESSION['AUTH'] = false;
            header('Location: ' . $siteRoot . '?module=security&action=error&message=no_openidconnect&msg=mod_security_problemlogginginwith');
        }
    }

    public function nextAction($action, $params = array(), $module = NULL) {
        // list($template, $_) = $this->_dispatch($action, $this->_moduleName);
        $params['action'] = $action;
        header('Location: ' . html_entity_decode($this->uri($params, $module)));
        return NULL;
    }

}

?>