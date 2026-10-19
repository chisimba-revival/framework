<?php
/** SafeHTML/HTMLSax are retired; their public boundary uses the maintained purifier. */
$app = dirname(__DIR__, 2) . '/app';
if (is_dir($app . '/core_modules/utilities/resources/safehtml')) {
    throw new RuntimeException('Retired SafeHTML runtime has returned');
}
require $app . '/core_modules/utilities/tests/htmlcleaner_test.php';
if (class_exists('PEAR', false) || class_exists('XML_HTMLSax3', false)) {
    throw new RuntimeException('HTML cleaning loaded a retired parser');
}
echo "PASS: SafeHTML and HTMLSax retired without loading PEAR\n";
