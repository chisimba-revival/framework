<?php
/** Two real processes race from the same revision; exactly one must commit. */
if (PHP_SAPI !== 'cli') { exit(2); }
$app = realpath($argv[1] ?? dirname(__DIR__, 2) . '/app');
$base = sys_get_temp_dir() . '/chisimba-config-race-' . bin2hex(random_bytes(8));
mkdir($base, 0700);
$path = $base . '/config.xml';
file_put_contents($path, '<Settings><VALUE>original</VALUE></Settings>');
$worker = <<<'WORKER'
require $argv[1] . '/core_modules/config/classes/configurationdocument.php';
$document = new ChisimbaConfigurationDocument($argv[2], 'Settings');
fwrite(STDOUT, "ready\n"); fflush(STDOUT);
if (fgets(STDIN) !== "go\n") { exit(4); }
try {
    $document->save($document->fromArray(array('VALUE'=>$argv[3])));
    exit(0);
} catch (RuntimeException $e) {
    exit(str_contains($e->getMessage(), 'changed') ? 3 : 4);
}
WORKER;
$processes = array(); $channels = array();
try {
    foreach (array('first','second') as $value) {
        $process = proc_open(array(PHP_BINARY, '-r', $worker, $app, $path, $value), array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')), $pipes);
        if (!is_resource($process)) { throw new RuntimeException('Worker could not start.'); }
        $processes[] = $process; $channels[] = $pipes;
    }
    foreach ($channels as $pipes) {
        stream_set_timeout($pipes[1], 10);
        if (fgets($pipes[1]) !== "ready\n") { throw new RuntimeException('Worker did not become ready.'); }
    }
    foreach ($channels as $pipes) { fwrite($pipes[0], "go\n"); fclose($pipes[0]); }
    $codes = array();
    foreach ($processes as $index=>$process) {
        $pipes = $channels[$index];
        fclose($pipes[1]);
        $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
        $codes[] = proc_close($process);
        if ($errors !== '') { throw new RuntimeException('Worker emitted diagnostics.'); }
    }
    sort($codes);
    $document = new DOMDocument(); $document->load($path);
    $value = $document->getElementsByTagName('VALUE')->item(0)->textContent;
    if ($codes !== array(0,3) || !in_array($value, array('first','second'), true) || glob($base.'/.chisimba-config-*')) {
        throw new RuntimeException('Concurrent writers did not preserve one complete winning revision.');
    }
    echo "PASS: two concurrent processes, one complete commit, one rejected stale writer, no temporary files\n";
} finally {
    foreach ($channels as $pipes) { foreach ($pipes as $pipe) { if (is_resource($pipe)) { fclose($pipe); } } }
    foreach ($processes as $process) { if (is_resource($process)) { proc_terminate($process); proc_close($process); } }
    foreach (glob($base.'/*') as $file) { unlink($file); }
    rmdir($base);
}
