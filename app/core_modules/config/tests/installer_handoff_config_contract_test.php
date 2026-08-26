<?php
/** Verify that incomplete configuration reads cannot crash application startup. */
$source = file_get_contents(
    dirname(__DIR__) . '/classes/altconfig_class_inc.php'
);

$checks = array(
    'missing parsed root fails safely' => str_contains(
        $source,
        'if (!is_object($this->_root))'
    ),
    'missing Settings section fails safely' => str_contains(
        $source,
        'if (!is_object($Settings))'
    ),
    'logging uses the guarded accessor' => preg_match(
        '~function getenable_logging\(\).*?getItem\(\'KEWL_ENABLE_LOGGING\'\)~s',
        $source
    ) === 1,
    'logging no longer dereferences a missing directive' => !preg_match(
        '~function getenable_logging\(\).*?SettingsDirective->getContent~s',
        $source
    ),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
