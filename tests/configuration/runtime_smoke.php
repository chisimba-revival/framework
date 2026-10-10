<?php
/** Read-only configured local runtime check. No application records are written. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_EVENT_SMOKE') !== '1') {
    fwrite(STDERR, "Set CHISIMBA_EVENT_SMOKE=1 and pass the configured local app directory.\n");
    exit(2);
}
$app = $argv[1] ?? '';
if (!is_dir($app)) { throw new RuntimeException('Local application directory required'); }
chdir($app);
$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['HTTP_HOST'] = 'chisimba.test:8445';
$_SERVER['SCRIPT_NAME'] = '/ch/index.php';
$_SERVER['QUERY_STRING'] = '';
$GLOBALS['kewl_entry_point_run'] = true;
require 'classes/core/engine_class_inc.php';
$engine = new engine();
if (get_class($engine->eventDispatcher) !== 'ChisimbaEventDispatcher') {
    throw new RuntimeException('Engine did not select native events');
}
$language = $engine->getObject('language', 'language');
if ($language->eventDispatcher !== $engine->eventDispatcher) {
    throw new RuntimeException('Framework object dispatcher identity changed');
}
if (!is_string($language->languageText('word_home', 'system', 'Home'))) {
    throw new RuntimeException('Translation lookup failed');
}
$db = $engine->getDbObj();
$result = $db->query("SELECT 'event-smoke' AS marker");
if (PEAR::isError($result) || $result->fetchRow(MDB2_FETCHMODE_ASSOC)['marker'] !== 'event-smoke') {
    throw new RuntimeException('Database smoke query failed');
}
$seen = array();
$engine->eventDispatcher->addObserver(static function ($event) use (&$seen) {
    $seen[] = $event->getNotificationInfo();
}, 'event-smoke');
$engine->eventDispatcher->post($language, 'event-smoke', array('fixture' => true), false);
if ($seen !== array(array('fixture' => true))) { throw new RuntimeException('Engine event delivery failed'); }
foreach (get_included_files() as $file) {
    if (preg_match('~/Event/(Dispatcher|Notification)\.php$~', $file)) {
        throw new RuntimeException('Legacy PEAR event file was loaded');
    }
}
if (class_exists('Event_Dispatcher', false) || class_exists('Event_Notification', false)) {
    throw new RuntimeException('Legacy event classes were loaded');
}
if (class_exists('Config', false) || class_exists('Config_Container', false)) { throw new RuntimeException('PEAR Config loaded during engine boot'); }
if ($engine->getObject('config','config') !== $engine->getObject('altconfig','config')
    || !($engine->newObject('config','config') instanceof altconfig)) { throw new RuntimeException('Historical configuration service alias failed'); }
$catalogue=$engine->getObject('catalogueconfig','modulecatalogue');
if (count($catalogue->getModulelist('all')) < 1) { throw new RuntimeException('Native catalogue read failed'); }
if (class_exists('Config', false) || class_exists('Config_Container', false)) { throw new RuntimeException('PEAR Config loaded by catalogue'); }
echo "PASS: native configuration/catalogue (no PEAR Config), engine, shared dispatcher, language, read-only database, event delivery; no PEAR Event files/classes loaded\n";
