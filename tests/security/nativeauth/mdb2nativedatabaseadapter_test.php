<?php
define('MDB2_PREPARE_RESULT', 1);
define('MDB2_PREPARE_MANIP', 2);
define('MDB2_FETCHMODE_ASSOC', 2);

require_once dirname(__FILE__) . '/../../../app/core_modules/security/classes/'
    . 'nativeauth/mdb2nativedatabaseadapter.php';

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(
            STDERR,
            "FAIL: {$message}\nExpected: "
            . var_export($expected, true)
            . "\nActual: "
            . var_export($actual, true)
            . "\n"
        );
        exit(1);
    }

    echo "PASS: {$message}\n";
}

function assertTrueValue($actual, $message)
{
    assertSameValue(true, (bool) $actual, $message);
}

class AdapterFakeResult
{
    private $row;
    private $rows;
    public $freed = false;
    public $fetchMode = null;

    public function __construct($row, array $rows)
    {
        $this->row = $row;
        $this->rows = $rows;
    }

    public function fetchRow($mode)
    {
        $this->fetchMode = $mode;
        return $this->row;
    }

    public function fetchAll($mode)
    {
        $this->fetchMode = $mode;
        return $this->rows;
    }

    public function free()
    {
        $this->freed = true;
    }
}

class AdapterFakeStatement
{
    private $returnValue;
    public $parameters = null;
    public $freed = false;

    public function __construct($returnValue)
    {
        $this->returnValue = $returnValue;
    }

    public function execute($parameters)
    {
        $this->parameters = $parameters;
        return $this->returnValue;
    }

    public function free()
    {
        $this->freed = true;
    }
}

class AdapterFakeConnection
{
    public $sql;
    public $types;
    public $mode;
    public $statement;

    public function __construct($statement)
    {
        $this->statement = $statement;
    }

    public function prepare($sql, $types = null, $mode = null)
    {
        $this->sql = $sql;
        $this->types = $types;
        $this->mode = $mode;
        return $this->statement;
    }
}

$row = array('userid' => '7', 'username' => 'admin');
$result = new AdapterFakeResult($row, array($row));
$statement = new AdapterFakeStatement($result);
$connection = new AdapterFakeConnection($statement);
$adapter = new Mdb2NativeDatabaseAdapter($connection);

$actual = $adapter->fetchOne(
    'SELECT userid, username FROM tbl_users WHERE username = ?',
    array('admin')
);

assertSameValue($row, $actual, 'fetchOne returns an associative row');
assertSameValue(
    array('admin'),
    $statement->parameters,
    'fetchOne binds parameters'
);
assertSameValue(
    MDB2_PREPARE_RESULT,
    $connection->mode,
    'fetchOne uses result prepare mode'
);
assertTrueValue($statement->freed, 'fetchOne frees the statement');
assertTrueValue($result->freed, 'fetchOne frees the result');

$result = new AdapterFakeResult(null, array($row));
$statement = new AdapterFakeStatement($result);
$connection = new AdapterFakeConnection($statement);
$adapter = new Mdb2NativeDatabaseAdapter($connection);

assertSameValue(
    array($row),
    $adapter->fetchAll(
        'SELECT userid, username FROM tbl_users WHERE isactive = ?',
        array('1')
    ),
    'fetchAll returns associative rows'
);
assertSameValue(
    array('1'),
    $statement->parameters,
    'fetchAll binds parameters'
);

$statement = new AdapterFakeStatement(3);
$connection = new AdapterFakeConnection($statement);
$adapter = new Mdb2NativeDatabaseAdapter($connection);

assertSameValue(
    3,
    $adapter->execute(
        'UPDATE tbl_users SET logins = logins + 1 WHERE userid = ?',
        array('7')
    ),
    'execute returns affected-row count'
);
assertSameValue(
    MDB2_PREPARE_MANIP,
    $connection->mode,
    'execute uses manipulation prepare mode'
);
assertSameValue(
    array('7'),
    $statement->parameters,
    'execute binds parameters'
);
assertTrueValue($statement->freed, 'execute frees the statement');

$result = new AdapterFakeResult(false, array());
$statement = new AdapterFakeStatement($result);
$connection = new AdapterFakeConnection($statement);
$adapter = new Mdb2NativeDatabaseAdapter($connection);

assertSameValue(
    null,
    $adapter->fetchOne('SELECT userid FROM tbl_users WHERE userid = ?', array('x')),
    'fetchOne normalises no row to null'
);

echo "All MDB2 native database adapter tests passed.\n";
