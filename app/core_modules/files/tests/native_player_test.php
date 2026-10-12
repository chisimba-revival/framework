<?php
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject {
    public function getObject($name, $module = null) {
        if ($name === 'nativeplayer') return new nativeplayer();
        if ($name === 'language') return new class { public function languageText($key,$module) { return $key; } };
        throw new RuntimeException('Unexpected dependency: '.$name);
    }
}
$app = dirname(__DIR__, 3);
require dirname(__DIR__).'/classes/nativeplayer_class_inc.php';
function verify($ok, $message) { if (!$ok) throw new RuntimeException($message); }
$p = new nativeplayer();
foreach (['javascript:alert(1)','data:video/mp4,evil','//outside.invalid/x','https://user:pass@example.com/v.mp4',"/evil\n.mp4",''] as $url) {
    verify($p->video($url) === '' && $p->audio($url) === '', 'Unsafe URL accepted');
}
$url = '/index.php?module=filemanager&action=download&id=private123&token=keep';
$html = $p->video($url, '640" onload="bad()', 360);
verify(str_contains($html, '<video') && str_contains($html, 'controls preload="none" playsinline'), 'Native video controls');
verify(str_contains($html, htmlspecialchars($url, ENT_QUOTES, 'UTF-8')), 'Authorised route/query changed');
verify(!str_contains($html, 'onload') && !str_contains($html, 'autoplay'), 'Unsafe dimensions or autoplay');
foreach (['flv','swf','wmv','avi','mpg'] as $extension) {
    $out = $p->video('/original.'.$extension);
    verify(!str_contains($out,'<video') && str_contains($out,'href="/original.'.$extension.'"'), 'Original legacy file lost');
}
require $app.'/core_modules/filemanager/classes/fileembed_class_inc.php';
$embed = new fileembed();
foreach (['showMP4','showWebm','showOggVideo','showQuicktime'] as $method) {
    verify(str_contains($embed->$method('/clip.mp4',640,360),'<video'), $method);
}
verify(str_contains($embed->showSoundPlayer('/song.mp3'),'<audio'), 'Filemanager audio');
verify(!str_contains($embed->showFLV($url),'<video'), 'FLV route must not claim playable');
foreach (['parse4mp3','parse4flv','parse4embed'] as $class) require $app.'/core_modules/filters/classes/'.$class.'_class_inc.php';
$mp3=new parse4mp3();
verify(str_contains($mp3->parse('[MP3]/song.mp3[/MP3] [MP3:/other.mp3]'),'<audio'), 'MP3 shortcode');
verify(str_contains($mp3->parse('[MP3]<a href="/song.mp3">/song.mp3</a>[/MP3]'),'src="/song.mp3"'), 'Pasted MP3 link');
$flv=new parse4flv();$flv->init();
$out=$flv->parse('[FLV:/bad] [FLV: url=/clip.mp4?a=1&b=2,width=400] [FLV: height=200]');
verify(str_contains($out,'src="/clip.mp4?a=1&amp;b=2"') && str_contains($out,'[FLV: height=200]'), 'FLV parameters/query or state leak');
$filter=new parse4embed();
foreach (['mp3','mov','wmv','swf','mpeg','avi'] as $ext) {
    $out=$filter->parse('[EMBED]<a href="/clip.'.$ext.'?id=7">Clip</a>[/EMBED]');
    verify(!preg_match('/<object|<embed|<applet|\.swf["\x27] /i',$out) && str_contains($out,'/clip.'.$ext.'?id=7'), 'Legacy embed '.$ext);
}
echo "PASS: native filemanager/shortcode media, safe URLs, preserved private routes, legacy links, query strings and no plugin markup\n";
