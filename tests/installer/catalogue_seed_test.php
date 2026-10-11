<?php
/** Exercise the actual catalogue XML loader and schema-name choice with fixture collaborators. */
if (PHP_SAPI !== 'cli') exit(64);
$GLOBALS['kewl_entry_point_run'] = true;
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
class dbTableManager {
    public $objConfig;
    public $createdIndexes = [];
    public function getObject($name, $module) { return new stdClass(); }
    public function newObject($name, $module) {
        return new class {
            public function tablelist() {}
            public function checktable($table) { return false; }
        };
    }
    public function createTable($name, $fields, $options) {}
    public function createTableIndex($table, $name, $definition) { $this->createdIndexes[] = $name; }
    public function errorCallback($message) { throw new RuntimeException($message); }
}
require dirname(__DIR__, 2) . '/app/core_modules/modulecatalogue/classes/modulesadmin_class_inc.php';
class SeedFixtureCatalogue extends modulesadmin {
    public function fixtureRepository($repository) { $this->objModules = $repository; }
}
$root = sys_get_temp_dir() . '/chisimba-seed-' . bin2hex(random_bytes(6));
mkdir($root . '/fixture/sql', 0700, true);
try {
    file_put_contents($root . '/fixture/sql/defaultdata.xml', '<?xml version="1.0" encoding="UTF-8"?><defaultdata><tbl_fixture><id>001</id><body>Café &amp; 日本語</body><empty></empty></tbl_fixture><tbl_fixture><id>002</id><body>Second row</body></tbl_fixture></defaultdata>');
    $catalogue = new SeedFixtureCatalogue();
    $catalogue->objConfig = new class($root) {
        public function __construct(private $root) {}
        public function getModulePath() { return $this->root . '/'; }
    };
    $repository = new class {
        public $rows = [];
        public function getRow($field, $id, $table) {
            if (!is_string($id)) throw new RuntimeException('XML object escaped the seed boundary');
            return $this->rows[$id] ?? false;
        }
        public function insert($row, $table) {
            foreach ($row as $value) if (!is_string($value)) throw new RuntimeException('Non-scalar seed');
            $this->rows[$row['id']] = $row;
            return true;
        }
    };
    $catalogue->fixtureRepository($repository);
    $catalogue->loadData('fixture');
    $catalogue->loadData('fixture');
    if (count($repository->rows) !== 2 || $repository->rows['001']['body'] !== 'Café & 日本語'
        || $repository->rows['001']['empty'] !== '') throw new RuntimeException('Seed data changed');
    $catalogue->objModFile = new class {
        public function findSqlFile($module, $table) {
            return dirname(__DIR__, 2) . '/app/core_modules/context/sql/tbl_context_parentnodes.sql';
        }
    };
    $method = new ReflectionMethod(modulesadmin::class, 'makeTable');
    $method->invoke($catalogue, 'tbl_context_parentnodes', 'context');
    if ($catalogue->createdIndexes !== ['tbl_context_parentnodes_FKIndex1']) {
        throw new RuntimeException('Declared schema index name was not preserved');
    }
    echo "PASS actual catalogue seed scalar conversion, Unicode, repeatability and declared index name\n";
} finally {
    unlink($root . '/fixture/sql/defaultdata.xml');
    rmdir($root . '/fixture/sql'); rmdir($root . '/fixture'); rmdir($root);
}
