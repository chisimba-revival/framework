<?php
/** Opt-in disposable fixture: uses the shared installer runtime smoke's engine. */
if(PHP_SAPI!=='cli'||getenv('CHISIMBA_INSTALLER_FIXTURE')!=='1')exit(64);
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
require __DIR__.'/../installer/installed_runtime_smoke.php';
$parser=$engine->getObject('bbcodeparser','utilities');
if($parser->parse4bbcode('<p>[b]Café[/b]</p>')!=='<p><strong>Café</strong></p>')throw new RuntimeException('Installed formatting failed');
if(class_exists('PEAR',false)||class_exists('HTML_BBCodeParser',false))throw new RuntimeException('Legacy parser loaded');
echo "PASS installed native BBCode without PEAR\n";
