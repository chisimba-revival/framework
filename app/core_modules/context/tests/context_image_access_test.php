<?php
$GLOBALS['kewl_entry_point_run'] = true;
set_error_handler(static function ($n, $s) { throw new ErrorException($s, 0, $n); });
class ChisimbaObject { public $objConfig; function getObject($name, $module = '') { return $GLOBALS['objects'][$name]; } }
require dirname(__DIR__, 2) . '/filemanager/classes/filedelivery_class_inc.php';
require dirname(__DIR__) . '/classes/contextimage_class_inc.php';
function verify($ok, $why) { if (!$ok) throw new RuntimeException($why); }
$root = sys_get_temp_dir() . '/chisimba-image-' . bin2hex(random_bytes(8));
mkdir($root); mkdir($root . '/public'); mkdir($root . '/secure');
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j2ioAAAAASUVORK5CYII=');
file_put_contents($root . '/secure/test.png', $png);
$objects['altconfig'] = new class($root) {
    function __construct(private $root) {}
    function getcontentBasePath() { return $this->root . '/public/'; }
    function getcontentPath() { return '/files/'; }
};
$objects['dbsysconfig'] = new class($root) { function __construct(private $root) {} function getValue($a, $b) { return $this->root . '/secure'; } };
$objects['dbfile'] = new class { public $path = 'test.png'; function getFile($id) { return ['id' => $id, 'path' => $this->path]; } };
$objects['filereadpolicy'] = new class { public $allowed = true; function mayRead($file) { return $this->allowed; } };
$objects['filedelivery'] = new filedelivery();
$objects['mkdir'] = new class { function mkdirs($path) { return is_dir($path) || mkdir($path); } };
$objects['cleanurl'] = new class { function cleanUpUrl($url) { return $url; } };
try {
    $image = new contextimage(); $image->init();
    verify($image->setContextImage('course', 'file'), 'authorised secure image can become featured image');
    verify(file_get_contents($root . '/public/contextimage/course.png') === $png, 'exact image bytes');
    $objects['filereadpolicy']->allowed = false;
    verify(!$image->setContextImage('course', 'foreign'), 'unreadable file denied');
    verify(is_file($root . '/public/contextimage/course.png'), 'denial preserves existing image');
    $objects['filereadpolicy']->allowed = true; $objects['dbfile']->path = '../secure/test.png';
    verify(!$image->setContextImage('course', 'traversal'), 'traversal denied');
    foreach (['../course', [], '', 'a/b'] as $code) {
        verify(!$image->removeContextImage($code), 'invalid removal scope');
        verify(!$image->getContextImage($code), 'invalid read scope');
    }
    verify($image->removeContextImage('course') && !is_file($root . '/public/contextimage/course.png'), 'valid removal');
} finally {
    foreach (glob($root . '/public/contextimage/*') ?: [] as $f) unlink($f);
    if (is_dir($root . '/public/contextimage')) rmdir($root . '/public/contextimage');
    unlink($root . '/secure/test.png'); rmdir($root . '/secure'); rmdir($root . '/public'); rmdir($root);
}
echo "PASS featured-image access, secure storage, traversal denial, retained image and removal\n";
