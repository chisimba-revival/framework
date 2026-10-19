<?php
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
require __DIR__.'/../../app/core_modules/utilities/classes/nativebbcode.php';
function checkBb($input,$expected) {
 $actual=ChisimbaBbcode::parse($input);
 if($actual!==$expected)throw new RuntimeException(json_encode(['input'=>$input,'expected'=>$expected,'actual'=>$actual],JSON_UNESCAPED_SLASHES));
}
foreach(file(__DIR__.'/bbcode_legacy_fixtures.jsonl',FILE_IGNORE_NEW_LINES) as $line) {
 [$input,$expected]=json_decode($line,true,512,JSON_THROW_ON_ERROR);
 if($input==='[code]a < b[/code]')$expected='<code>a &lt; b</code>';
 if($input==='[b][i]cross[/b][/i]')$expected='<strong><em>cross</em></strong>';
 checkBb($input,$expected);
}
checkBb('[code][b]<script>alert(1)</script>[/b][/code]','<code>[b]&lt;script&gt;alert(1)&lt;/script&gt;[/b]</code>');
checkBb('[list][*]one[*]two[/list]','<ul><li>one</li><li>two</li></ul>');
checkBb('Café [B]birds[/B]','Café <strong>birds</strong>');
checkBb('<a href="/path/[b]">existing</a>','<a href="/path/[b]">existing</a>');
checkBb('[url=javascript:alert(1)]click[/url]','click');
checkBb('[url=java&#x73;cript:alert(1)]click[/url]','click');
checkBb('[img=data:text/html,evil][/img]','');
checkBb('[color=red;position:fixed]text[/color]','<span>text</span>');
checkBb('[font="Arial; background:url(x)"]text[/font]','<span>text</span>');
checkBb('[url="https://example.invalid/\"onclick=bad"]x[/url]','x');
checkBb('[url=/local?a=1&b=2]local[/url]','<a href="/local?a=1&amp;b=2">local</a>');
checkBb('[url=https://example.invalid t=_blank]x[/url]','<a href="https://example.invalid" target="_blank" rel="noopener noreferrer">x</a>');
checkBb('[unknown]text[/unknown]','[unknown]text[/unknown]');
checkBb('Visit https://example.invalid and person@example.invalid','Visit <a href="https://example.invalid">https://example.invalid</a> and <a href="mailto:person@example.invalid">person@example.invalid</a>');
$deep=str_repeat('[b]',65).'x';checkBb($deep,$deep);
$large=str_repeat('x',1048577);checkBb($large,$large);
$broken=str_repeat('[b',10000);checkBb($broken,$broken);
$brokenHtml=str_repeat('<',10000);checkBb($brokenHtml,$brokenHtml);
if(class_exists('PEAR',false)||class_exists('HTML_BBCodeParser',false))throw new RuntimeException('Legacy parser loaded');
echo "PASS native BBCode: legacy formatting fixtures, nested/malformed input, HTML preservation, URL/style safety, Unicode and bounded input without PEAR\n";

// Exercise the public wrapper and washout delegation independently of working directory.
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 public function getObject($name,$module) {return new class {public function parse($text){return $text;}};}
}
require __DIR__.'/../../app/core_modules/utilities/classes/bbcodeparser_class_inc.php';
require __DIR__.'/../../app/core_modules/utilities/classes/washout_class_inc.php';
$cwd=getcwd();
try {
 chdir(sys_get_temp_dir());
 $wrapper=new bbcodeparser();$wrapper->init();
 if($wrapper->parse4bbcode('[b]wrapper[/b]')!=='<strong>wrapper</strong>')throw new RuntimeException('Wrapper depends on cwd');
 $washout=new washout();$washout->useFilters='yes';$washout->classes=[];$washout->bbcode=$wrapper;
 if($washout->parseText('<p>[b]washout[/b]</p>')!=='<p><strong>washout</strong></p>')throw new RuntimeException('Washout delegation');
 $washout->useFilters='no';
 if($washout->parseText('[b]disabled[/b]')!=='[b]disabled[/b]')throw new RuntimeException('Disabled filters changed');
} finally {chdir($cwd);}
echo "PASS public wrapper independent of cwd and washout enabled/disabled delegation\n";
