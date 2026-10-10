<?php
/** Native tag-cloud output/security and historical caller contracts. */
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject {}
require dirname(__DIR__, 2) . '/app/core_modules/utilities/classes/tagcloud_class_inc.php';
$checks = 0;
function tagCheck($condition, $message) {
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    ++$checks;
}
function tagDocument($html) {
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>');
    return $doc;
}
function tagLinks($html) {
    $links = array();
    foreach (tagDocument($html)->getElementsByTagName('a') as $a) {
        $links[] = array($a->textContent, $a->getAttribute('href'));
    }
    return $links;
}
$c = new tagcloud(); $c->init();
tagCheck($c->buildAll() === '', 'empty cloud has no fabricated English message');
$c->addElement(' ', '/blank', 5);
tagCheck($c->buildAll() === '', 'blank labels omitted');
$c->addElement('日本語 & <tag> "quoted"', '/index.php?a=1&b=2', 0);
$html = $c->buildAll();
tagCheck(tagLinks($html) === array(array('日本語 & <tag> "quoted"', '/index.php?a=1&b=2')), 'Unicode and escaped labels/URLs round trip');
tagCheck(strpos($html, 'weight-3') !== false, 'single zero-weight tag uses neutral size');
tagCheck(strpos($html, '<tag>') === false && strpos($html, '<style') === false && strpos($html, 'style=') === false, 'no raw markup or inline CSS');
tagCheck($html === $c->buildAll() && $html === $c->biuldAll(), 'repeat rendering and FAQ spelling are stable');
$c->clearElements();
$fixtures = array(
    array('name'=>'Zulu','url'=>'/z','weight'=>9,'time'=>100),
    array('name'=>'Alpha','url'=>'?tag=alpha','weight'=>1,'time'=>200),
    array('name'=>'Middle','url'=>'https://example.invalid/m','weight'=>4,'time'=>300),
);
$html = $c->buildCloud($fixtures);
tagCheck(array_column(tagLinks($html), 0) === array('Alpha', 'Middle', 'Zulu'), 'alphabetical order');
$doc = tagDocument($html); $items = $doc->getElementsByTagName('li');
tagCheck($items->item(0)->getAttribute('class') === 'chisimba-tag-cloud__weight-1', 'minimum weight');
tagCheck($items->item(1)->getAttribute('class') === 'chisimba-tag-cloud__weight-3', 'square-root middle weight');
tagCheck($items->item(2)->getAttribute('class') === 'chisimba-tag-cloud__weight-5', 'maximum weight');
tagCheck($doc->getElementsByTagName('ul')->length === 1, 'semantic list');
$c->addElement('Last', '#anchor', 3);
tagCheck(count(tagLinks($c->buildCloud(array()))) === 4, 'incremental add/buildCloud contract');
$c->clearElements();
foreach (array('javascript:alert(1)', "java\nscript:alert(1)", 'data:text/html,bad', 'vbscript:bad', 'file:///tmp/test', 'https://', '\\evil.invalid', '//evil.invalid\\bad', ' https://example.invalid', '') as $bad) {
    $c->clearElements(); $c->addElement('Safe label', $bad, 1);
    $html = $c->buildAll();
    tagCheck(count(tagLinks($html)) === 0 && strpos($html, 'Safe label') !== false, 'unsafe URL becomes text: ' . json_encode($bad));
}
foreach (array('/relative', 'index.php?tag=x', '?tag=x', '#tag', 'https://example.invalid/x', 'http://example.invalid/x', '//example.invalid/x') as $url) {
    $c->clearElements(); $c->addElement('Link', $url, 1);
    tagCheck(tagLinks($c->buildAll()) === array(array('Link', $url)), 'supported URL retained');
}
$c->clearElements();
foreach (array(-1, 0, 'invalid', INF, NAN) as $weight) { $c->addElement('Zero', '/z', $weight); }
$html = $c->buildAll();
tagCheck(substr_count($html, 'weight-3') === 5, 'invalid/negative/nonfinite weights normalise to zero');
$c->clearElements(); $c->addElement('A', '/a', 4); $c->addElement('B', '/b', 4);
tagCheck(substr_count($c->buildAll(), 'weight-3') === 2, 'equal weights use neutral size');
$c->clearElements(); $c->addElement('duplicate', '/first', 1); $c->addElement('duplicate', '/second', 1);
tagCheck(array_column(tagLinks($c->buildAll()), 1) === array('/first', '/second'), 'equal names preserve caller order');
$c->clearElements(); $c->addElement("broken\xFF", '/broken', 1);
tagCheck(strpos($c->buildAll(), "\xEF\xBF\xBD") !== false, 'invalid UTF-8 replaced safely');
$c->clearElements();
$c->buildCloud(array(array('name'=>'Without time','url'=>'/time','weight'=>1)));
tagCheck(count(tagLinks($c->buildAll())) === 1, 'optional timestamp');
tagCheck(!class_exists('HTML_TagCloud', false), 'native renderer loads no PEAR class');

// Compare valid link content/destinations with the retained vendor oracle.
// Equal timestamps avoid its PHP 8 fractional colour-index deprecation.
require dirname(__DIR__, 2) . '/app/lib/pear/HTML/TagCloud.php';
$old = new HTML_TagCloud();
foreach ($fixtures as $tag) { $old->addElement($tag['name'], $tag['url'], $tag['weight'], 100); }
$c->clearElements();
tagCheck(tagLinks($c->buildCloud($fixtures)) === tagLinks($old->buildHTML()), 'valid labels, links and order match PEAR');
echo "PASS: $checks native tag-cloud assertions\n";
