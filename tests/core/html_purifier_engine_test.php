<?php
/** Exercise the real engine sanitisation method without site configuration or a database. */
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$app = dirname(__DIR__, 2) . '/app';
$temporary = sys_get_temp_dir() . '/html-engine-' . bin2hex(random_bytes(6));
mkdir($temporary . '/config', 0700, true);
file_put_contents($temporary . '/config/dbdetails_inc.php', '<?php');
file_put_contents($temporary . '/lucene.php', '<?php');
$cwd = getcwd();
$includePath = get_include_path();
chdir($temporary);
set_include_path($temporary . PATH_SEPARATOR . $app);
$GLOBALS['kewl_entry_point_run'] = true;
define('KEWL_DB_DSN', 'mysql://fixture:fixture@invalid/fixture');
try {
    require $app . '/classes/core/engine_class_inc.php';
    $engine = (new ReflectionClass(engine::class))->newInstanceWithoutConstructor();
    $result = $engine->purifyArray([7 => '<p>Bird<script>bad()</script></p>', 'nested' => ['<b onclick="bad()">key</b>' => '<img src="/bird.jpg" onerror="bad()">', 'number' => 42]], true);
    if ($result !== [7 => '<p>Bird</p>', 'nested' => ['number' => 42, '<b>key</b>' => '<img src="/bird.jpg" alt="bird.jpg" />']]) {
        throw new RuntimeException('Nested sanitisation mismatch: ' . var_export($result, true));
    }
    if ($engine->purifier !== ChisimbaHtmlSanitizer::purifier()) throw new RuntimeException('Engine bypassed shared service');
    echo "PASS: real engine uses shared purifier; nested values and keys sanitised, numeric values and keys preserved\n";
} finally {
    chdir($cwd);
    set_include_path($includePath);
    unlink($temporary . '/config/dbdetails_inc.php');
    unlink($temporary . '/lucene.php');
    rmdir($temporary . '/config');
    rmdir($temporary);
}
