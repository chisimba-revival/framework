<?php

/**
 * dbTable Class
 *
 * Database abstraction layer.
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
 * Logging layer
 */
require_once "lib/logging.php";
require_once __DIR__ . "/chisimbadatabaseconnection.php";

/**
 * dbTable class
 *
 * This class encapsulates all database functions and methods. All modules that require database access should extend this base class.
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
class dbTable extends ChisimbaObject {

    /**
     * Whether or not to use prepared statements
     *
     * @access public
     * @var    string
     */
    public $USE_PREPARED_STATEMENTS = FALSE;

    /**
     * The current table name that we are working with
     *
     * @access public
     * @var    string - default NULL
     */
    public $_tableName = NULL;

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
    private $_db = NULL;

    /**
     * Database type for this instance and its connection
     *
     * @var string
     */
    public $dbType;

    /** Server prefix used when generating identifiers and cache keys. */
    public $_serverName;

    /**
     * Are we in a transaction?
     *
     * @access private
     * @var    string
     */
    private $_inTransaction = FALSE;

    /** Native connection generation owned by this table, never another caller. */
    private ?int $nativeTransactionId = null;

    /** Connections owned by callback transactions; legacy methods may not end them. */
    private static ?WeakMap $callbackTransactions = null;

    /**
     * property to hold the last id inserted into the db
     *
     * @access private
     * @var    string
     */
    private $_lastId = NULL;

    /**
     * Property to hold the portability object for queries against multiple RDBM's
     *
     * @access private
     * @var    object
     */
    private $_portability;

    /**
     * Property to handle the error reporting
     *
     * @access private
     * @var    string
     */
    private $debug = FALSE;

    // Kept only to make the retired cache branches inert until their APC-only
    // simplification is completed. No code can set this to TRUE.
    public $objMemcache = FALSE;
    public $objAPC = FALSE;
    protected $cacheTTL = 3600;
    public $objYaml;
    public $dbLayer;
    public $cachePrefix;
    public $objLu;
    public $objLuAdmin;
    public $enableLogging;
    public $appid;

    /**
     * Method to initialise the dbTable object.
     *
     * @access public
     * @param  string   $tableName     The name of the table this object encapsulates
     * @param  boolean  $mirror        Whether to mirror these operations (defaults to TRUE)
     * @param ChisimbaDatabaseConnection|null $pearDb Native connection; historical argument name retained.
     * @param  callback $errorCallback The name of a custom error callback function (defaults to the global)
     * @return void
     */
    /** Framework bootstrap only: reuse a connection before module composition exists. */
    public static function forFrameworkConnection($connection): self
    {
        $table=new self();
        $table->_db=$connection;
        if (!$connection instanceof ChisimbaDatabaseConnection) throw new LogicException('Only native PDO connections are supported.');
        $table->dbLayer='PDO';
        $table->dbType='mysqli';
        $table->_serverName='framework';
        return $table;
    }

    public function init($tableName = null, $pearDb = NULL, $errorCallback = "globalPearErrorCallback") {
        $modname = $this->objEngine->_moduleName;
        $this->objLuAdmin = null;
        $this->objLu = null;
        $this->enableLogging = $this->objEngine->enableLogging;
        $this->eventDispatcher = $this->objEngine->eventDispatcher;
        // global $_globalObjDb;
        $this->_tableName = $tableName;
        $this->_errorCallback = $errorCallback;
        if ($pearDb == NULL) {
            $this->_db = $this->objEngine->getDbObj();
            $pearDb = $this->_db;
        } else {
            $this->_db = $pearDb;
        }

        $this->objDBConfig = $this->getObject('altconfig', 'config');
        // check for APC
        if (extension_loaded('apc')) {
            if ($this->objDBConfig->getenable_apc() == 'TRUE') {
                $this->objAPC = TRUE;
            } else {
                $this->objAPC = FALSE;
            }
            $this->cacheTTL = $this->objDBConfig->getcache_ttl();
        }

        $this->_serverName = $this->objDBConfig->serverName();
        // set up the cache prefix for this instance
        $this->cachePrefix = $this->_serverName . "_" . $modname . "_";
        //check if debugging is enabled
        if ($this->objDBConfig->geterror_reporting() == "developer") {
            $this->debug = TRUE;
        }
        $this->dbLayer = $this->objDBConfig->getenable_dbabs();
        if ($this->dbLayer !== 'PDO' || !$this->_db instanceof ChisimbaDatabaseConnection) {
            throw new LogicException('Only native PDO connections are supported.');
        }
        $this->dbType = $this->objEngine->pdsn['phptype'];
        $this->appid = $this->objEngine->appid;
    }

    /**
     * Preserve dbTable's established associative/string row contract at its boundary.
     * Repositories needing exact text use the explicit exact prepared APIs below.
     * Database failures return false as documented; diagnostics contain no SQL/data.
     */
    private function nativeQuery($sql, array $parameters = []) {
        try {
            // Match MDB2_PORTABILITY_EMPTY_TO_NULL for legacy CRUD/bindings.
            $parameters = array_map(static fn($value) => $value === '' ? null : $value, $parameters);
            $result = $this->_db->query($sql, $parameters);
            if (!is_array($result)) {
                if ($this->objAPC) apc_clear_cache("user");
                return $result;
            }
            return array_map(static function ($row) {
                $converted = [];
                foreach ($row as $key => $value) {
                    if ($value !== null) {
                        $value = $value === '' ? null : rtrim((string)$value);
                    }
                    $converted[strtolower($key)] = $value;
                }
                return $converted;
            }, $result);
        } catch (ChisimbaDatabaseException $error) {
            error_log($error->getMessage());
            return false;
        }
    }

    /**
     * Run one atomic repository operation. Nested/foreign transactions are
     * rejected before the callback runs. A failed commit never returns success.
     * The callback must not execute DDL or manage transactions itself.
     */
    public function transaction(callable $operation) {
        $owner = $this->_db->beginTransaction();
        self::$callbackTransactions ??= new WeakMap();
        self::$callbackTransactions[$this->_db] = true;
        try {
            $value = $operation();
            $this->_db->commit($owner);
            return $value;
        } catch (Throwable $error) {
            try {
                if ($this->_db->inTransaction()) $this->_db->rollback($owner);
            } catch (Throwable $rollbackError) {
                error_log('Database transaction rollback failed.');
            }
            throw $error;
        } finally {
            unset(self::$callbackTransactions[$this->_db]);
        }
    }

    /**
     * Compose an atomic operation with an existing MySQL transaction. The server
     * state also recognises legacy callers that used SQL START TRANSACTION.
     * Only this savepoint is released/rolled back; the outer owner keeps control.
     */
    public function transactionWithSavepoint(callable $operation) {
        if (!in_array($this->dbType, ['mysql','mysqli'], true)) {
            throw new RuntimeException('Savepoints require a validated MySQL/MariaDB adapter.');
        }
        $state = $this->selectExactPrepared('SELECT @@in_transaction AS active');
        if (empty($state[0]['active'])) return $this->transaction($operation);
        $name = 'chisimba_' . bin2hex(random_bytes(12));
        $this->executeExactPrepared('SAVEPOINT ' . $name);
        self::$callbackTransactions ??= new WeakMap();
        $alreadyOwned = isset(self::$callbackTransactions[$this->_db]);
        self::$callbackTransactions[$this->_db] = true;
        try {
            $value = $operation();
            $this->executeExactPrepared('RELEASE SAVEPOINT ' . $name);
            return $value;
        } catch (Throwable $error) {
            try {
                $this->executeExactPrepared('ROLLBACK TO SAVEPOINT ' . $name);
                $this->executeExactPrepared('RELEASE SAVEPOINT ' . $name);
            } catch (Throwable $rollbackError) {
                error_log('Database savepoint rollback failed.');
            }
            throw $error;
        } finally {
            if (!$alreadyOwned) unset(self::$callbackTransactions[$this->_db]);
        }
    }

    /**
     * Checked, positional prepared reads for repositories. Retains dbTable's
     * legacy row values; unlike getArray(), a database failure throws.
     */
    public function selectPrepared(#[\SensitiveParameter] $sql, #[\SensitiveParameter] array $parameters = []): array {
        return $this->checkedPrepared($sql, $parameters, false);
    }

    /** Checked writes return affected rows, including zero for a failed CAS. */
    public function executePrepared(#[\SensitiveParameter] $sql, #[\SensitiveParameter] array $parameters = []): int {
        $affected = $this->checkedPrepared($sql, $parameters, true);
        if ($this->objAPC) apc_clear_cache("user");
        return $affected;
    }

    /** Exact text reads: retain empty strings, whitespace and column case. */
    public function selectExactPrepared(#[\SensitiveParameter] $sql, #[\SensitiveParameter] array $parameters = []): array {
        return $this->exactPrepared($sql, $parameters, false);
    }

    /** Exact text writes must not apply MDB2's empty-string-to-NULL portability. */
    public function executeExactPrepared(#[\SensitiveParameter] $sql, #[\SensitiveParameter] array $parameters = []): int {
        $result = $this->exactPrepared($sql, $parameters, true);
        if ($this->objAPC) apc_clear_cache('user');
        return $result;
    }

    private function exactPrepared(#[\SensitiveParameter] $sql, #[\SensitiveParameter] array $parameters, bool $write) {

        if (!is_string($sql) || trim($sql) === '' || !array_is_list($parameters)) {
            throw new InvalidArgumentException('Prepared queries require SQL and positional parameters.');
        }

            return $write ? $this->_db->execute($sql, $parameters) : $this->_db->fetchAll($sql, $parameters);
    }

    public function hasActiveTransaction(): bool {
        return $this->_db->inTransaction();
    }

    /** Database-scoped MySQL/MariaDB coordination on this canonical connection. */
    public function withDatabaseLock(string $scope, callable $operation, int $timeout = 5) {
        if (!in_array($this->dbType, ['mysql', 'mysqli'], true)) {
            throw new RuntimeException('Database locks require MySQL or MariaDB.');
        }
        if (!preg_match('/^[a-z0-9:-]{1,24}$/D', $scope) || $timeout < 0 || $timeout > 60) {
            throw new InvalidArgumentException('Invalid database lock request.');
        }
        $name = "CONCAT(?,LEFT(SHA2(DATABASE(),256),40))";
        $rows = $this->selectExactPrepared('SELECT GET_LOCK(' . $name . ',?) AS acquired', [$scope, $timeout]);
        if ((int)($rows[0]['acquired'] ?? 0) !== 1) throw new RuntimeException('Database data is busy; retry the operation.');
        try { return $operation(); }
        finally {
            $rows = $this->selectExactPrepared('SELECT RELEASE_LOCK(' . $name . ') AS released', [$scope]);
            if ((int)($rows[0]['released'] ?? 0) !== 1) throw new RuntimeException('Database lock release failed.');
        }
    }

    /** Driver mechanics stay here, never in authentication/domain repositories. */
    private function checkedPrepared(#[\SensitiveParameter] $sql, #[\SensitiveParameter] array $parameters, bool $write) {

        if (!is_string($sql) || trim($sql) === '' || !array_is_list($parameters)) {
            throw new InvalidArgumentException('Prepared queries require SQL and positional parameters.');
        }

            $parameters = array_map(static fn($value) => $value === '' ? null : $value, $parameters);
            if ($write) return $this->_db->execute($sql, $parameters);
            return array_map(static function ($row) {
                $converted = [];
                foreach ($row as $key => $value) {
                    $converted[strtolower($key)] = $value === null || $value === ''
                        ? null : rtrim((string) $value);
                }
                return $converted;
            }, $this->_db->fetchAll($sql, $parameters));
    }

    /**
     * Method to evaluate if a particular value of a particular field exists in the database
     *
     * @access public
     * @param  string $field the name of the field to search
     * @param  mixed  $value the value to search for
     * @return bool   TRUE |FALSE if exists return TRUE, otherwise FALSE.
     */
    public function valueExists($field, $value, $table = NULL) {
        $rows = $this->nativeQuery('SELECT COUNT(*) AS fcount FROM '
            . $this->_db->quoteIdentifier($table ?? $this->_tableName) . ' WHERE '
            . $this->_db->quoteIdentifier($field) . ' = ?', [$value]);
        return $rows !== false && (int)$rows[0]['fcount'] > 0;
    }

    /**
     * Method to get all items from the table.
     * Override in derived classes to implement access restrictions
     *
     * @access public
     * @param  string $filter a SQL WHERE clause (optional)
     * @return array  |FALSE Rows as an array of associative arrays, or FALSE on failure
     */
    public function getAll($filter = NULL) {
        $stmt = "SELECT * FROM {$this->_tableName} $filter";
        if ($this->objMemcache == TRUE) {
            if (chisimbacache::getMem()->get(md5($this->cachePrefix . $stmt))) {
                $cache = chisimbacache::getMem()->get(md5($this->cachePrefix . $stmt));
                $ret = unserialize($cache);
            } else {
                if ($this->debug == TRUE) {
                    log_debug($stmt);
                }
                $ret = $this->getArray($stmt);
                chisimbacache::getMem()->set(md5($this->cachePrefix . $stmt), serialize($ret), MEMCACHE_COMPRESSED, $this->cacheTTL);
            }
        } elseif ($this->objAPC == TRUE) {
            $ret = apc_fetch($this->cachePrefix . $stmt);
            if ($ret == FALSE) {
                $ret = $this->getArray($stmt);
                apc_store($this->cachePrefix . $stmt, $ret, $this->cacheTTL);
            }
        } else {
            if ($this->debug == TRUE) {
                log_debug($stmt);
            }
            $ret = $this->getArray($stmt);
        }

        return $ret;
    }

    /**
     * Method to fetch a row from the table.
     * Override in derived classes if necessary
     *
     * @access public
     * @return array  |FALSE A row as an associative array, or FALSE on failure
     * @param  string $pkfield the name of the primary key field
     * @param  mixed  $pkvalue the value of the primary key field for the record
     */
    public function getRow($pk_field, $pk_value, $table = NULL) {
        if ($table !== null) $this->_tableName = $table;
        $rows = $this->nativeQuery('SELECT * FROM ' . $this->_db->quoteIdentifier($this->_tableName)
            . ' WHERE ' . $this->_db->quoteIdentifier($pk_field) . ' = ?', [$pk_value ?? '']);
        return $rows === false ? false : ($rows[0] ?? null);
    }

    /**
     * Method to execute a query against the database and return an array.
     *
     * @access public
     * @param  string $stmt the SQL query string
     * @return array  |FALSE Rows as an array of associate arrays, or FALSE on failure
     */
    public function getArray($stmt) {
        return $this->nativeQuery($stmt);
    }

    /**
     * Method to execute a query against the database and return an array.
     *
     * @access public
     * @param  string $stmt  the SQL query string
     * @param  int    $first The first record to return
     * @param  int    $count The number of records to return
     * @return array  |FALSE Rows as an array of associate arrays, or FALSE on failure
     */
    public function getArrayWithLimit($stmt, $first, $count) {
        foreach ([$first, $count] as $bound) {
            if (!(is_int($bound) || is_string($bound) && ctype_digit($bound)) || $bound < 0
                || (string)(int)$bound !== (string)$bound) {
                throw new InvalidArgumentException('Limits must be non-negative integers.');
            }
        }
        return $this->nativeQuery(rtrim(rtrim($stmt), ';') . ' LIMIT ' . (int)$count . ' OFFSET ' . (int)$first);
    }

    /**
     * Method to structure and execute a subselect
     *
     * Note: If your db server does not support subselects, this will not work, so please check first
     *
     * @example // sub-select query string
     *           $sql_ss = 'SELECT id FROM people WHERE id = 1 OR id = 2';
     *           // the main query
     *           $sql = 'SELECT * FROM people WHERE id IN (%s)';
     *
     * @param string $sql the primary sql query
     * @param string $ss_sql The subselect query
     * @return array $ret return values array
     */
    public function subSelect($sql, $ss_sql) {
        return $this->nativeQuery(sprintf($sql, $ss_sql));
    }

    /**
     * Method to fetch all items from the table.
     * Override in derived classes to implement access restrictions
     *
     * @access     public
     * @return array|false Rows, or false on failure.
     * @param      string $filter a SQL WHERE clause (optional)
     * @deprecated See getAll
     * @see        getAll
     */
    public function fetchAll($filter = NULL) {
        $stmt = "SELECT * FROM {$this->_tableName} " . $filter;
        if ($this->objMemcache == TRUE) {
            if (chisimbacache::getMem()->get(md5($this->cachePrefix . $stmt))) {
                $cache = chisimbacache::getMem()->get(md5($this->cachePrefix . $stmt));
                $ret = unserialize($cache);
            } else {
                if ($this->debug == TRUE) {
                    log_debug($stmt);
                }
                $ret = $this->query($stmt);
                chisimbacache::getMem()->set(md5($this->cachePrefix . $stmt), serialize($ret), MEMCACHE_COMPRESSED, $this->cacheTTL);
            }
        } elseif ($this->objAPC == TRUE) {
            $ret = apc_fetch($this->cachePrefix . $stmt);
            if ($ret == FALSE) {
                $ret = $this->query($stmt);
                apc_store($this->cachePrefix . $stmt, $ret, $this->cacheTTL);
            }
        } else {
            if ($this->debug == TRUE) {
                log_debug($stmt);
            }
            $ret = $this->query($stmt);
        }
        return $ret;
    }

    /**
     * Method to return a count of records in the table.
     * Mainly to use in recordset paging
     *
     * @access public
     * @param  string $filter a SQL WHERE clause (optional)
     * @return int    number of records that matched
     */
    public function getRecordCount($filter = NULL) {
        $sql = "SELECT COUNT(*) AS rc FROM {$this->_tableName} " . $filter;
        if ($this->objMemcache == TRUE) {
            if (chisimbacache::getMem()->get(md5($this->cachePrefix . $sql))) {
                $cache = chisimbacache::getMem()->get(md5($this->cachePrefix . $sql));
                $ret = unserialize($cache);
            } else {
                if ($this->debug == TRUE) {
                    log_debug($sql);
                }
                $rs = $this->query($sql);
                $ret = $rs[0]['rc'];
                chisimbacache::getMem()->set(md5($this->cachePrefix . $sql), serialize($ret), MEMCACHE_COMPRESSED, $this->cacheTTL);
            }
        } elseif ($this->objAPC == TRUE) {
            $ret = apc_fetch($this->cachePrefix . $sql);
            if ($ret == FALSE) {
                $rs = $this->query($sql);
                $ret = $rs[0]['rc'];
                apc_store($this->cachePrefix . $sql, $ret, $this->cacheTTL);
            }
        } else {
            if ($this->debug == TRUE) {
                log_debug($sql);
            }
            $rs = $this->query($sql);
            $ret = $rs[0]['rc'];
        }
        return $ret;
    }

    /**
     * Method to begin a transaction.
     * Use a transaction where you are performing multiple inserts and/or updates as part
     * of a single conceptual operation.
     *
     * @access public
     * @param  void
     * @return void
     */
    public function beginTransaction() {
        if (isset(self::$callbackTransactions[$this->_db])) {
            throw new LogicException("The callback owns this transaction.");
        }

            $this->nativeTransactionId = $this->_db->beginTransaction();
            $this->_inTransaction = true;
            return;
    }

    /**
     * Method to commit a transaction.
     *
     * @param  void
     * @return set    property _inTransaction
     * @access public
     */
    public function commitTransaction() {
        if (isset(self::$callbackTransactions[$this->_db])) {
            throw new LogicException("The callback owns this transaction.");
        }

            if (!$this->_inTransaction) throw new LogicException('This dbTable does not own a transaction.');
            $this->_db->commit($this->nativeTransactionId);
            $this->nativeTransactionId = null;
            $this->_inTransaction = false;
            return;
    }

    /**
     * Method to rollback a transaction.
     *
     * @param  void
     * @return void
     * @access public
     */
    public function rollbackTransaction() {
        if (isset(self::$callbackTransactions[$this->_db])) {
            throw new LogicException("The callback owns this transaction.");
        }

            if (!$this->_inTransaction) throw new LogicException('This dbTable does not own a transaction.');
            $this->_db->rollback($this->nativeTransactionId);
            $this->nativeTransactionId = null;
            $this->_inTransaction = false;
            return;
    }

    /** Preserve existing short site IDs; long names must still fit the 32-byte ID schema. */
    private function newRecordId()
    {
        $id = $this->_serverName . '_' . random_int(1000, 99999) . '_' . time();
        return strlen($id) <= 32 ? $id : bin2hex(random_bytes(16));
    }

    /**
     * Method to insert a new record into the table.
     *
     * @param  array  $fields    The new record as an associative array containing field names as keys,
     *                           field values as values. All non-NULL fields must be present
     * @param  string $tablename The table to insert into, if not the default (optional)
     * @return string |FALSE Generated PK ID on success, FALSE on failure
     * @access public
     */
    public function insert($fields, $tablename = '') {
        if (!isset($fields['id']) || $fields['id'] === '') {
            $fields['id'] = $this->newRecordId();
        }
        $columns = array_map([$this->_db, 'quoteIdentifier'], array_keys($fields));
        $sql = 'INSERT INTO ' . $this->_db->quoteIdentifier($tablename ?: $this->_tableName)
            . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($fields), '?')) . ')';
        $result = $this->nativeQuery($sql, array_values($fields));
        if ($result === false) return false;
        $this->_lastId = $fields['id'];
        return $this->_lastId;
    }

    /**
     * Method to update an existing record in the table.
     *
     * @param  string $pkfield   the name of the primary key field
     * @param  mixed  $pkvalue   the value of the primary key field for the record to update
     * @param  array  $fields    The record as an associative array containing field names as keys and field values as values.
     * @param  string $tablename The name of the table to update, if not the default (optional)
     * @return TRUE   |FALSE TRUE on success, FALSE on failure
     * @access public
     */
    public function update($pkfield, $pkvalue, $fields, $tablename = '') {
        if ($fields === []) throw new InvalidArgumentException('Update fields are required.');
        $assignments = array_map(fn($name) => $this->_db->quoteIdentifier($name) . ' = ?', array_keys($fields));
        $sql = 'UPDATE ' . $this->_db->quoteIdentifier($tablename ?: $this->_tableName)
            . ' SET ' . implode(', ', $assignments) . ' WHERE ' . $this->_db->quoteIdentifier($pkfield) . ' = ?';
        // Legacy update/delete results were truthy even when zero rows changed.
        // Keep their documented success boolean; query/_execute expose counts.
        return $this->nativeQuery($sql, [...array_values($fields), $pkvalue]) !== false;
    }

    /**
     * Method to delete a record from the table.
     *
     * @param  string $pkfield the name of the primary key field
     * @param  mixed  $pkvalue the value of the primary key field for the record to delete
     * @return TRUE   |FALSE TRUE on success, FALSE on failure
     * @access public
     */
    public function delete($pkfield, $pkvalue, $tablename = '') {
        return $this->nativeQuery('DELETE FROM ' . $this->_db->quoteIdentifier($tablename ?: $this->_tableName)
            . ' WHERE ' . $this->_db->quoteIdentifier($pkfield) . ' = ?', [$pkvalue]) !== false;
    }

    /**
     * Method to execute a SQL statement.
     *      Should not be used outside of methods of this class.
     *      Add methods here if you need functionality not available
     *      from them. Successful implementation of dynamic mirroring
     *      will depend on all database modifications being channeled
     *      through this class or its derived classes.
     *
     * @param  string $stmt   The SQL statement
     * @param  array  $params The parameters to the execute, defaults to empty
     * @return TRUE   |FALSE TRUE on success, FALSE on failure
     * @access public
     * @todo   create an alias for this for public use and mark it as private
     */
    public function _execute($stmt, $params = array()) {
        return $this->nativeQuery($stmt, $params === '' ? [] : $params);
    }


    /**
     * Method to fetch the primary key value of the last insert operation
     *
     * @return mixed  Primary key value of last insert.
     * @param  void
     * @access public
     */
    public function getLastInsertId() {
        return $this->_lastId;
    }

    /**
     * The error callback function, defers to configured error handler
     *
     * @param  string $error
     * @return void
     * @access public
     */
    public function errorCallback($error) {
        call_user_func($this->_errorCallback, $error);
    }

    /**
     * Method to execute a query against the database
     *
     * @param      string $stmt the SQL query string
     * @return array|int|false Rows, affected count, or false on failure.
     * @deprecated see execute()
     * @access     public
     */
    public function query($stmt) {
        return $this->nativeQuery($stmt);
    }

    /**
     * Determine whether an SQL statement returns rows.
     *
     * @param string $stmt
     * @return bool
     */
    private function _isResultQuery($stmt) {
        $sql = ltrim($stmt);

        do {
            $previous = $sql;

            $sql = preg_replace(
                '/^\/\*.*?\*\/\s*/s',
                '',
                $sql
            );

            $sql = preg_replace(
                '/^(?:--[^\r\n]*|#[^\r\n]*)[\r\n]+\s*/',
                '',
                $sql
            );
        } while ($sql !== $previous);

        return preg_match(
            '/^(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN|WITH)\b/i',
            $sql
        ) === 1;
    }

    /**
     * Execute SQL that does not return rows.
     *
     * @param string $stmt
     * @return mixed
     */
    private function _executeStatement($stmt) {
        return $this->nativeQuery($stmt);
    }


    /**
     * Method to construct a sql join statement.
     *
     * The assumption is that the From table is the current table.<br>
     * An example: The join condition is (tblFrom.fk1 = tblTo.pk1)
     * <code>join('INNER JOIN', 'tblTo', array('fk1'=>'pk1')</code>
     *
     * @param  string $sqlJoinType the sql JOIN statement. (eg. 'INNER JOIN'|'LEFT JOIN')
     * @param  string $tblJoinTo   name of the table to join to.
     * @param  array  $join        the foriegn and primary keys of the two tables. (eg. array('fkfield' => 'pkfield') )
     * @param  string $tblJoinFrom name of the table being joined to.(optional)
     * @return string the sql JOIN statement.
     * @access public
     */
    public function join($sqlJoinType, $tblJoinTo, $join, $tblJoinFrom = NULL) {
        $tblJoinFrom = (is_NULL($tblJoinFrom)) ? $this->_tableName : $tblJoinFrom;
        $strSQL = "{$sqlJoinType} {$tblJoinTo}";
        // Condtions of the join
        $insertON = TRUE;
        foreach ($join as $key => $value) {
            $strSQL .= ($insertON) ? " ON ( " : " OR ( ";
            $insertON = FALSE;
            $strSQL .= "{$tblJoinFrom}.{$key} = {$tblJoinTo}.{$value}";
            $strSQL .= " )";
        }
        if ($this->debug == TRUE) {
            log_debug($strSQL);
        }
        return $strSQL;
    }

    /**
     * Method to generate a unique ID for use as a primary key value
     * To ensure uniqueness amongst a mirroring cluster '@servername' is
     * appended.
     *
     * @return string The generated ID
     * @param  void
     * @access public
     */
    public function generateId() {
        throw new LogicException('This legacy database operation is not supported by the native boundary.');
    }

    /**
     * Method to return the last entry in a table or
     * last entry that matches $filter
     *
     * @param string $filter     A SQL where clause
     * @param string $orderField The field to use as the
     *                           order field to mark the last entry, defaults to the
     *                           id field
     */
    public function getLastEntry($filter = NULL, $orderField = "id") {
        $sql = "SELECT * FROM " . $this->_tableName . $filter . " ORDER BY "
                . $orderField . " DESC ";
        if ($this->debug == TRUE) {
            log_debug($sql);
        }
        return $this->getArrayWithLimit($sql, 0, 1);
    }

    /**
     * Select a different database
     *
     * @param  string $name name of the database that should be selected
     * @return string name of the database previously connected to
     * @access public
     */
    public function setDatabaseTo($name) {
        throw new LogicException('This legacy database operation is not supported by the native boundary.');
    }

    /**
     * Execute a stored procedure and return any results
     *
     * @param  string $name              string that identifies the function to execute
     * @param  mixed  $params            array that contains the paramaters to pass the stored proc
     * @param  mixed  $types             array that contains the types of the columns in
     *                                   the result set
     * @param  mixed  $result_class      string which specifies which result class to use
     * @param  mixed  $result_wrap_class string which specifies which class to wrap results in
     * @return mixed  a result handle or MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function executeStoredProc($name, $params = NULL, $types = NULL, $result_class = TRUE, $result_wrap_class = FALSE) {
        throw new LogicException('This legacy database operation is not supported by the native boundary.');
    }

    /**
     * Return string to call a variable with the current timestamp inside an SQL statement
     * There are three special variables for current date and time:
     * - CURRENT_TIMESTAMP (date and time, TIMESTAMP type)
     * - CURRENT_DATE (date, DATE type)
     * - CURRENT_TIME (time, TIME type)
     *
     * @return string to call a variable with the current timestamp
     * @access public
     */
    public function now() {
        return date('Y-m-d H:i:s');
    }

    /**
     * list all tables in the current database
     *
     * @return mixed  data array on success, a MDB2 error on failure
     * @access public
     */
    public function listDbTables() {
        $rows = $this->nativeQuery("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        return $rows === false ? false : array_map(fn($row) => reset($row), $rows);
    }

    /**
     * Execute the specified query, fetch all the rows of the result set into
     * a two dimensional array and then frees the result set.
     *
     * @param   string  the SELECT query statement to be executed.
     * @param   array   optional array argument that specifies a list of
     *       expected datatypes of the result set columns, so that the eventual
     *       conversions may be performed. The default list of datatypes is
     *       empty, meaning that no conversion is performed.
     * @param   int     how the array data should be indexed
     * @param   bool    if set to TRUE, the $all will have the first
     *       column as its first dimension
     * @param   bool    used only when the query returns exactly
     *       two columns. If TRUE, the values of the returned array will be
     *       one-element arrays instead of scalars.
     * @param   bool    if TRUE, the values of the returned array is
     *       wrapped in another array.  If the same key value (in the first
     *       column) repeats itself, the values will be appended to this array
     *       instead of overwriting the existing values.
     *
     * @return  mixed   MDB2_OK or data array on success, a MDB2 error on failure
     *
     * @access  public
     */
    private function _queryAll($query, $types = array()) {
        return $this->nativeQuery($query);
    }

    private function _queryRow($query) {
        return $this->queryRow($query);
    }

    public function queryRow($query) {
        $rows = $this->nativeQuery($query);
        return $rows === false ? false : ($rows[0] ?? null);
    }

    /**
     * Method to generate a substring query that is database independent
     *
     * @param string $value Field/Value that will be substring'ed
     * @param int $postion Starting position for substring
     * @param int $length Length of substring to capture
     *
     * @return string.
     */
    public function getSubstring($value, $postion, $length = NULL) {
        $identifier = $this->_db->quoteIdentifier($value);
        if (filter_var($postion, FILTER_VALIDATE_INT) === false
            || ($length !== null && (filter_var($length, FILTER_VALIDATE_INT) === false || $length < 0))) {
            throw new InvalidArgumentException('Invalid substring bounds.');
        }
        return 'SUBSTRING(' . $identifier . ', ' . (int)$postion . ($length === null ? '' : ', ' . (int)$length) . ')';
    }

}

?>
