<?php
/** Strict boundary checks for country lists and empty/leading-text BBCode. */
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
$app=$argv[1]??dirname(__DIR__,2).'/app';
set_include_path($app.'/lib/pear'.PATH_SEPARATOR.get_include_path());
require_once 'PEAR.php';
require_once 'I18Nv2/Country.php';
require_once 'HTML/BBCodeParser.php';
$country=new I18Nv2_Country('en','iso-8859-1');
if($country->getLanguage()!=='en'||empty($country->codes['ZA']))throw new RuntimeException('Country list did not load');
$parser=new HTML_BBCodeParser();
$parser->HTML_BBCodeParser();
foreach(array('','Plain text','Plain [b]bold[/b]') as $text){
 $parser->setText($text);$parser->parse();$out=$parser->getParsed();
 if($text===''&&$out!=='')throw new RuntimeException('Empty parser output');
 if($text==='Plain text'&&$out!==$text)throw new RuntimeException('Plain text changed');
 if($text==='Plain [b]bold[/b]'&&!str_contains($out,'bold'))throw new RuntimeException('Mixed text lost');
}
echo "PASS: country list and BBCode empty/leading text under strict diagnostics\n";
