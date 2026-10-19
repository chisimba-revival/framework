<?php
/** Legacy rich HTML boundary: preserve content while rejecting executable markup. */
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject {}
require dirname(__DIR__) . '/classes/htmlcleaner_class_inc.php';
$cleaner = new htmlcleaner();
$cleaner->init();
function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
check(HTMLPurifier::VERSION === '4.19.1', 'Expected maintained vendor version');
check(ChisimbaHtmlSanitizer::purifier() === ChisimbaHtmlSanitizer::purifier(), 'Reuse shared policy');
check(ChisimbaHtmlSanitizer::purifier()->config->get('Cache.DefinitionImpl') === null, 'No vendor cache writes');
check($cleaner->cleanHtml() === null && $cleaner->cleanHtml('') === '', 'Empty input contract');
foreach ([
    '<script>alert(1)</script><p>Safe</p>',
    '<img src="/bird.jpg" onerror="alert(1)">',
    '<a href="jav&#x61;script:alert(1)">Bird</a>',
    '<img src="data:image/svg+xml,evil">',
    '<svg onload="alert(1)"><script>alert(1)</script></svg>',
    '<math><mtext><img src=x onerror=alert(1)></mtext></math>',
    '<iframe src="https://example.com"></iframe><object data="evil"></object><embed src="evil">',
    '<form action="/evil"><input name="password"></form>',
    '<p style="width:expression(alert(1));background:url(javascript:bad())">Safe</p>',
    '<a href="java&#10;script:alert(1)">Bird</a>',
] as $input) {
    $clean = $cleaner->cleanHtml($input);
    check(!preg_match('/<(script|svg|math|iframe|object|embed|form|input)\b|onerror|onload|javascript:|data:|expression\(/i', $clean), 'Unsafe output: ' . $clean);
    check($cleaner->cleanHtml($clean) === $clean, 'Idempotent output');
    check(ChisimbaHtmlSanitizer::purifier()->purify($input) === $clean, 'Engine and wrapper share policy');
}
$rich = '<h1>Élan &amp; birds</h1><table><tbody><tr><th>Name</th><td>鹭</td></tr></tbody></table><ol><li><span class="kw1" style="color:#0000ff">function</span></li></ol><pre><code>&lt;tag&gt;</code></pre><a href="https://example.com/path?a=1&amp;b=2">Web</a><a href="/files/bird.jpg">Local</a><a href="mailto:example@example.com">Email</a><img src="/files/bird.jpg" alt="Bird" width="120">';
$clean = $cleaner->cleanHtml($rich);
foreach (['<h1>Élan &amp; birds</h1>', '<table>', '<th>Name</th>', '鹭', '<ol>', 'class="kw1"', 'color:#0000ff', '<code>&lt;tag&gt;</code>', 'href="/files/bird.jpg"', 'mailto:example@example.com', 'alt="Bird"', 'width="120"'] as $fragment) {
    check(str_contains($clean, $fragment), 'Lost rich content: ' . $fragment . ' in ' . $clean);
}
check($cleaner->cleanHtml('<p>Second call</p>') === '<p>Second call</p>', 'No cross-call content accumulation');
require dirname(__DIR__) . '/classes/richtextsanitizer_class_inc.php';
$strict = new richtextsanitizer();
check(!str_contains($strict->cleanHtml('<p class="custom" style="color:red">Safe</p>'), 'style='), 'Modern strict policy remains strict');
echo "PASS: maintained shared HTML cleaner, rich content, malicious markup, Unicode, nulls, repeat calls and strict policy separation\n";
