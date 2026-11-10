<?php
/**
 * Bootstrap the canonical initial administrator through the established
 * installer contract.
 *
 * This reuses InitialAdminProvisioningService::ensureInitialAdministrator(),
 * which is exactly what the Module Catalogue installer calls before and after
 * module registration (core_modules/modulecatalogue/controller.php). It reads
 * the canonical group manifest at installer/config/permission-groups.xml,
 * provisions the baseline groups through GroupService, the permission identity
 * through IdentityService, and grants every defined right to the Site Admin
 * group through PermissionService. No table is written directly.
 *
 * The canonical initial administrator is userid "1" with username "admin";
 * ensureInitialAdministrator() rejects any other userid by design.
 *
 * The installer seeds the account with a single character password. This
 * script always replaces it with an explicit password_hash() credential via
 * AuthenticationService and UserService::updatePasswordHash().
 *
 * Idempotent and repeatable: rerunning converges rather than duplicating.
 * No login session is created; normal authentication remains mandatory.
 *
 * Usage:
 *   php core_modules/security/scripts/bootstrap-administrator.php [appdir] [password]
 *
 * The password may also be supplied through the CHISIMBA_ADMIN_PASSWORD
 * environment variable so it need not appear in the process list. When
 * neither is supplied the script refuses to run, because leaving the
 * installer's one character password in place is not acceptable.
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}

$app = $argv[1] ?? dirname(__DIR__, 3);

if (!is_file($app . '/classes/core/engine_class_inc.php')) {
    throw new RuntimeException('Pass the Chisimba application directory');
}

$password = $argv[2] ?? (getenv('CHISIMBA_ADMIN_PASSWORD') ?: null);

if (!is_string($password) || trim($password) === '') {
    throw new RuntimeException(
        'Pass an administrator password as the second argument or set '
        . 'CHISIMBA_ADMIN_PASSWORD. The installer default of a single '
        . 'character password must not be left in place.'
    );
}

chdir($app);

$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['QUERY_STRING'] = '';

$GLOBALS['kewl_entry_point_run'] = true;

require_once 'classes/core/engine_class_inc.php';

$engine = new engine();

$provisioning = $engine->getObject('initialadminprovisioningservice', 'security');
$result = $provisioning->ensureInitialAdministrator('1');

if (!is_array($result) || empty($result['ok'])) {
    $code = is_array($result) && isset($result['code'])
        ? $result['code']
        : 'unknown_failure';
    throw new RuntimeException('Administrator bootstrap failed: ' . $code);
}

$userId = isset($result['userId']) ? (string) $result['userId'] : '1';

$users = $engine->getObject('userservice', 'security');
$authentication = $engine->getObject('authenticationservice', 'security');

$storedHash = $users->findByUserId($userId);

if (!is_array($storedHash) || !isset($storedHash['pass'])) {
    throw new RuntimeException('Administrator record is unreadable after bootstrap');
}

$newHash = $authentication->createPasswordHash($password);
$passwordResult = $users->updatePasswordHash($userId, $newHash);

if (!is_array($passwordResult) || empty($passwordResult['ok'])) {
    $code = is_array($passwordResult) && isset($passwordResult['code'])
        ? $passwordResult['code']
        : 'unknown_failure';
    throw new RuntimeException('Administrator password update failed: ' . $code);
}

$needsUpgrade = $authentication->passwordHashNeedsUpgrade($storedHash['pass']);

echo 'Initial administrator ready.' . PHP_EOL;
echo '  userid: ' . $userId . PHP_EOL;
echo '  permissionUserId: ' . (isset($result['permissionUserId'])
    ? (string) $result['permissionUserId'] : 'unknown') . PHP_EOL;
echo '  siteAdminGroupId: ' . (isset($result['groupId'])
    ? (string) $result['groupId'] : 'unknown') . PHP_EOL;
echo '  credential: password_hash()' . PHP_EOL;
echo '  replaced weak credential: ' . ($needsUpgrade ? 'yes' : 'no') . PHP_EOL;
