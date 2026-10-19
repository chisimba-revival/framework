<?php
/** Real default ACL regression; writes only a disposable fixture directory. */
if (PHP_SAPI !== 'cli') { exit(2); }
$app = realpath($argv[1] ?? dirname(__DIR__, 2) . '/app');
require $app . '/core_modules/config/classes/configurationfile.php';
$base = sys_get_temp_dir() . '/chisimba-config-acl-' . bin2hex(random_bytes(8));
mkdir($base, 0700);
try {
    $process = proc_open(['setfacl', '-m', 'd:u::rwx,d:g::rwx,d:o::---', $base], [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    if (proc_close($process) !== 0) { throw new RuntimeException('Default ACL setup failed; setfacl is required.'); }
    $writer = new ChisimbaConfigurationFile();
    $writer->replace($base . '/new.ini', 'private fixture', null);
    foreach (['new.ini', 'new.ini.lock'] as $file) {
        clearstatcache(true, $base . '/' . $file);
        if ((fileperms($base . '/' . $file) & 0777) !== 0600) { throw new RuntimeException('Default ACL defeated private file mode.'); }
    }
    chmod($base . '/new.ini', 0640);
    $writer->replace($base . '/new.ini', 'replacement fixture', 'private fixture');
    if ((fileperms($base . '/new.ini') & 0777) !== 0640) { throw new RuntimeException('Existing mode was not retained.'); }
    echo "PASS: inherited ACL cannot broaden new configuration/lock access; existing mode retained\n";
} finally {
    foreach (glob($base . '/*') as $file) { unlink($file); }
    rmdir($base);
}
