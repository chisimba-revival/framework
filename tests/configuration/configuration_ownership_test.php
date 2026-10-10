<?php
/** Root-only container fixture: web user must not replace a root-owned file. */
if (PHP_SAPI !== 'cli' || !function_exists('posix_geteuid') || posix_geteuid() !== 0) {
    fwrite(STDERR, "Run this disposable ownership gate as root inside the local PHP container.\n"); exit(2);
}
$app = realpath($argv[1] ?? dirname(__DIR__, 2) . '/app');
$user = posix_getpwnam('www-data');
if (!$user) { throw new RuntimeException('www-data test identity missing.'); }
$base = sys_get_temp_dir().'/chisimba-config-owner-'.bin2hex(random_bytes(8));
mkdir($base,0770); chgrp($base,$user['gid']); chmod($base,0770);
$path=$base.'/config.xml'; $original='<Settings><VALUE>original</VALUE></Settings>';
file_put_contents($path,$original); chmod($path,0644);
$worker = <<<'WORKER'
if (!posix_setgid((int)$argv[3]) || !posix_setuid((int)$argv[4])) { exit(4); }
require $argv[1].'/core_modules/config/classes/configurationdocument.php';
$document=new ChisimbaConfigurationDocument($argv[2],'Settings');
try { $document->save($document->fromArray(array('VALUE'=>'changed'))); exit(5); }
catch (RuntimeException $e) { exit(str_contains($e->getMessage(),'ownership') ? 0 : 6); }
WORKER;
try {
    $process=proc_open(array(PHP_BINARY,'-r',$worker,$app,$path,(string)$user['gid'],(string)$user['uid']),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
    fclose($pipes[0]); $output=stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors=stream_get_contents($pipes[2]); fclose($pipes[2]); $code=proc_close($process);
    clearstatcache(true,$path);
    if ($code!==0 || $output!=='' || $errors!=='' || file_get_contents($path)!==$original || fileowner($path)!==0 || (fileperms($path)&0777)!==0644 || glob($base.'/.chisimba-config-*')) {
        throw new RuntimeException('Ownership failure did not preserve the original file and metadata.');
    }
    echo "PASS: real web-user ownership failure preserves root-owned bytes/mode and cleans temporary file\n";
} finally {
    foreach (glob($base.'/*') as $file) { unlink($file); } rmdir($base);
}
