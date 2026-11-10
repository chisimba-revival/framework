<?php
/** Explicit CLI equivalent of the normal Module Catalogue REST API upgrade. */
if (PHP_SAPI !== 'cli') exit(64);
$app = rtrim($argv[1] ?? dirname(__DIR__, 3), '/');
if (!is_file($app . '/classes/core/engine_class_inc.php')) throw new RuntimeException('Pass the Chisimba application directory');
chdir($app);
$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['QUERY_STRING'] = '';
$GLOBALS['kewl_entry_point_run'] = true;
require_once 'classes/core/engine_class_inc.php';
$engine = new engine();
$engine->getObject('restschema', 'restapi')->upgrade();
echo "REST API schema checked and upgraded.\n";
