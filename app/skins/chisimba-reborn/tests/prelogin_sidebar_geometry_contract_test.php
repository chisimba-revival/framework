<?php
$css = file_get_contents(dirname(__DIR__) . '/canvases/_default/stylesheet.css');
$selector = '#Canvas_Content_Body:has(#form_loginform)'
    . "\n    > #Canvas_Content_Body_Region1 {";
$start = strpos($css, $selector);
$end = $start === false ? false : strpos($css, '}', $start);
$rule = $start === false || $end === false ? '' : substr($css, $start, $end - $start);
$checks = array(
    'prelogin sidebar rule exists' => $rule !== '',
    'sidebar region adds no inset' => strpos($rule, 'padding: 0 !important') !== false,
    'sidebar fills its grid track' => strpos($rule, 'width: 100% !important') !== false,
    'sidebar starts at the grid baseline' => strpos($rule, 'align-self: start !important') !== false,
);
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}
fwrite(STDOUT, "Prelogin sidebar geometry contract: PASS\n");
