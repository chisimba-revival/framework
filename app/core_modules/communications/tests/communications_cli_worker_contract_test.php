<?php
$script = file_get_contents(dirname(__DIR__) . '/scripts/run_outbox_worker.php');
$checks = array(
    'CLI only' => str_contains($script, "PHP_SAPI !== 'cli'"),
    'real engine boot' => str_contains($script, "new engine()"),
    'canonical worker' => str_contains($script, "getObject('communicationworker', 'communications')"),
    'bounded batch' => str_contains($script, "'max_range' => 100")
        && str_contains($script, '$worker->run((int) $limit)'),
    'machine-readable result' => str_contains($script, 'json_encode($summary'),
    'failure exit' => str_contains($script, 'exit(1)'),
);
foreach ($checks as $name => $passed) {
    if (!$passed) { fwrite(STDERR, "FAIL: {$name}\n"); exit(1); }
}
echo 'OK: ' . count($checks) . " communications CLI worker checks\n";
?>
