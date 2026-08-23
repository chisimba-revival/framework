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
    $worker = $engine->getObject('communicationworker', 'communications');
    $summary = $worker->run((int) $limit);
    if (!is_array($summary)) {
        throw new RuntimeException('Communications worker returned an invalid summary.');
    }
    echo json_encode($summary, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Communications worker failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
?>
