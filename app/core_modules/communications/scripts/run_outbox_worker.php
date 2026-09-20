<?php
/** Process one bounded Communications outbox batch from the command line. */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This command is available only from the command line.\n");
    exit(64);
}

$siteRoot = dirname(__DIR__, 3);
chdir($siteRoot);
$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['QUERY_STRING'] = '';
$GLOBALS['kewl_entry_point_run'] = true;

require_once 'classes/core/engine_class_inc.php';

$limit = isset($argv[1]) ? filter_var($argv[1], FILTER_VALIDATE_INT, array(
    'options' => array('min_range' => 1, 'max_range' => 100),
)) : 20;
if ($limit === false) {
    fwrite(STDERR, "Batch size must be an integer from 1 to 100.\n");
    exit(64);
}

try {
    $engine = new engine();
    $scheduled = array();
    $catalogue = $engine->getObject('modules', 'modulecatalogue');
    if ($catalogue->checkIfRegistered('liveclass')) {
        try {
            $scheduled['liveclass'] = $engine->getObject('liveclassreminderservice', 'liveclass')->run((int) $limit);
        } catch (Throwable $scheduledFailure) {
            $scheduled['liveclass'] = array('failed' => 1, 'detail' => $scheduledFailure->getMessage());
        }
    }
    if ($catalogue->checkIfRegistered('webinar')) {
        try {
            $scheduled['webinar'] = $engine->getObject('webinarregistrationservice','webinar')->reminders();
        } catch (Throwable $scheduledFailure) {
            $scheduled['webinar'] = array('failed' => 1);
        }
    }
    if ($catalogue->checkIfRegistered('audience')) {
        try {
            if ($catalogue->checkIfRegistered('webinar')) {
                $scheduled['announcements'] = $engine->getObject('webinarannouncements', 'webinar')->run();
            }
            $scheduled['audience'] = $engine->getObject('audiencecampaigns', 'audience')->pump((int) $limit);
        } catch (Throwable $scheduledFailure) {
            $scheduled['audience'] = array('failed' => 1);
        }
    }
    $worker = $engine->getObject('communicationworker', 'communications');
    $summary = $worker->run((int) $limit);
    if (!is_array($summary)) {
        throw new RuntimeException('Communications worker returned an invalid summary.');
    }
    if ($scheduled) { $summary['scheduled'] = $scheduled; }
    echo json_encode($summary, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Communications worker failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
?>
