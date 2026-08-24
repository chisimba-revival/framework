<?php
/**
 * Verify that URI construction is context independent and links escape once.
 */

$root = dirname(__DIR__, 2);
$engine = file_get_contents($root . '/app/classes/core/engine_class_inc.php');
if (!str_contains($engine, "                : '&';")) {
    fwrite(STDERR, "FAIL: ordinary URI construction does not use raw separators\n");
    exit(1);
}
if (str_contains($engine, ': \'&amp;\', $output')) {
    fwrite(STDERR, "FAIL: URI construction still pre-encodes HTML entities\n");
    exit(1);
}

$GLOBALS['kewl_entry_point_run'] = true;
require_once $root . '/app/classes/core/object_class_inc.php';
$elements = $root . '/app/core_modules/htmlelements/classes';
require_once $elements . '/abhtmlbase_class_inc.php';
require_once $elements . '/ifhtml_class_inc.php';
require_once $elements . '/link_class_inc.php';

$link = new link('/index.php?module=context&action=manageplugins');
$link->link = 'Manage course tools';
$html = $link->show();
$expected = 'href="/index.php?module=context&amp;action=manageplugins"';
if (!str_contains($html, $expected)) {
    fwrite(STDERR, "FAIL: link did not encode its href exactly once: $html\n");
    exit(1);
}
if (str_contains($html, '&amp;amp;')) {
    fwrite(STDERR, "FAIL: link contains a double-encoded query separator\n");
    exit(1);
}

$legacy = new link('/index.php?module=context&amp;action=manageplugins');
$legacy->link = 'Legacy caller';
$legacyHtml = $legacy->show();
if (!str_contains($legacyHtml, $expected)
    || str_contains($legacyHtml, '&amp;amp;')
) {
    fwrite(STDERR, "FAIL: legacy pre-escaped link was not normalized once\n");
    exit(1);
}

echo "Raw URI and single-encoding link contract passed.\n";
