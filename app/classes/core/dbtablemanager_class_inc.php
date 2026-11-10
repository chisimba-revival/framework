<?php

/**
 * Database management object
 *
 * The dbTableManager class is the main (core) database management object. It can be used to create/drop tables
 * as well as index and manage tables and databases in a variety of RDBM's
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
 * @package   core
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2007 Paul Scott
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
// security check - must be included in all scripts
if (!
        /**
         * Description for $GLOBALS
         * @global entry point $GLOBALS['kewl_entry_point_run']
         * @name   $kewl_entry_point_run
         */
        $GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check


/**
 * Logging object
 */
require_once "lib/logging.php";

/**
 * database table manager
 *
 * The database manager object can be used to create, drop and manipulate table and database structure,
 * as well as manipulate db schema as a whole
 *
 * @category  Chisimba
 * @package   core
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2007 Paul Scott
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
class dbTableManager extends ChisimbaObject {
    /** Database server name loaded during schema-manager initialisation. */
    public $_serverName;


    /**
     * The current table name that we are working with
     *
     * @access public
     * @var    string - default NULL
     */
    public $_tableName = NULL;
    /**
     * The current database that we are working with
     *
     * @access public
     * @var    string - default NULL
     */
    public $_dbName = NULL;
    /**
     * The global error callback for dbTable errors
     *
     * @access public
     * @var    string
     */
    public $_errorCallback;
    /**
     * The database config object
     *
     * @access public
     * @var    object
     */
    public $objDBConfig;
    /**
     * The db object
     *
     * @access private
     * @var    object
     */
    private $_dbmanager = NULL;
    /**
     * The non Schema DB Object
     * We are instantiating this again, in case of first time install
     * This way, we can create the tables through the magic __call method
     * and get away with it
     *
     * @access private
     * @var    object
     */
    private $_db = NULL;
    public $appid;
    private ?ChisimbaDatabaseSchema $sharedSchema = null;

    /** Framework/installer composition; never store live connections in a session. */
    protected function useConnection($connection): void
    {
        $this->_db=$connection;
        $this->sharedSchema=null;
    }

    /** Catalogue installation hooks only: SQL comes from tracked migration files. */
    public function executeSchemaStatement($sql) { return $this->schema()->executeMigration($sql); }

    private function schema(): ChisimbaDatabaseSchema
    {
        require_once __DIR__.'/chisimbadatabaseschema.php';
        return $this->sharedSchema ??= new ChisimbaDatabaseSchema(dbTable::forFrameworkConnection($this->_db));
    }


    /**
     * Method to initialise the dbTableManager object.
     *
     * @access public
     * @param  string   $tableName     The name of the table this object encapsulates
     * @param  string   $dbName        The database name
     * @param  PEAR     $              ::MDB2_Schema $pearDb The PEAR::MDB2_Schema object to use (defaults to use the global connection)
     * @param  callback $errorCallback The name of a custom error callback function (defaults to the global)
     * @return void
     */
    public function init($dbName = NULL, $pearDbManager = NULL, $errorCallback = "globalPearErrorCallback") {

        $this->_errorCallback = $errorCallback;
        if ($pearDbManager == NULL) {
            $this->_dbmanager = $this->objEngine->getDbManagementObj();
            $this->_db = $this->objEngine->getDbObj();
        } else {
            $this->_dbmanager = $pearDbManager;
            throw new LogicException('External legacy schema managers are retired.');
        }

        // Load up the config object and get the servername
        $this->objDBConfig = $this->getObject('altconfig', 'config');
        $this->_serverName = $this->objDBConfig->serverName();
        $this->appid = $this->objEngine->appid;

    }

    /**
     * Method to parse a database definition file by creating a Metabase schema format
     * parser object and passing the file contents as parser input data stream.
     *
     * @param  string $input_file            the path of the database schema file.
     * @param  array  $variables             an associative array that the defines the text
     *                                       string values that are meant to be used to replace the variables that are
     *                                       used in the schema description.
     * @param  bool   $fail_on_invalid_names (optional) make function fail on invalid
     *                                       names
     * @return mixed  TRUE on success, or a MDB2 error object
     * @access public
     */
    public function parseDbDefFile($input_file, $variables = array(), $fail_on_invalid_names = TRUE) {
        throw new LogicException('Legacy XML database definitions are retired. Use registered native table definitions.');
    }

    /**
     * Method to attempt to reverse engineer a schema structure from an existing MDB2
     * This method can be used if no xml schema file exists yet.
     * The resulting xml schema file may need some manual adjustments.
     *
     * @return mixed  MDB2_OK or array with all ambiguities on success, or a MDB2 error object
     * @access public
     */
    public function getDefFromDb() {
        throw new LogicException('This legacy database operator API is retired. Use native schema operations.');
    }

    /**
     * Method to dump the database to a specified schema file
     * There are three options as to how to dump the db to file
     * 1. Structure only
     * 2. Content Only
     * 3. All - both Structure and content
     *
     * @access public
     * @param  string $option
     * @param  string $dumptype
     * @param  string $dumpfile
     * @return bool
     */
    public function dumpDatabaseToFile($option, $dumptype, $dumpfile) {
        throw new LogicException('This legacy database operator API is retired. Use native schema operations.');
    }

