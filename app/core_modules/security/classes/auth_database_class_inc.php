<?php
/* -------------------- IFAUTH INTERFACE CLASS ----------------*/

/**
*
* Plugin authenticatoin class to authenticate a user via a database
*
* @author Derek Keats, James Scoble
* @category Chisimba
* @package security
* @copyright AVOIR
* @licence GNU/GPL
*
*/

$this->loadClass("abauth", "security");
$this->loadClass("ifauth", "security");

class auth_database extends abauth implements ifauth
{
    /**
    *
    * Init method. It sets up a connection to the users database table
    * and instantiates required objects.
    *
    */
    // AUTH_DATABASE_INIT_SIGNATURE_PHP82
    public function init($dataTable = null, $pearDb = null, $errorCallback = 'globalPearErrorCallback')
    {
        parent::init('tbl_users');
    }

    /**
    *
    * Method to authenticate the user via the database
    * @param string $username The username supplied in the login
    * @param string $password The password supplied in the login
    * @return TRUE|FALSE Boolean indication of success of login
    */
    public function authenticate($username, $password, $remember = true)
    {
        if ($this->nativeLoginEnabled()
            && $password !== '--twitter--'
            && $password !== '--') {
            return $this->authenticateNatively($username, $password);
        }
        require_once dirname(__FILE__)
            . '/nativeauth/nativeauthshadowtrace.php';

        NativeAuthShadowTrace::log(
            'auth_database.authenticate',
            'entered',
            array('username' => $username)
        );

        $login = $this->objLu->login($username, $password, $remember);

        NativeAuthShadowTrace::log(
            'auth_database.authenticate',
            'LiveUser login returned',
            array('result' => $login ? TRUE : FALSE)
        );
        if(!$login) {
            // check if user is inactive
            if($this->objLu->isInactive()) {
                throw new customException("User is inactive, please contact site admin");
            }
            else {
                return FALSE;
            }
        }

        //Retrieve the users data from the database
        $line=$this->getUserDataAsArray($username);

        NativeAuthShadowTrace::log(
            'auth_database.authenticate',
            'user record loaded',
            array('record_found' => is_array($line))
        );
        // set the line as a stdClass, serialize and store in session to lower db calls
        $user = new stdClass();
        // add the user info to the class
        $user->username = $line['username'];
        $user->userid = $line['userid'];
        $user->title = $line['title'];
        $user->firstname = $line['firstname'];
        $user->surname = $line['surname'];
        $user->pass = NULL;
        $user->creationdate = $line['creationdate'];
        $user->emailaddress = $line['emailaddress'];
        $user->logins = $line['logins'];
        $user->isactive = $line['isactive'];
        // serialize the object to preserve structure etc
        $user = serialize($user);
        // set it into session to be used elsewhere (objUser mainly)
        $this->setSession('userprincipal', $user);
        if ($line) {
            if ($line['isactive']=='0'){
                DEFINE('STATUS','inactive');
                return FALSE;
            }
            //LDAP will be handled in chain-of-command
            if ($line['pass']==sha1('--LDAP--')){
                return FALSE;
            } else {
                $password=sha1(trim($password));
                // if the login was successful
                if($this->objLu->isloggedIn() == TRUE) {
                //if ( strtolower($line['pass'])==strtolower($password) ) {
                    $this->_record = $line;
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    /**
     * Whether the reversible first native-login switch is enabled.
     *
     * Enable only in the local PHP 8.2 container with:
     * CHISIMBA_NATIVE_AUTH_LOGIN=1
     */
    private function nativeLoginEnabled()
    {
        $value = getenv('CHISIMBA_NATIVE_AUTH_LOGIN');

        return in_array(
            strtolower(trim((string) $value)),
            array('1', 'true', 'yes', 'on'),
            true
        );
    }

    /**
     * Verify local credentials through the native authentication stack while
     * leaving compatibility session establishment to authenticate/abauth.
     */
    private function authenticateNatively($username, $password)
    {
        require_once dirname(__FILE__)
            . '/nativeauth/mdb2nativedatabaseadapter.php';
        require_once dirname(__FILE__)
            . '/nativeauth/nativeuserrepository.php';
        require_once dirname(__FILE__)
            . '/nativeauth/nativepasswordverifier.php';
        require_once dirname(__FILE__)
            . '/nativeauth/localpasswordprovider.php';
        require_once dirname(__FILE__)
            . '/nativeauth/authenticationproviderregistry.php';
        require_once dirname(__FILE__)
            . '/nativeauth/nativeauthenticationservice.php';
        require_once dirname(__FILE__)
            . '/nativeauth/legacyauthsessionbridge.php';

        $database = new Mdb2NativeDatabaseAdapter(
            $this->objEngine->getDbObj()
        );
        $users = new NativeUserRepository($database);
        $provider = new LocalPasswordProvider(
            $users,
            new NativePasswordVerifier()
        );
        $service = new NativeAuthenticationService(
            new AuthenticationProviderRegistry(array($provider)),
            new LegacyAuthSessionBridge()
        );

        $result = $service->authenticate(
            LocalPasswordProvider::PROVIDER_ID,
            trim((string) $username),
            (string) $password,
            array(
                'ip' => isset($_SERVER['REMOTE_ADDR'])
                    ? $_SERVER['REMOTE_ADDR']
                    : null,
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT'])
                    ? $_SERVER['HTTP_USER_AGENT']
                    : null,
            )
        );

        if (!$result->isSuccess()) {
            if ($result->getStatus()
                === CanonicalAuthenticationResult::STATUS_INACTIVE) {
                if (!defined('STATUS')) {
                    define('STATUS', 'inactive');
                }
            }

            return false;
        }

        $record = $database->fetchOne(
            'SELECT username, userid, title, firstname, surname, pass, '
            . 'creationdate, emailaddress, logins, isactive, accesslevel '
            . 'FROM tbl_users WHERE username = ?',
            array($result->getUsername())
        );

        if (!is_array($record)
            || empty($record['userid'])
            || (string) $record['userid'] !== (string) $result->getUserId()
            || (string) $record['isactive'] !== '1') {
            return false;
        }

        $this->_record = $record;
        $this->storeNativeCompatibilityPrincipal($record);

        return true;
    }

    /**
     * Preserve the userprincipal contract historically populated by
     * auth_database::authenticate().
     */
    private function storeNativeCompatibilityPrincipal(array $record)
    {
        $user = new stdClass();
        $user->username = $record['username'];
        $user->userid = $record['userid'];
        $user->title = $record['title'];
        $user->firstname = $record['firstname'];
        $user->surname = $record['surname'];
        $user->pass = null;
        $user->creationdate = $record['creationdate'];
        $user->emailaddress = $record['emailaddress'];
        $user->logins = $record['logins'];
        $user->isactive = $record['isactive'];

        $this->setSession('userprincipal', serialize($user));
    }

    /**
    * Look up user's data in the database.
    * @param string $username
    * @return array on success, FALSE on failure.
    */
    public function getUserDataAsArray($username)
    {
        /*$array = array();
        $array['username'] = $this->objLu->getProperty('handle');
        $array['userid'] = $this->objLu->getProperty('auth_user_id');
        $array['isactive'] = $this->objLu->getProperty('is_active');
        $array['emailaddress'] = $this->objLu->getProperty('email');
        $array['sex'] = $this->objLu->getProperty('sex');

        var_dump($array); die();*/

        $sql="SELECT
            tbl_users.username,
            tbl_users.userid,
            tbl_users.title,
            tbl_users.firstname,
            tbl_users.surname,
            tbl_users.pass,
            tbl_users.creationdate,
            tbl_users.emailaddress,
            tbl_users.logins,
            tbl_users.isactive,
            tbl_users.accesslevel
        FROM
            tbl_users
        WHERE
            (username = '".addslashes($username)."')";
        $array=$this->getArray($sql);
        //var_dump($array[0]); die();
        if (!empty($array))
        {
            return $array[0];
        } else {
            return FALSE;
        }
    }

    public function getUserDataAsArray2($username)
    {
        $sql="SELECT
            tbl_users.username,
            tbl_users.userid,
            tbl_users.title,
            tbl_users.firstname,
            tbl_users.surname,
            tbl_users.pass,
            tbl_users.creationdate,
            tbl_users.emailaddress,
            tbl_users.logins,
            tbl_users.isactive,
            tbl_users.accesslevel
        FROM
            tbl_users
        WHERE
            (username = '".addslashes($username)."')";
        $array=$this->getArray($sql);
        if (!empty($array))
        {
            return $array[0];
        } else {
            return FALSE;
        }
    }

}
?>