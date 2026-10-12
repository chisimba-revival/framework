<?php
/** Prevent migrated libraries from re-entering the deployable bundle. */
$root = dirname(__DIR__, 2);
foreach (array_merge(json_decode(file_get_contents(__DIR__ . '/retired-pear-paths.json'), true, 512, JSON_THROW_ON_ERROR), ['Config.php', 'Config', 'Event', 'Translation2.php', 'Translation2', 'I18Nv2.php', 'I18Nv2',
    'PHPUnit2', 'Benchmark', 'HTML/TagCloud.php', 'HTML/BBCodeParser.php', 'HTML/BBCodeParser', 'Image/Graph.php', 'Image/Graph']) as $path) {
    if (file_exists($root . '/app/lib/pear/' . $path)) throw new RuntimeException('Retired runtime dependency: ' . $path);
}
foreach (['app/lib/Auth/OpenID/MDB2Store.php', 'app/core_modules/security/classes/Auth/OpenID/MDB2Store.php'] as $path) {
    if (file_exists($root . '/' . $path)) throw new RuntimeException('Unused OpenID MDB2 adapter returned: ' . $path);
}
if (file_exists($root . '/app/tests')) {
    throw new RuntimeException('Historical web test harness returned to application');
}
if (file_exists($root . '/app/core_modules/utilities/classes/graph_class_inc.php')) {
    throw new RuntimeException('Retired graph wrapper returned');
}
foreach (['app/installer/pearinstall.php', 'pearpacker.php'] as $path) {
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/' . $path) . ' 2>&1', $output, $status);
    if ($status !== 64) throw new RuntimeException('Retired operator did not refuse: ' . $path);
}
echo "PASS retired runtime packages absent; obsolete PEAR operators refuse before bootstrap\n";
