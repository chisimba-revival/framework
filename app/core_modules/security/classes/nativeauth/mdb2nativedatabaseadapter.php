<?php
require_once dirname(__FILE__) . '/nativedatabaseadapterinterface.php';

/**
 * Parameterised MDB2 bridge for native Chisimba repositories.
 *
 * This adapter contains database mechanics only. It does not perform
 * authentication, session management, authorisation, redirects, or UI work.
 */
class Mdb2NativeDatabaseAdapter implements NativeDatabaseAdapterInterface
{
    private $connection;

    public function __construct($connection)
    {
        if (!is_object($connection)
            || !method_exists($connection, 'prepare')) {
            throw new InvalidArgumentException(
                'MDB2 connection must provide prepare().'
            );
        }

        $this->connection = $connection;
    }

    public function fetchOne($sql, array $parameters = array())
    {
        $result = $this->executePreparedResult($sql, $parameters);

        if (!is_object($result) || !method_exists($result, 'fetchRow')) {
            $this->freeResult($result);
            throw new RuntimeException(
                'MDB2 result does not provide fetchRow().'
            );
        }

        $row = $result->fetchRow($this->associativeFetchMode());
        $this->assertNotDatabaseError($row, 'fetchRow');
        $this->freeResult($result);

        if ($row === false || $row === null || $row === array()) {
            return null;
        }

        if (!is_array($row)) {
            throw new RuntimeException(
                'MDB2 fetchRow() did not return an associative array.'
            );
        }

        return $row;
    }

    public function fetchAll($sql, array $parameters = array())
    {
        $result = $this->executePreparedResult($sql, $parameters);

        if (!is_object($result) || !method_exists($result, 'fetchAll')) {
            $this->freeResult($result);
            throw new RuntimeException(
                'MDB2 result does not provide fetchAll().'
            );
        }

        $rows = $result->fetchAll($this->associativeFetchMode());
        $this->assertNotDatabaseError($rows, 'fetchAll');
        $this->freeResult($result);

        if ($rows === false || $rows === null) {
            return array();
        }

        if (!is_array($rows)) {
            throw new RuntimeException(
                'MDB2 fetchAll() did not return an array.'
            );
        }

        return $rows;
    }

    public function execute($sql, array $parameters = array())
    {
        $statement = $this->prepare(
            $sql,
            $this->manipulationPrepareMode(),
            $parameters
        );

        try {
            $affected = $statement->execute(array_values($parameters));
            $this->assertNotDatabaseError($affected, 'execute');

            if (!is_int($affected) && !ctype_digit((string) $affected)) {
                throw new RuntimeException(
                    'MDB2 manipulation did not return an affected-row count.'
                );
            }

            return (int) $affected;
        } finally {
            $this->freeStatement($statement);
        }
    }

    private function executePreparedResult($sql, array $parameters)
    {
        $statement = $this->prepare(
            $sql,
            $this->resultPrepareMode(),
            $parameters
        );

        try {
            $result = $statement->execute(array_values($parameters));
            $this->assertNotDatabaseError($result, 'execute');

            return $result;
        } finally {
            $this->freeStatement($statement);
        }
    }

    /**
     * Declare the bind types so MDB2 does not fall back to a null type.
     *
     * MDB2 records the types given to prepare() and looks each one up per
     * placeholder when binding. A placeholder with no recorded type resolves to
     * null, which reaches MDB2\Driver\Datatype::mapPrepareDatatype(null) and
     * indexes $options['datatype_map'][null], raising a PHP 8.1 deprecation on
     * every statement. Quoting already infers the type from the value, so
     * declaring it here changes nothing except supplying the bind type
     * character MDB2 was otherwise leaving out.
     *
     * @param array $parameters The bound values, in placeholder order
     * @return array MDB2 type names, positionally matching $parameters
     */
    private function parameterTypes(array $parameters): array
    {
        $types = array();

        foreach ($parameters as $value) {
            // Only the names MDB2 recognises may be declared here. Passing an
            // unknown one makes execute() fail with "not supported", so a null
            // or any other value is declared as text, which is how MDB2 would
            // have bound it anyway once quoting inferred the type.
            if (is_int($value)) {
                $types[] = 'integer';
            } elseif (is_bool($value)) {
                $types[] = 'boolean';
            } elseif (is_float($value)) {
                $types[] = 'float';
            } else {
                $types[] = 'text';
            }
        }

        return $types;
    }

    private function prepare($sql, $mode, array $parameters = array())
    {
        $sql = trim((string) $sql);
        if ($sql === '') {
            throw new InvalidArgumentException('SQL must not be empty.');
        }

        $statement = $this->connection->prepare($sql, $this->parameterTypes($parameters), $mode);
        $this->assertNotDatabaseError($statement, 'prepare');

        if (!is_object($statement)
            || !method_exists($statement, 'execute')) {
            throw new RuntimeException(
                'MDB2 prepare() did not return an executable statement.'
            );
        }

        return $statement;
    }

    private function assertNotDatabaseError($value, $operation)
    {
        if ($this->isDatabaseError($value)) {
            $message = method_exists($value, 'getMessage')
                ? $value->getMessage()
                : 'unknown database error';

            throw new RuntimeException(
                'MDB2 ' . $operation . ' failed: ' . $message
            );
        }
    }

    private function isDatabaseError($value)
    {
        if (class_exists('PEAR', false)
            && method_exists('PEAR', 'isError')) {
            return PEAR::isError($value);
        }

        return is_object($value)
            && is_a($value, 'PEAR_Error');
    }

    private function freeStatement($statement)
    {
        if (is_object($statement) && method_exists($statement, 'free')) {
            $statement->free();
        }
    }

    private function freeResult($result)
    {
        if (is_object($result) && method_exists($result, 'free')) {
            $result->free();
        }
    }

    private function resultPrepareMode()
    {
        return defined('MDB2_PREPARE_RESULT')
            ? MDB2_PREPARE_RESULT
            : 1;
    }

    private function manipulationPrepareMode()
    {
        return defined('MDB2_PREPARE_MANIP')
            ? MDB2_PREPARE_MANIP
            : 2;
    }

    private function associativeFetchMode()
    {
        return defined('MDB2_FETCHMODE_ASSOC')
            ? MDB2_FETCHMODE_ASSOC
            : 2;
    }
}
