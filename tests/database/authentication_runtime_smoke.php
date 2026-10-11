<?php
/** Read-only installed composition gate. No login, session or user changes. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_AUTH_DB_SMOKE') !== '1') exit(64);
chdir($argv[1]);
$GLOBALS['kewl_entry_point_run'] = true;
$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['HTTP_HOST'] = 'chisimba.test:8445';
$_SERVER['SCRIPT_NAME'] = '/ch/index.php';
$_SERVER['QUERY_STRING'] = '';
require 'classes/core/engine_class_inc.php';
$engine = new engine();
$auth = $engine->getObject('auth_database', 'security');
if ($auth->verifyCredentials('fixture-absent-' . bin2hex(random_bytes(16)), 'fixture-only') !== false) {
    throw new RuntimeException('Unknown identity unexpectedly authenticated');
}
if (!class_exists('DbTableNativeDatabaseAdapter', false) || class_exists('Mdb2NativeDatabaseAdapter', false)) {
    throw new RuntimeException('Authentication bypassed the dbTable adapter');
}
echo "PASS: installed authentication composition rejects absent identity through dbTable\n";
$stack = $engine->getObject('nativeauthwebcomposition', 'security')->build();
if (!$stack['factors'] instanceof DbTableMfaRepository || $stack['factors']->findActiveTotpForUser('fixture-absent-' . bin2hex(random_bytes(16))) !== null) {
    throw new RuntimeException('MFA composition/read failed');
}
$engine->getObject('abuseprotectionprovider', 'abuseprotection');
echo "PASS: installed MFA/remembered-login/abuse composition uses dbTable repositories\n";
