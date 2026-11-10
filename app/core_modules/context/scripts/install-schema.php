<?php
/**
 * Apply the context module's own table definitions to an installation whose
 * context tables were never created.
 *
 * dbcontext::init() used to run a conditional ALTER TABLE on every
 * instantiation to add tbl_context.use_sections. That placed DDL on ordinary
 * page requests, which framework/AGENTS.md forbids, and it produced a
 * "no such table" error on every request for an installation that predates it.
 * The runtime ALTER has been removed because core_modules/context/sql/
 * tbl_context.sql already declares use_sections, so the column ships with the
 * table and no migration is needed. What is needed is to create the tables,
 * which is what this script does, once, from the module's own definitions.
 *
 * The definitions are applied through the installer's DbHandler rather than
 * hand written DDL so the applied schema cannot drift from what module
 * registration would produce. Existing tables are left untouched, so the
 * script is idempotent and never drops or rewrites data, and it never runs
 * during an ordinary page request.
 *
 * Usage:
 *   php core_modules/context/scripts/install-schema.php [appdir]
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

// Use the installer entry point for CLI bootstrap.
$_SERVER['SCRIPT_FILENAME'] = $app . 'installer/index.php';

$GLOBALS['kewl_entry_point_run'] = true;
require_once 'classes/core/engine_class_inc.php';

$engine = new engine();

// DbHandler reads its connection details from these two session keys, which is
// how the installer itself reaches it. They must be assigned *after* the engine
// has booted: the engine replaces $_SESSION wholesale when it starts, so
// values written before that are discarded and DbHandler would receive an
// empty DSN.
$_SESSION['sys_root'] = $app;

if (!defined('KEWL_DB_DSN')) {
    throw new RuntimeException(
        'KEWL_DB_DSN is not defined, so the database is unknown. Boot the '
        . 'engine so config/dbdetails_inc.php is loaded first.'
    );
}

$_SESSION['dsn'] = KEWL_DB_DSN;

// Shared installer bootstrap.
if (!defined('INSTALL_DIR')) {
    define('INSTALL_DIR', $app . 'installer');
}

require_once $app . 'installer/dbhandlers/dbhandler.inc';

if (!class_exists('DbHandler')) {
    throw new RuntimeException(
        'DbHandler is unavailable, so the installer table builder cannot be '
        . 'reused.'
    );
}

$handler = new DbHandler();
$conn = &$handler->getDbHandler($_SESSION['dsn']);
$db = $engine->getObject('modules', 'modulecatalogue');
require_once $app . 'classes/core/chisimbadatabaseschema.php';
$schema = new ChisimbaDatabaseSchema($db);

/**
 * Reports whether a table already exists.
 *
 * Uses the engine's own connection rather than a second one, so this check and
 * the subsequent CREATE share the same connection and the same transaction
 * boundary.
 */
$tableExists = fn($tableName) => $schema->tableExists($tableName);

$sqlDir = $app . 'core_modules/context/sql/';

// Ordered parents-first so that a table whose definition names a foreign key
// or index against a parent table is created after that parent. The order is
// also the order the module's own sql directory reads alphabetically, with the
// parent tables pulled forward.
$order = array(
    'tbl_context',
    'tbl_context_nodes',
    'tbl_context_parentnodes',
    'tbl_context_page_content',
    'tbl_context_blocks',
    'tbl_context_file',
    'tbl_context_filedata',
    'tbl_context_learneroutcomes',
    'tbl_context_learneroutcomes_dependencies',
    'tbl_context_marketing',
    'tbl_context_nodes_has_tbl_context_page_content',
    'tbl_context_parentnodes_has_tbl_context',
    'tbl_context_sharednodes',
    'tbl_context_temp',
    'tbl_context_usernotes',
);

$created = 0;
$present = 0;

foreach ($order as $expectedTable) {
    $path = $sqlDir . $expectedTable . '.sql';

    if (!is_file($path)) {
        throw new RuntimeException('Table definition is missing: ' . $expectedTable);
    }

    if ($tableExists($expectedTable)) {
        echo 'present: ' . $expectedTable . PHP_EOL;
        $present++;
        continue;
    }

    // Each .sql file assigns $tablename, $fields and $options, and may assign
    // $name and $indexes. Including inside this closure keeps them local
    // instead of leaking across iterations, and isset() covers definitions that
    // declare no indexes. The index array is named $tableIndexes in the module
    // sql files but $indexes in the installer's loader, so both are accepted.
    $load = function ($definition) {
        $tablename = null;
        $fields = null;
        $options = null;
        $name = null;
        $indexes = null;
        $tableIndexes = null;

        include $definition;

        if ($indexes === null && $tableIndexes !== null) {
            $indexes = $tableIndexes;
        }

        return array($tablename, $fields, $options, $name, $indexes);
    };

    list($tableName, $fields, $tableOptions, $name, $indexes) = $load($path);

    if ($tableName !== $expectedTable) {
        throw new RuntimeException(
            'Definition for ' . $expectedTable . ' declares table '
            . var_export($tableName, true)
        );
    }

    if (!$conn->createTables($tableName, $fields, $tableOptions)) {
        throw new RuntimeException('Could not create table ' . $tableName);
    }

    if ($indexes !== null && $indexes !== false) {
        if (!$conn->createTableIndex($tableName, $name, $indexes)) {
            throw new RuntimeException(
                'Table ' . $tableName . ' was created but its indexes failed'
            );
        }
    }

    echo 'created: ' . $tableName . PHP_EOL;
    $created++;
}

echo 'created ' . $created . ' table(s); ' . $present . ' already present.' . PHP_EOL;