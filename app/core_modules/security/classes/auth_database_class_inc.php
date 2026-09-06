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
    private $credentialProof = null;
    private $credentialFailureStatus = '';

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
        /*
         * Preserve the public boolean and _record contract while primary
         * credential verification is moved behind the transaction boundary.
         * This method must not establish identity or issue a remembered login.
         */
        $this->credentialProof = null;
        $proof = $this->verifyCredentials($username, $password);
        if ($proof === false) {
            return false;
        }

        $this->credentialProof = $proof;
        $this->_record = $proof['record'];
        return true;
    }

    /**
     * Return the last successful credential proof.
     *
     * @return array|null
     */
    public function getCredentialProof()
    {
        return $this->credentialProof;
    }

    public function getCredentialFailureStatus()
    {
        return $this->credentialFailureStatus;
    }

    /**
     * Verify primary credentials without creating authenticated state.
     *
     * @return array|false Canonical proof and legacy record, or false.
     */
    public function verifyCredentials($username, $password)
    {
        $this->credentialFailureStatus = '';
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
            . '/nativeauth/nativesessionservice.php';

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
            new NativeSessionService($this)
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
            $this->credentialFailureStatus = $result->getReason()
                ?: $result->getStatus();
            if ($result->getStatus()
                === CanonicalAuthenticationResult::STATUS_INACTIVE) {
                if (!defined('STATUS')) {
                    define('STATUS', 'inactive');
                }
            }

            return false;
        }

        $record = $database->fetchOne(
            'SELECT username, userid, title, firstname, surname, '
            . 'emailaddress, logins, isactive, accesslevel '
            . 'FROM tbl_users WHERE username = ?',
            array($result->getUsername())
        );

        if (!is_array($record)
            || empty($record['userid'])
            || (string) $record['userid'] !== (string) $result->getUserId()
            || (string) $record['isactive'] !== '1') {
            return false;
        }

        return array(
            'result' => $result,
            'record' => $record,
        );
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
