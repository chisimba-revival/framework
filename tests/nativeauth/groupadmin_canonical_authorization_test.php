<?php
/**
 * Source contract for canonical group-administration authorization.
 *
 * @author Derek Keats
 */

function p103Assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

$root = dirname(__FILE__, 3);
$controller = file_get_contents(
    $root . '/app/core_modules/groupadmin/controller.php'
);
$reader = file_get_contents(
    $root . '/app/core_modules/groupadmin/classes/groupadminreadservice_class_inc.php'
);
$authorization = file_get_contents(
    $root . '/app/core_modules/groupadmin/classes/groupadminauthorizationservice_class_inc.php'
);

p103Assert(
    strpos($controller, "getObject('groupadminauthorizationservice', 'groupadmin')") !== false
        && strpos($reader, "getObject('groupadminauthorizationservice', 'groupadmin')") !== false,
    'controller and read service share the canonical group-admin authorization boundary'
);
p103Assert(
    strpos($controller, "getObject('user', 'security')") === false
        && strpos($reader, "getObject('user', 'security')") === false
        && strpos($controller, '->isAdmin()') === false
        && strpos($reader, '->isAdmin()') === false,
    'migrated consumers no longer construct or call the legacy user facade'
);
p103Assert(
    strpos($authorization, 'new NativeSessionService($this)') !== false
        && strpos($authorization, '->isAuthenticated()') !== false
        && strpos($authorization, '->getUserId()') !== false,
    'authorization obtains authentication and current identity from the canonical session service'
);
p103Assert(
    strpos($authorization, "groupIdForName('Site Admin')") !== false
        && strpos($authorization, '->isGroupMember(') !== false,
    'Site Admin status is decided by canonical group membership'
);
p103Assert(
    substr_count($authorization, 'return false;') >= 3,
    'missing authentication, identity, and baseline group data fail closed'
);
p103Assert(
    substr_count($controller, '$this->assertAdministrator();') >= 2
        && strpos($reader, '$this->assertAdministrator();') !== false,
    'controller and read-service security gates remain enforced'
);

echo "All group-admin canonical authorization source contracts passed.\n";
