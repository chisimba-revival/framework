<?php
/** No archive extraction or network: check retirement and retained gzip behaviour. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
error_reporting(E_ALL);
set_error_handler(static function ($n, $s, $f, $l) { throw new ErrorException($s, 0, $n, $f, $l); });
$root = dirname(__DIR__, 2);
$modules = $argv[1] ?? dirname($root) . '/modules';
$zend = $modules . '/zend/resources';
if (!is_dir($zend)) throw new RuntimeException('Modules checkout required');
foreach ([$root . '/app/lib/pear/Archive', $zend . '/Zend/Filter/Compress/Tar.php'] as $path) {
    if (file_exists($path)) throw new RuntimeException('Retired TAR component returned: ' . $path);
}
set_include_path($zend . PATH_SEPARATOR . get_include_path());
require 'Zend/Filter/Compress.php';
require 'Zend/Filter/Decompress.php';
$input = "Synthetic gzip fixture: café\n";
$compress = new Zend_Filter_Compress();
$decompress = new Zend_Filter_Decompress();
if ($decompress->filter($compress->filter($input)) !== $input) throw new RuntimeException('Default Gz roundtrip failed');
if (class_exists('Archive_Tar', false) || class_exists('PEAR', false)) throw new RuntimeException('Retained compression loaded PEAR');
echo "PASS TAR adapter/library absent; default Zend Gz roundtrip preserved without PEAR\n";
