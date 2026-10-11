<?php
/** Run with normal PHP and with php -n to cover a missing PDO MySQL driver. */
if (PHP_SAPI !== 'cli') exit(64);
define('INSTALL_DIR', dirname(__DIR__, 2) . '/app/installer');
require INSTALL_DIR . '/template.inc';
require INSTALL_DIR . '/steps/versioncheck.inc';
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
$root = sys_get_temp_dir() . '/chisimba-requirements-' . bin2hex(random_bytes(6));
mkdir($root, 0700);
try {
    $_SESSION = ['sys_root' => $root];
    $_POST = ['pear_path' => '/unrelated/system/pear', 'ignore_errors' => '1',
        'confirm_step' => 'VersionCheck', 'next' => '1'];
    $action = new RuntimeLibrariesCheckAction();
    check(!$action->processAction(), 'missing libraries must block installation');
    check($_SESSION['pear_path'] === $root . '/lib/pear/', 'posted path must not override bundle');
    check(count($action->errors) === 9, 'every required bundled file is checked');
    $files = ['PEAR.php','Archive/Tar.php','XML/Serializer.php','XML/Unserializer.php',
        'XML/Parser.php','XML/Util.php','HTML/BBCodeParser.php','Mail.php','Mail/mime.php'];
    foreach ($files as $file) {
        $path = $root . '/lib/pear/' . $file;
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0700, true);
        file_put_contents($path, "<?php\n");
    }
    check($action->processAction(), 'complete bundle should pass without MDB2 or PEAR registry');
    ob_start(); $action->paintAction(); $html = ob_get_clean();
    check(!str_contains($html, 'name="pear_path"'), 'no editable PEAR path');
    unlink($root . '/lib/pear/XML/Parser.php');
    check(!(new VersionCheck())->process(), 'forged confirmation cannot bypass a missing dependency');
    $php = new PHPCheckAction(); $php->processAction();
    $pdo = class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers(), true);
    check($php->required_settings['mysqlclient']['available'] === $pdo, 'actual PDO driver detection');
    if (!$pdo) check(!$php->success, 'missing PDO must fail closed');
    check(!class_exists('MDB2', false), 'checks do not load MDB2');
    echo 'PASS installer requirements; PDO MySQL ' . ($pdo ? 'available' : 'missing and blocked') . "\n";
} finally {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $entry) $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    rmdir($root);
}
