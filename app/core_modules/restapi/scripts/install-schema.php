<?php
/**
 * Apply the REST API module's own table schema, guarded and repeatable.
 *
 * TokenAuthService used to create tbl_rest_refresh_tokens and
 * tbl_rest_api_keys from its constructor. That meant every ordinary page
 * request could run DDL, which the architecture rules forbid, and it hid the
 * fact that the tables had been defined with the wrong column type: user_id
 * was INT while a Chisimba identifier is tbl_users.userid, a varchar(25).
 * Under STRICT_TRANS_TABLES any account whose userid is not numeric was
 * rejected with ERROR 1366 during login, which is how the defect surfaced.
 *
 * The definitions now live beside the module as sql/*.sql, which is also where
 * the Module Catalogue looks for module table definitions, and restapi/register
 * .conf already declares both tables as module-owned. This script reuses those
 * definitions through the installer's own DbHandler::createTables() and
 * createTableIndex(), rather than hand-writing DDL, so the applied schema
 * cannot drift from the declared one.
 *
 * For each table: create it when absent, and when it already exists only widen
 * user_id if it is not already a character column. MODIFY preserves existing
 * rows, so no data is dropped, and the script is safe to rerun. Nothing here
 * runs during a page request.
 *
 * Usage:
 *   php core_modules/restapi/scripts/install-schema.php [appdir]
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}

$app = rtrim($argv[1] ?? dirname(__DIR__, 3), '/') . '/';

if (!is_file($app . 'classes/core/engine_class_inc.php')) {
    throw new RuntimeException('Pass the Chisimba application directory');
}

chdir($app);

$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['QUERY_STRING'] = '';

// installer/dbhandlers/dbhandler.inc rewrites this to locate the bundled PEAR
// libraries, stripping the installer entry point from the path. Under the web
// installer it is .../installer/index.php; here the engine entry point plays
// that role. dbhandler.inc and dbhandlermanager.inc both append
// "$SCRIPT_FILENAME minus installer/index.php + lib/pear/" to the include path,
// so the bundled MDB2 and its mysqli driver resolve exactly as they do under
// the web installer.
$_SERVER['SCRIPT_FILENAME'] = $app . 'installer/index.php';

$GLOBALS['kewl_entry_point_run'] = true;

require_once 'classes/core/engine_class_inc.php';

$engine = new engine();

// DbHandler reads its connection details from these two session keys, which is
// how the installer itself reaches it. They must be assigned *after* the engine
// has booted: the engine replaces $_SESSION wholesale when it starts, so values
// written before that are discarded and DbHandler would receive an empty DSN.
$_SESSION['sys_root'] = $app;

if (!defined('KEWL_DB_DSN')) {
    throw new RuntimeException(
        'KEWL_DB_DSN is not defined, so the database is unknown. Boot the engine '
        . 'so config/dbdetails_inc.php is loaded first.'
    );
}

$_SESSION['dsn'] = KEWL_DB_DSN;

// The installer defines this before it loads dbhandler.inc; reuse the same value
// so the bundled PEAR path resolves identically here.
if (!defined('INSTALL_DIR')) {
    define('INSTALL_DIR', $app . 'installer');
}

require_once $app . 'installer/dbhandlers/dbhandler.inc';

if (!class_exists('DbHandler')) {
    throw new RuntimeException('The installer database handler could not be loaded');
}

$handler = new DbHandler();
$conn = &$handler->getDbHandler($_SESSION['dsn']);
$db = $engine->getDbObj();

/**
 * Whether a table exists.
 *
 * Uses the engine's own connection rather than a second one, so this check and
 * the subsequent DDL share the same connection and the same transaction
 * boundary.
 */
$tableExists = function ($tableName) use ($db) {
    $result = $db->query('SHOW TABLES LIKE ' . $db->quote($tableName));

    if (MDB2::isError($result)) {
        throw new RuntimeException(
            'Could not inspect table ' . $tableName . ': ' . $result->getMessage()
        );
    }

    return $result->numRows() > 0;
};

/**
 * The declared SQL type of a column, lowercased, or null when absent.
 *
 * information_schema is used rather than SHOW COLUMNS because SHOW COLUMNS FROM
 * does not accept a quoted table identifier, whereas every value here is a
 * bound literal and the table and column names come from the fixed definition
 * list above rather than from input.
 */
