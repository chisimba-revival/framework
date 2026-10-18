<?php
/**
 * Local-only persisted-role test. Creates one disposable user and role groups,
 * exercises real controller policies, and removes its exact fixtures in finally.
 * Authentication is tested separately over HTTP; this CLI test sets its own
 * isolated canonical session identity, never another browser's session.
 * @author Derek Keats
 */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_LOCAL_ROLE_TEST') !== '1'
    || !is_dir('/var/www/html/ch')) {
    throw new RuntimeException('Run explicitly inside the local development container');
}
chdir('/var/www/html/ch');
$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['HTTP_HOST'] = 'chisimba.test';
$_SERVER['SCRIPT_NAME'] = '/ch/index.php';
$_SERVER['PHP_SELF'] = '/ch/index.php';
$_SERVER['QUERY_STRING'] = '';
$GLOBALS['kewl_entry_point_run'] = true;
require 'classes/core/engine_class_inc.php';
$engine = new engine();
$users = $engine->getObject('userservice', 'security');
$identity = $engine->getObject('identityservice', 'security');
$groups = $engine->getObject('groupservice', 'groupadmin');
$user = $engine->getObject('user', 'security');
$context = $engine->getObject('dbcontext', 'context');
$sessions = $engine->getObject('nativeauthwebcomposition', 'security')->build()['sessions'];
$admin = $users->findByUsername('admin');
function verifyRole($ok, $label) {
    if (!$ok) { throw new RuntimeException($label); }
}
verifyRole(is_array($admin), 'Local administrator missing');
$sessions->establish($admin['userid']);
verifyRole($user->isAdmin(), 'Local administrator role missing');
$prefix = 'qatest' . bin2hex(random_bytes(5));
$created = null;
$permissionId = null;
$groupIds = array();
$memberships = array();
require 'core_modules/contextgroups/controller.php';
require 'core_modules/contextpermissions/controller.php';
try {
    $created = $engine->getObject('userprovisioningservice', 'security')->createLocalUser(
        array('userid' => $prefix, 'username' => $prefix, 'firstname' => 'Disposable',
            'surname' => 'Role test', 'emailaddress' => $prefix . '@example.invalid',
            'isactive' => 1, 'howcreated' => 'useradmin'), bin2hex(random_bytes(24))
    );
    verifyRole(!empty($created['ok']), 'Fixture provisioning failed');
    $permissionId = $identity->permissionUserIdForUser($created['userId']);
    foreach (array('Lecturers', 'Students') as $role) {
        $result = $groups->createNamespacedGroup($prefix, $role, 'Disposable role test');
        verifyRole(!empty($result['ok']), 'Fixture group creation failed');
        $groupIds[$role] = $result['groupId'];
    }
    $context->setSession('contextCode', $prefix);
    $sessions->establish($created['userId']);
    $permissions = new contextpermissions($engine, 'contextpermissions');
    foreach (array('outsider', 'Students', 'Lecturers', 'revoked') as $role) {
        foreach ($memberships as $groupId) {
            verifyRole($groups->removeMembership($groupId, $permissionId), 'Role removal failed');
        }
        $memberships = array();
        if (isset($groupIds[$role])) {
            $memberships[] = $groupIds[$role];
            verifyRole($groups->ensureMembership($groupIds[$role], $permissionId), 'Role grant failed');
        }
        $controller = new contextgroups($engine, 'contextgroups');
        verifyRole($controller->isValid('addusers') === ($role === 'Lecturers'), "$role membership gate");
        verifyRole(!$permissions->isValid('save'), "$role permission editor denied");
    }
    $sessions->establish($admin['userid']);
    $controller = new contextgroups($engine, 'contextgroups');
    verifyRole($controller->isValid('addusers'), 'Administrator membership gate');
    verifyRole($permissions->isValid('save'), 'Administrator permission editor');
} finally {
    $sessions->establish($admin['userid']);
    foreach ($memberships as $groupId) {
        verifyRole($groups->removeMembership($groupId, $permissionId), 'Cleanup membership failed');
    }
    foreach ($groupIds as $groupId) {
        $result = $groups->deleteGroup($groupId);
        verifyRole(!empty($result['ok']), 'Cleanup group failed');
    }
    if ($permissionId !== null) {
        verifyRole($identity->rollbackProvisionedIdentity($created['userId'], $permissionId), 'Cleanup identity failed');
    }
    if (!empty($created['ok'])) {
        $result = $users->rollbackProvisionedUser($created['userId'], $created['storageId']);
        verifyRole(!empty($result['ok']), 'Cleanup user failed');
        verifyRole($users->findByUserId($created['userId']) === null, 'Fixture user remains');
    }
    $sessions->destroy();
}
echo "PASS: persisted outsider/student/lecturer/revoked/admin controller policies; fixtures removed\n";
