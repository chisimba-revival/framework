<?php
/** Static contracts for reusable, person-centred operational presence. */
$source = file_get_contents(dirname(__DIR__) . '/classes/loggedinusers_class_inc.php');
$controller = file_get_contents(dirname(__DIR__) . '/controller.php');
$checks = array(
    'online count is per person' => strpos($source, 'COUNT(DISTINCT userid)') !== false,
    'active people are reusable' => strpos($source, 'function getActiveUsers()') !== false,
    'latest session supplies scope' => strpos($source, 'ORDER BY logged.whenlastactive DESC') !== false,
    'context presence is reusable' => strpos($source, 'function getActiveUsersInContext($contextCode)') !== false,
    'any active context session counts once' => strpos($source, "WHERE logged.coursecode = '{\$contextCode}'") !== false,
    'native authentication records login history' => strpos($controller, "getObject('userloginhistory', 'security')->addHistoryEntry(\$userId)") !== false,
    'native authentication starts presence' => strpos($controller, "getObject('loggedinusers', 'security')->insertLogin(\$userId)") !== false,
    'native logout clears presence immediately' => strpos($controller, "getObject('loggedinusers', 'security')->doLogout(\$userId)") !== false,
);
$failed = array_keys(array_filter($checks, static function ($passed) { return !$passed; }));
if ($failed) {
    fwrite(STDERR, 'Failed: ' . implode(', ', $failed) . PHP_EOL);
    exit(1);
}
echo 'Operational presence contracts passed (' . count($checks) . ').' . PHP_EOL;
