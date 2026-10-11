<?php

require_once INSTALL_DIR.'/dbhandlers/dbhandlermanager.inc';
$mepath = $_SERVER["SCRIPT_FILENAME"];
$mepath = str_replace('installer/index.php', '', $mepath);
ini_set ( 'include_path', ini_get ( 'include_path' ) . PATH_SEPARATOR . $mepath.'lib/pear/');

/**

* DbHandler

*

* Handles installer db queries for installer

*

* @author Prince Mbekwa

* @version $version$ - 1.0

* @package 5ive

* @subpackage installer

*/

class DbHandler extends dbHandlermanager

{

	/**

	* The dsn to connect to

	* @var string

	*/

	public  $dsn;



	/**

	* The database connection to query over.

	* @var object

	*/

	private $schema;


    private $dbmanager;
	/**

	* Errors this class has had.

	* @var array

	*/

	public  $errors = Array();



	/**

	* Whether this handler has done its action successfully or not

	* @var boolean

	*/

	public $success = false;



	/**

	* Version of the db needed for 5ive

	* @var string

	*/

	public $minVersion;

	public $_globalObjDb;

	public $_globalObjDbManager;

	public function __construct()
    {
        $this->dsn=ChisimbaDatabaseDsn::parse($_SESSION['dsn']);
        // Database creation needs a server connection before the new DB exists.
        $serverDsn=$this->dsn; $serverDsn['database']='information_schema';
        $this->_globalObjDb=ChisimbaDatabaseConnection::fromLegacyDsn($serverDsn);
        $this->dbmanager=new dbHandlermanager($this->_globalObjDb);
        parent::__construct($this->_globalObjDb);
        if (!empty($this->dsn['database'])) {
            $databases=$this->dbmanager->listDatabases();
            if (in_array($this->dsn['database'],$databases,true)) $this->dbmanager->setDatabase($this->dsn['database']);
        }
    }
	/**
	 * Disconnect
	 *
	 */
	public function disconnect(){ $this->_globalObjDb=null; $this->dbmanager=null; }
	function _pearErrorCallback($error)
    {
        // TODO: note $error->getMessage() returns a shorter and friendlier but
        //       less informative message, for production should use getMessage
        //TODO: note 2: Appending the getUserinfo method from the PEAR
        //      error stack will give you the same detail as toString()
        //      but it will look decent and not confuse the crap out of users
        //      that being said, we should still go for just getMessage() in prod

         return $msg = $error->getMessage()."! Please check your database username and password. Remember that the user you are trying to connect as should have sufficient privileges to create databases and tables, as well as drop tables.";

        //echo $msg."<br><br>";
    }

	/**

	* Get a db handler class for the given dsn

	*

	* @param string $dsn The dsn to connect to.

	* @return object the db handler for the given database

	* @access public

	*/

	public function &getDbHandler($dsn)

	{
		try{

	   		return $this;
		}catch (Exception $e){

			return false;
		}

	}//end getDbHandler()
    //check for db connections
    public function checkConnection($dbname){try{$this->dbmanager->setDatabase($dbname);$this->dbmanager->listDbTables();return true;}catch(Throwable $e){return false;}}
	/**

	* Abstract emptyDatabase method

	*

	* @return boolean Whether the database could be emptied or not.

	* @access public

	*/

	public function emptyDatabase($db,$dsn){ throw new LogicException('Automatic destructive database replacement is disabled; use an explicit backup/restore workflow.'); }

	/**
	 * Create Tables for new 5ive installations
	 *
	 * @return TRUE / FALSE boolean
	 * @access Public
	 */
	public function createTables($tablename,$fields,$options){ return $this->dbmanager->createTable($tablename,$fields,$options); }

	/**
     * Method to create an index on the table
     *
     * @access public
     * @param string $tableName
     * @param string $keyname
     * @param array $index
     * @return bool true on success | False on failure
     */
    public function createTableIndex($tableName,$keyname,$index,$trunc=false){ return $this->dbmanager->createTableIndex($tableName,$keyname,$index,$trunc); }

	/**
     * create a new database
     *
     * @param string $db name of the database that should be created
     * @return bool true on success
     * @access public
     */
    public function createDb($db){$this->dbmanager->createDb($db);$this->dbmanager->setDatabase($db);return true;}


	/**

	* Get the number of tables in the database

	*

	* @return int The number of database tables.

	* @access public

	*/

	public function getNumberOfTables()

	{
		return $this->dbmanager->listDbTables();

	}//end getNumberOfTables()





	/**

	* Check the version is allowed

	*

	* @return string The version of the database

	* @access public

	*/

	public function checkVersion($dsn){return version_compare($this->dbmanager->getServerVersion(true),$this->minVersion??'5.0','>=');}//end version()





	/**

	* Set the dsn this db handler will use

	*

	* @param string $dsn the dsn to use.

	* @return void

	* @access public

	*/

	public function setDSN($dsn){$parsed=ChisimbaDatabaseDsn::parse($dsn);$connection=ChisimbaDatabaseConnection::fromLegacyDsn($parsed);$this->dsn=$parsed;$this->_globalObjDb=$connection;$this->dbmanager=new dbHandlermanager($connection);$this->useConnection($connection);return true;}//end setDSN()

	public function insert($fields,$tablename='') {
        $tablename=$tablename?:$this->_tableName;
        if (empty($fields['id'])) $fields['id']='init';
        $columns=array_map([ChisimbaDatabaseSchema::class,'identifier'],array_keys($fields));
        dbTable::forFrameworkConnection($this->_globalObjDb)->executeExactPrepared('INSERT INTO '.ChisimbaDatabaseSchema::identifier($tablename).' ('.implode(',',$columns).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')',array_values($fields));
        return $fields['id'];
    }

    public function _execute($stmt,$params=[]) { return dbTable::forFrameworkConnection($this->_globalObjDb)->executeExactPrepared($stmt,$params); }


}//end class

?>