$columnType = function ($tableName, $columnName) use ($db) {
    // Placeholders must go through prepare() then execute($bindings): MDB2's
    // query() takes a complete SQL string only and silently ignores a second
    // bindings argument.
    $statement = $db->prepare(
        'SELECT DATA_TYPE FROM information_schema.COLUMNS'
        . ' WHERE TABLE_SCHEMA = DATABASE()'
        . ' AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );

    if (MDB2::isError($statement)) {
        throw new RuntimeException(
            'Could not inspect ' . $tableName . '.' . $columnName . ': '
            . $statement->getMessage()
        );
    }

    $result = $statement->execute(array($tableName, $columnName));

    if (MDB2::isError($result)) {
        $message = $result->getMessage();
        $statement->free();
        throw new RuntimeException(
            'Could not inspect ' . $tableName . '.' . $columnName . ': ' . $message
        );
    }

    $type = null;

    if ($result->numRows() > 0) {
        $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);

        // The driver lowercases information_schema column labels, so the key
        // arrives as 'data_type' even though the query spells it DATA_TYPE.
        // Normalise rather than assume one casing, otherwise a present column
        // reads as absent and the script refuses to touch the table.
        if (is_array($row)) {
            foreach ($row as $label => $value) {
                if (strtolower((string) $label) === 'data_type') {
                    $type = strtolower((string) $value);
                    break;
                }
            }
        }
    }

    $result->free();
    $statement->free();

    return $type;
};

// The module's own definitions, which restapi/register.conf already declares as
// module-owned tables.
$definitions = array(
    'tbl_rest_refresh_tokens' => 'core_modules/restapi/sql/tbl_rest_refresh_tokens.sql',
    'tbl_rest_api_keys' => 'core_modules/restapi/sql/tbl_rest_api_keys.sql',
    'tbl_rest_identity_epoch' => 'core_modules/restapi/sql/tbl_rest_identity_epoch.sql',
);

$created = 0;
$present = 0;
$widened = 0;

foreach ($definitions as $expectedTable => $path) {
    if (!is_file($path)) {
        throw new RuntimeException('Table definition is missing: ' . $path);
    }

    if (!$tableExists($expectedTable)) {
        // Each .sql file assigns $tablename, $fields and $options, and may
        // assign $name and $indexes. Including inside this closure keeps them
        // local instead of leaking across iterations, and the initialisation
        // covers definitions that declare no indexes.
        $load = function () use ($path) {
            $tablename = null;
            $fields = null;
            $options = null;
            $name = null;
            $indexes = null;
            $tableIndexes = null;

            include $path;

            return array($tablename, $fields, $options, $name, $indexes, $tableIndexes);
        };

        list($tableName, $fields, $options, $name, $indexes, $tableIndexes) = $load();

        if ($tableName !== $expectedTable) {
            throw new RuntimeException(
                'Definition ' . $path . ' declares ' . var_export($tableName, true)
                . ' but was expected to declare ' . $expectedTable
            );
        }

        if (!$conn->createTables($tableName, $fields, $options)) {
            throw new RuntimeException('Could not create table ' . $tableName);
        }

        if ($indexes !== null && $indexes !== false) {
            if (!$conn->createTableIndex($tableName, $name, $indexes)) {
                throw new RuntimeException(
                    'Table ' . $tableName . ' was created but its indexes failed'
                );
            }
        }

        if ($tableIndexes !== null && $tableIndexes !== false) {
            foreach ($tableIndexes as $indexName => $indexDefinition) {
                if (!$conn->createTableIndex($tableName, $indexName, $indexDefinition)) {
                    throw new RuntimeException(
                        'Table ' . $tableName . ' was created but its index '
                        . $indexName . ' failed'
                    );
                }
            }
        }

        echo 'created: ' . $tableName . PHP_EOL;
        $created++;
        continue;
    }

    $type = $columnType($expectedTable, 'user_id');

    if ($type === null) {
        throw new RuntimeException(
            'Table ' . $expectedTable . ' exists but has no user_id column, so this '
            . 'script cannot confirm the schema matches the module definition'
        );
    }

    // Only widen when the column is still numeric. Any numeric type here is the
    // defect this script exists to correct.
    //
    // The test deliberately enumerates the numeric types instead of looking for
    // a character prefix. "varchar" contains "char" but not at offset 0, so a
    // strpos($type, 'char') prefix test never matched it and the ALTER re-ran on
    // every invocation, which is neither idempotent nor an honest report of what
    // the script did.
    $numericTypes = array(
        'tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint',
        'decimal', 'numeric', 'float', 'double', 'real', 'bit',
    );

    if (in_array($type, $numericTypes, true)) {
        // The table name is an identifier, not a literal, so it is delimited
        // with backticks. It comes from the fixed definition list above, never
        // from input; $db->quote() would emit a string literal and MySQL would
        // reject the statement.
        $result = $db->query(
            'ALTER TABLE `' . $expectedTable . '`'
            . ' MODIFY user_id VARCHAR(25) NOT NULL'
        );

        if (MDB2::isError($result)) {
            throw new RuntimeException(
                'Could not widen ' . $expectedTable . '.user_id: ' . $result->getMessage()
            );
        }

        echo 'widened: ' . $expectedTable . '.user_id from ' . $type . ' to varchar(25)'
            . PHP_EOL;
        $widened++;
        continue;
    }

    echo 'present: ' . $expectedTable . ' (user_id ' . $type . ')' . PHP_EOL;
    $present++;
}

echo 'created ' . $created . ' table(s); widened ' . $widened
    . ' column(s); ' . $present . ' already correct.' . PHP_EOL;
