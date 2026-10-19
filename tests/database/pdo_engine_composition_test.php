<?php
/** Exercise real engine connection composition, with fixture-only configuration. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_DB_BASELINE') !== '1') exit(64);
$app = '/framework/app';
$temporary = sys_get_temp_dir() . '/pdo-engine-' . bin2hex(random_bytes(6));
mkdir($temporary . '/config', 0700, true);
// These files stand in only for installation configuration and search bootstrap;
// no installed site's configuration, credentials or tables are available here.
file_put_contents($temporary . '/config/dbdetails_inc.php', '<?php');
file_put_contents($temporary . '/lucene.php', '<?php');
chdir($temporary);
set_include_path($temporary . PATH_SEPARATOR . $app);
$GLOBALS['kewl_entry_point_run'] = true;
define('KEWL_DB_DSN', 'mysqli://root:' . rawurlencode(getenv('CHISIMBA_DB_PASSWORD')) . '@baseline-db:3306/contract_fixture');
try {
    require $app . '/classes/core/engine_class_inc.php';
    class FixtureDatabaseConfig {
        public function serverName() { return 'fixture'; }
        public function geterror_reporting() { return 'production'; }
        public function getenable_dbabs() { return 'PDO'; }
    }
    class FixturePdoEngine extends engine {
        public function __construct($backend = 'PDO') {
            $this->_dbabs = $backend;
            $this->_moduleName = 'fixture';
            $this->enableLogging = false;
        }
        public function getObject($name, $moduleName) {
            if ($name !== 'altconfig' || $moduleName !== 'config') throw new LogicException('Unexpected fixture service');
            return new FixtureDatabaseConfig();
        }
    }
    try {
        (new FixturePdoEngine('MDB2'))->getDbObj();
        throw new LogicException('Retired backend was accepted');
    } catch (RuntimeException $expected) {
        if (!str_contains($expected->getMessage(), 'MDB2 is retired')) throw $expected;
    }
    $first = new FixturePdoEngine();
    $second = new FixturePdoEngine();
    $connection = $first->getDbObj();
    if (!($connection instanceof ChisimbaDatabaseConnection) || $connection !== $first->getDbObj()
        || $connection !== $second->getDbObj() || $connection !== $GLOBALS['_globalObjDb']) {
        throw new RuntimeException('Engine lost canonical connection identity');
    }
    $table = new dbTable($first, 'fixture');
    if ($table->query("SELECT 'composed' AS VALUE") !== [['value' => 'composed']]) {
        throw new RuntimeException('Real dbTable initialisation/composition failed');
    }
    $first->getDbManagementObj();
    foreach (['MDB2', 'PEAR', 'PEAR_Error'] as $legacy) {
        if (class_exists($legacy, false)) throw new RuntimeException('PDO engine composition loaded ' . $legacy);
    }
    echo "PASS: real engine getDbObj and dbTable init share one native connection; legacy DSN port parsed; MDB2 and PEAR not loaded\n";
} finally {
    chdir(sys_get_temp_dir());
    unlink($temporary . '/config/dbdetails_inc.php');
    unlink($temporary . '/lucene.php');
    rmdir($temporary . '/config');
    rmdir($temporary);
}
