<?php
/**
 * Apply security module tables that the installer never created.
 *
 * Four tables shipped in core_modules/security/sql/ were missing from
 * installer/steps/databasecreate.inc, so no installation has ever created
 * them. The canonical login flow reads them unconditionally:
 * GuardedLoginApplicationService::begin() resolves the MFA policy context,
 * which queries tbl_auth_mfa_enrolments through Mdb2MfaRepository. A missing
 * table there does not raise a readable error, it raises
 * "Call to undefined method MDB2_Error::execute()" and the login dies.
 *
 * databasecreate.inc has been corrected, so a fresh install now creates these
 * tables. This script exists for installations that predate that fix. It
 * reuses the module's own .sql definitions and the installer's own
 * DbHandler::createTables()/createTableIndex(), rather than hand-writing DDL,
 * so the applied schema cannot drift from what a fresh install produces.
 *
 * Idempotent and repeatable: a table that already exists is left untouched, so
 * re-running never drops or rewrites existing data. Nothing here runs during
 * an ordinary page request; this is an explicit post-install step, invoked as:
 *
 *   php core_modules/security/scripts/install-missing-schema.php [appdir]
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
// so the bundled MDB2 and its mysqli driver resolve exactly as they do under the
// web installer.
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
        'KEWL_DB_DSN is not defined, so the database is unknown. Boot the '
        . 'engine so config/dbdetails_inc.php is loaded first.'
    );
}

$_SESSION['dsn'] = KEWL_DB_DSN;

// The installer defines this before it loads dbhandler.inc; reuse the same
// value so the bundled PEAR path resolves identically here.
if (!defined('INSTALL_DIR')) {
    define('INSTALL_DIR', $app . 'installer');
}

require_once $app . 'installer/dbhandlers/dbhandler.inc';

if (!class_exists('DbHandler')) {
    throw new RuntimeException(
        'The installer database handler could not be loaded from '
        . $app . 'installer/dbhandlers/dbhandler.inc'
    );
}

/**
 * Report whether a table is already present.
 *
 * Uses the engine's own connection rather than a second one, so this check and
 * the subsequent CREATE share the same connection and the same transaction
 * boundary.
 */
$tableExists = function ($db, $tableName) {
    $result = $db->query('SHOW TABLES LIKE ' . $db->quote($tableName));
    if (MDB2::isError($result)) {
        throw new RuntimeException(
            'Could not inspect the schema: ' . $result->getMessage()
        );
    }
    return $result->numRows() > 0;
};

$handler = new DbHandler();
$conn = &$handler->getDbHandler($_SESSION['dsn']);
$db = $engine->getDbObj();

/**
 * The tables the installer omitted, in the order databasecreate.inc now lists
 * them so a fresh install and this repair agree.
 */
$definitions = array(
    'core_modules/security/sql/tbl_auth_mfa_enrolments.sql',
    'core_modules/security/sql/tbl_auth_mfa_recovery_codes.sql',
    'core_modules/security/sql/tbl_auth_persistent_logins.sql',
    'core_modules/security/sql/tbl_useractivity.sql',
);

$created = 0;
$present = 0;

foreach ($definitions as $definition) {
    $path = $app . $definition;

    if (!is_file($path)) {
        throw new RuntimeException('Table definition is missing: ' . $definition);
    }

    // Each .sql file assigns $tablename, $fields and $options, and may assign
    // $name and $indexes. Including inside this closure keeps them local
    // instead of leaking across iterations, and isset() covers definitions
    // that declare no indexes.
    $load = function () use ($path) {
        $tablename = null;
        $fields = null;
        $options = null;
        $name = null;
        $indexes = null;
        include $path;
        return array($tablename, $fields, $options, $name, $indexes);
    };

    list($tableName, $fields, $options, $name, $indexes) = $load();

    if ($tableExists($db, $tableName)) {
        echo 'present: ' . $tableName . PHP_EOL;
        $present++;
        continue;
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

    echo 'created: ' . $tableName . PHP_EOL;
    $created++;
}

echo PHP_EOL;
echo 'created ' . $created . ' table(s); ' . $present . ' already present.' . PHP_EOL;
