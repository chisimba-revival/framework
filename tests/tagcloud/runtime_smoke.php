<?php
/** Explicitly opted-in local runtime smoke; no application record writes. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_TAG_SMOKE') !== '1') { exit(2); }
$app = $argv[1] ?? '';
if (!is_dir($app)) { throw new RuntimeException('Configured local app directory required'); }
chdir($app);
$_SERVER['REQUEST_METHOD']='CLI'; $_SERVER['HTTP_HOST']='chisimba.test:8445';
$_SERVER['SCRIPT_NAME']='/ch/index.php'; $_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true;
require 'classes/core/engine_class_inc.php';
$engine = new engine();
$cloud = $engine->newObject('tagcloud', 'utilities');
$html = $cloud->buildCloud(array(array('name'=>'Local & fixture','url'=>'?module=filemanager','weight'=>1)));
if (strpos($html, 'Local &amp; fixture') === false || strpos($html, 'chisimba-tag-cloud') === false) {
    throw new RuntimeException('Runtime factory/render failed');
}
if (class_exists('HTML_TagCloud', false)) { throw new RuntimeException('PEAR tag cloud loaded'); }
$other = $engine->newObject('tagcloud', 'utilities');
if ($other->buildAll() !== '') { throw new RuntimeException('Independent clouds leak tags'); }
echo "PASS: real engine creates independent native tag clouds without PEAR\n";
