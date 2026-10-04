<?php
/**
 * Define the REST API permission area and the rights its endpoints require.
 *
 * Rights are defined but never granted here: granting is an explicit
 * administrative act through the group services. Idempotent and repeatable.
 *
 * Usage: php core_modules/restapi/scripts/configure-service.php [appdir]
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}

$app = $argv[1] ?? dirname(__DIR__, 3);

if (!is_file($app . '/classes/core/engine_class_inc.php')) {
    throw new RuntimeException('Pass the Chisimba application directory');
}

chdir($app);

$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['QUERY_STRING'] = '';

$GLOBALS['kewl_entry_point_run'] = true;

require_once 'classes/core/engine_class_inc.php';

$engine = new engine();
$permissions = $engine->getObject('permissionservice', 'security');

/*
 * Keep this list in step with the requirePermission() calls in
 * core_modules/restapi/controller.php. An endpoint whose right is not defined
 * here denies for everyone, which is the intended fail-closed behaviour.
 */
$rights = [
    'users.view',
    'users.create',
    'users.edit',
    'users.delete',
    'modules.view',
    'modules.install',
    'modules.uninstall',
    'files.view',
    'files.upload',
    'files.delete',
];

$areaId = $permissions->ensureArea('chisimba', 'restapi');

if (!is_int($areaId) || $areaId < 1) {
    throw new RuntimeException('REST API permission area creation failed');
}

foreach ($rights as $right) {
    if (!$permissions->ensureRight($areaId, $right)) {
        throw new RuntimeException('Permission definition failed: ' . $right);
    }
}

echo 'REST API permission area and rights configured; no grants changed.' . PHP_EOL;
