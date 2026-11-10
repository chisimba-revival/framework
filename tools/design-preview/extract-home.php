<?php
if (PHP_SAPI !== 'cli' || count($argv) !== 3) exit(64);
$d=new DOMDocument();libxml_use_internal_errors(true);
$d->loadHTML('<?xml encoding="UTF-8">'.file_get_contents($argv[1]));
$x=new DOMXPath($d);
$grid=$x->query('//*[contains(concat(" ",normalize-space(@class)," ")," chisimba-publication-card-grid ")]')->item(0);
if (!$grid) throw new RuntimeException('No public cards found');
foreach (iterator_to_array($x->query('.//script|.//form|.//iframe',$grid)) as $n) $n->parentNode->removeChild($n);
foreach ($x->query('.//*',$grid) as $n) {
 foreach (iterator_to_array($n->attributes) as $attr) if (str_starts_with(strtolower($attr->name),'on')) $n->removeAttribute($attr->name);
 foreach (['href','src'] as $attr) if ($n->hasAttribute($attr)) {
  $url=$n->getAttribute($attr);
  if (!preg_match('~^https?://~',$url)) $n->setAttribute($attr,'https://learnthebirds.com/'.ltrim($url,'/'));
 }
}
file_put_contents($argv[2],json_encode([
 ['title'=>'Learn-the-Birds','html'=>'<p>Local design preview using published Learn-the-Birds content. Webinar links open the live public site.</p><p><a href="index.php?module=simpleblog&amp;action=view">News and stories</a> · <a href="index.php?module=security&amp;action=login">Editor login</a></p>'],
 ['title'=>'Webinars','html'=>$d->saveHTML($grid)]
],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
