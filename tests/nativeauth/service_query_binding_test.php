<?php
$GLOBALS['kewl_entry_point_run'] = true;
/** Untrusted lookup/search values must reach the prepared database boundary. */
class dbTable {
    public $calls = [];
    public $rows = [];
    public function selectPrepared($sql, array $values = []) { $this->calls[] = [$sql, $values]; return $this->rows; }
    public function getArray($sql) { throw new RuntimeException('Unexpected interpolated lookup'); }
}
$root = dirname(__DIR__, 2) . '/app/core_modules/security/classes/';
require $root . 'userservice_class_inc.php';
require $root . 'identityservice_class_inc.php';
require $root . 'permissionservice_class_inc.php';
function boundValue($service, $needle) {
    [$sql, $values] = $service->calls[count($service->calls)-1];
    if (str_contains($sql, $needle) || !in_array($needle, $values, true)) throw new RuntimeException('Value was not bound');
}
$users = new userservice();
$users->listUsers("O'Bird"); boundValue($users, "%O'Bird%");
if (count($users->calls[0][1]) !== 4) throw new RuntimeException('Missing search bindings');
if (!$users->usernameAvailable("O'Bird")) throw new RuntimeException('Unexpected match');
boundValue($users, "O'Bird");
$users->rows = [['userid'=>'taken']];
if ($users->usernameAvailable("O'Bird")) throw new RuntimeException('Existing username accepted');
$identity = new identityservice();
if ($identity->permissionUserIdForUser("x' OR '1'='1") !== null) throw new RuntimeException('Missing identity did not fail closed');
boundValue($identity, "x' OR '1'='1");
$permission = new permissionservice();
$permission->areaIdForName('chisimba', "x' OR '1'='1");
if ($permission->calls) boundValue($permission, "x' OR '1'='1");
$permission->areaIdForName('chisimba', 'toolbar'); boundValue($permission, 'toolbar');
echo "PASS prepared search/identity/permission values, quoted names and duplicate denial\n";
