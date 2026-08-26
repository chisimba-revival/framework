<?php
/** Verify that installation supports immutable application releases. */
$source = file_get_contents(
    dirname(__DIR__) . '/steps/installdirectory.inc'
);

$checks = array(
    'source root only needs to exist' => str_contains(
        $source,
        'if (!is_dir($sys_root))'
    ),
    'obsolete whole-root permission error is absent' => !str_contains(
        $source,
        'chmod -R 0755'
    ),
    'persistent directories remain write-tested' => str_contains(
        $source,
        '$check_dir . \'/tmpinstallfile\', "w"'
    ),
    'missing persistent directories are created recursively' => str_contains(
        $source,
        'mkdir ( $check_dir, 0755, true );'
    ),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