//func

    /**
     * Method to get the schema definition of a single table
     *
     * @param  string $table
     * @return array
     */
    public function getTableSchema($table) {
        $dbdef = $this->getDefFromDb();
        if (array_key_exists($table, $dbdef['tables'])) {
            return $dbdef['tables'][$table];
        } else {
            return FALSE;
        }
    }

    /**
     * Method to get a list of the fields in a table
     *
     * @param $table string Required
     */
    public function listTblFields($table) { return $this->schema()->columns($table); }

    /**
     * Method to get the debug strings from queries if neccessary
     *
     * @access private
     * @param  reference to       the management object $db
     * @param  string    $scope
     * @param  string    $message
     * @return string    message
     */
    private function printQueries($db, $scope, $message) {
        return $scope === 'query' ? $message : null;
    }

    /**
     * Method to create a table
     * <pre>
     * $fields = array(
     *   'id' => array(
     *   'type'     => 'char',
     *   'length'   => 32
     *   'unsigned' => TRUE,
     *   'autoincrement'  => FALSE,
     *  ),
     *  'somename' => array(
     *   'type'     => 'text',
     *   'length'   => 12,
     *  ),
     * 'somedate'  => array(
     *   'type'     => 'date',
     *  ),
     * );
     * $table = 'sometable';
     * </pre>
     *
     * since we are on php5 we can use the magic __call() method to:
     * - load the manager module: $_db->loadModule('Manager', NULL, TRUE);
     * - redirect the method call to the manager module: $_db->manager->createTable('sometable', $fields);
     *
     * @param string $tableName
     * @param array  $fields
     */
    public function createTable($tableName,$fields,$options) { return $this->schema()->createTable($tableName,$fields,$options); }

    /**
     * drop an existing table
     *
     * @param  string $name name of the table that should be dropped
     * @return mixed  MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function dropTable($name) { $this->schema()->dropTable($name); return true; }

    /**
     * Method to create an index on the table
     *
     * @access public
     * @param  string $tableName
     * @param  string $keyname
     * @param  array  $index
     * @return bool   TRUE on success | FALSE on failure
     */
    public function createTableIndex($tableName,$keyname,$index,$trunc=false) { return $this->schema()->createIndex($tableName,$keyname.'_idx',$index); }

    /**
     * Method to describe a pseudo PK id
     * Most RDBMS's besides MySQL do not have support for PK's so we fake it.
     *
     * @access public
     * @param  mixed  $tableName
     * @return bool   TRUE
     */
    public function createPK($tableName) { return $this->schema()->createIndex($tableName,'chisimba_id_idx',['fields'=>['id'=>[]]]); }

    /**
     * create a new database
     *
     * @param  string $db name of the database that should be created
     * @return bool   TRUE on success
     * @access public
     */
    public function createDb($db) { $this->schema(); dbTable::forFrameworkConnection($this->_db)->executeExactPrepared('CREATE DATABASE '.ChisimbaDatabaseSchema::identifier($db).' CHARACTER SET utf8 COLLATE utf8_unicode_ci'); return true; }

    /**
     * drop an existing database
     *
     * @param  string $db of the database that should be dropped
     * @return bool   TRUE on success
     * @access public
     */
    public function dropDb($db) { dbTable::forFrameworkConnection($this->_db)->executeExactPrepared('DROP DATABASE '.ChisimbaDatabaseSchema::identifier($db)); return true; }

    /**
     * list all databases
     *
     * @return mixed  data array on success
     * @access public
     */
    public function listDatabases() { return array_column(dbTable::forFrameworkConnection($this->_db)->selectExactPrepared('SHOW DATABASES'),'Database'); }

    /**
     * list all db users
     *
     * @return mixed  data array on success
     * @access public
     */
    public function listDbUsers() {
        throw new LogicException('This legacy database operator API is retired. Use native schema operations.');
    }

    /**
     * list all views in the current database
     *
     * @return mixed  data array on success
     * @access public
     */
    public function listDbViews() {
        throw new LogicException('This legacy database operator API is retired. Use native schema operations.');
    }

    /**
     * list all functions in the current database
     *
     * @return mixed  data array on success, a MDB2 error on failure
     * @access public
     */
    public function listDbFunctions() {
        throw new LogicException('This legacy database operator API is retired. Use native schema operations.');
    }

    /**
     * list all tables in the current database
     *
     * @return mixed  data array on success, a MDB2 error on failure
     * @access public
     */
    public function listDbTables() { return $this->schema()->tables(); }

    /**
     * list all fields in a tables in the current database
     *
     * @param  string $table name of table that should be used in method
     * @return mixed  data array on success, a MDB2 error on failure
     * @access public
     */
    public function listTableFields($table) { return $this->schema()->columns($table); }

    /**
     * get the stucture of a field into an array
     *
     * @param  string $table      name of the table on which the index is to be created
     * @param  string $name       name of the index to be created
     * @param  array  $definition associative array that defines properties of the index to be created.
     *                            Currently, only one property named FIELDS is supported. This property
     *                            is also an associative with the names of the index fields as array
     *                            indexes. Each entry of this array is set to another type of associative
     *                            array that specifies properties of the index that are specific to
     *                            each field.
     *
     *                            Currently, only the sorting property is supported. It should be used
     *                            to define the sorting direction of the index. It may be set to either
     *                            ascending or descending.
     *
     *                            Not all DBMS support index sorting direction configuration. The DBMS
     *                            drivers of those that do not support it ignore this property. Use the
     *                            function supports() to determine whether the DBMS driver can manage indexes.
     *
     *                            Example
     *                            array(
     *                            'fields' => array(
     *                            'user_name' => array(
     *                            'sorting' => 'ascending'
     *                            ),
     *                            'last_login' => array()
     *                            )
     *                            )
     * @return mixed  MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function createIndex($table,$name,$definition) { return $this->schema()->createIndex($table,$name.'_idx',$definition); }

    /**
     * drop existing index
     *
     * @param  string $table name of table that should be used in method
     * @param  string $name  name of the index to be dropped
     * @return mixed  MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function dropIndex($table,$name) { return $this->schema()->dropIndex($table,strtoupper($name)==='PRIMARY'?'PRIMARY':$name.'_idx'); }

    /**
     * list all indexes in a table
     *
     * @param  string $table name of table that should be used in method
     * @return mixed  data array on success, a MDB2 error on failure
     * @access public
     */
    public function listTableIndexes($table) { return array_values(array_unique(array_map(fn($name)=>preg_replace('/_idx$/','',$name), array_column(array_filter($this->schema()->indexes($table),fn($row)=>$row['Key_name']!=='PRIMARY'),'Key_name')))); }

    /**
     * create a constraint on a table
     *
     * @param  string $table      name of the table on which the constraint is to be created
     * @param  string $name       name of the constraint to be created
     * @param  array  $definition associative array that defines properties of the constraint to be created.
     *                            Currently, only one property named FIELDS is supported. This property
     *                            is also an associative with the names of the constraint fields as array
     *                            constraints. Each entry of this array is set to another type of associative
     *                            array that specifies properties of the constraint that are specific to
     *                            each field.
     *
     *                            Example
     *                            array(
     *                            'fields' => array(
     *                            'user_name' => array(),
     *                            'last_login' => array()
     *                            )
     *                            )
     * @return mixed  MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function createConstraint($table,$name,$definition) { return $this->schema()->createIndex($table,$name.'_idx',$definition,true); }

    /**
     * drop existing constraint
     *
     * @param  string $table name of table that should be used in method
     * @param  string $name  name of the constraint to be dropped
     * @return mixed  MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function dropConstraint($table,$name) { return $this->schema()->dropIndex($table,strtoupper($name)==='PRIMARY'?'PRIMARY':$name.'_idx'); }

    /**
     * list all constraints in a table
     *
     * @param  string $table name of table that should be used in method
     * @return mixed  data array on success, a MDB2 error on failure
     * @access public
     */
    public function listTableConstraints($table) { return array_values(array_unique(array_map(fn($name)=>preg_replace('/_idx$/','',$name), array_column(array_filter($this->schema()->indexes($table),fn($row)=>(int)$row['Non_unique']===0),'Key_name')))); }

    /**
     * create sequence
     *
     * @param  string $seq_name name of the sequence to be created
     * @param  string $start    start value of the sequence; default is 1
     * @return mixed  MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function createSequence($seq_name, $start = 1) {
        throw new LogicException('This legacy database operator API is retired. Use native schema operations.');
    }

    /**
     * drop existing sequence
     *
     * @param  string $seq_name name of the sequence to be dropped
     * @return mixed  MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function dropSequence($name) {
        throw new LogicException('This legacy database operator API is retired. Use native schema operations.');
    }

    /**
     * list all sequences in the current database
     *
     * @return mixed  data array on success, a MDB2 error on failure
     * @access public
     */
    public function listSequences() {
        throw new LogicException('This legacy database operator API is retired. Use native schema operations.');
    }

    /**
     * Log out and disconnect from the database.
     *
     * @return mixed  TRUE on success, FALSE if not connected and error
     *                object on error
     * @access public
     */
    public function disconnectDb() {
        return $this->_db->disconnect();
    }

    /**
     * Select a different database
     *
     * @param  string $name name of the database that should be selected
     * @return string name of the database previously connected to
     * @access public
     */
    public function setDatabase($name) { $this->schema(); dbTable::forFrameworkConnection($this->_db)->executeExactPrepared('USE '.ChisimbaDatabaseSchema::identifier($name)); return true; }

    /**
     * get the current database
     *
     * @return string name of the database
     * @access public
     */
    public function getDatabase() { return dbTable::forFrameworkConnection($this->_db)->selectExactPrepared('SELECT DATABASE() AS name')[0]['name']; }

    /**
     * return version information about the server
     *
     * @param  string $native determines if the raw version string should be returned
     * @return mixed  array with versoin information or row string
     * @access public
     */
    public function getServerVersion($native=false) { $version=dbTable::forFrameworkConnection($this->_db)->selectExactPrepared('SELECT VERSION() AS version')[0]['version']; return $native?$version:['native'=>$version,0=>$version]; }

    /**
     * this function helps in checking if a key exists before adding it to the
     * table
     * @param <type> $change
     * @param <type> $fields
     * @param <type> $changes
     * @return <type>
     */
    private function returnIfExistingKey($change, $fields, $changes) {

        foreach ($change as $c => $b) {
            foreach ($fields as $field) {

                if ($field == $c) {
                    return TRUE;
                }
            }
        }
    }

    /**
     * alter an existing table
     *
     * @param  string  $name    name of the table that is intended to be changed.
     * @param  array   $changes associative array that contains the details of each type
     *                          of change that is intended to be performed. The types of
     *                          changes that are currently supported are defined as follows:
     *
     *                          name
     *
     *                          New name for the table.
     *
     *                          add
     *
     *                          Associative array with the names of fields to be added as
     *                          indexes of the array. The value of each entry of the array
     *                          should be set to another associative array with the properties
     *                          of the fields to be added. The properties of the fields should
     *                          be the same as defined by the Metabase parser.
     *
     *
     *                          remove
     *
     *                          Associative array with the names of fields to be removed as indexes
     *                          of the array. Currently the values assigned to each entry are ignored.
     *                          An empty array should be used for future compatibility.
     *
     *                          rename
     *
     *                          Associative array with the names of fields to be renamed as indexes
     *                          of the array. The value of each entry of the array should be set to
     *                          another associative array with the entry named name with the new
     *                          field name and the entry named Declaration that is expected to contain
     *                          the portion of the field declaration already in DBMS specific SQL code
     *                          as it is used in the CREATE TABLE statement.
     *
     *                          change
     *
     *                          Associative array with the names of the fields to be changed as indexes
     *                          of the array. Keep in mind that if it is intended to change either the
     *                          name of a field and any other properties, the change array entries
     *                          should have the new names of the fields as array indexes.
     *
     *                          The value of each entry of the array should be set to another associative
     *                          array with the properties of the fields to that are meant to be changed as
     *                          array entries. These entries should be assigned to the new values of the
     *                          respective properties. The properties of the fields should be the same
     *                          as defined by the Metabase parser.
     *
     *                          Example
     *                          array(
     *                          'name' => 'userlist',
     *                          'add' => array(
     *                          'quota' => array(
     *                          'type' => 'integer',
     *                          'unsigned' => 1
     *                          )
     *                          ),
     *                          'remove' => array(
     *                          'file_limit' => array(),
     *                          'time_limit' => array()
     *                          ),
     *                          'change' => array(
     *                          'name' => array(
     *                          'length' => '20',
     *                          'definition' => array(
     *                          'type' => 'text',
     *                          'length' => 20,
     *                          ),
     *                          )
     *                          ),
     *                          'rename' => array(
     *                          'sex' => array(
     *                          'name' => 'gender',
     *                          'definition' => array(
     *                          'type' => 'text',
     *                          'length' => 1,
     *                          'default' => 'M',
     *                          ),
     *                          )
     *                          )
     *                          )
     *
     * @param  boolean $check   indicates whether the function should just check if the DBMS driver
     *                          can perform the requested table alterations if the value is TRUE or
     *                          actually perform them otherwise.
     * @access public
     *
     * @return mixed   MDB2_OK on success, a MDB2 error on failure
     */
    public function alterTable($name,$changes,$check) {
        if (isset($changes['insert'])) {
            if ($check) return true;
            $table=dbTable::forFrameworkConnection($this->_db);
            return $table->insert($changes['insert'],$name)!==false;
        }
        return $this->schema()->alterTable($name,$changes,(bool)$check);
    }

}

?>
