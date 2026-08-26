<?php
$app = dirname(__DIR__, 3);
$schema = file_get_contents(dirname(__DIR__) . '/sql/tbl_sysconfig_properties.sql');
$updates = file_get_contents(dirname(__DIR__) . '/sql/sql_updates.xml');

if (!str_contains($schema, "'pmodule' => array(\n        'type' => 'text',\n        'length' => 64")
    || !str_contains($schema, "'pname' => array(\n        'type' => 'text',\n        'length' => 128")
    || !str_contains($updates, '<version>1.622</version>')) {
    fwrite(STDERR, "FAIL: modern sysconfig identifier capacity\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($app));
foreach ($iterator as $file) {
    if ($file->getFilename() !== 'register.conf') {
        continue;
    }
    foreach (file($file->getPathname(), FILE_IGNORE_NEW_LINES) as $line) {
        if (str_starts_with($line, 'MODULE_ID: ')
            && strlen(trim(substr($line, 11))) > 64) {
            fwrite(STDERR, 'FAIL: module id exceeds sysconfig capacity in '
                . $file->getPathname() . "\n");
            exit(1);
        }
        if (str_starts_with($line, 'CONFIG: ')) {
            $name = trim(explode('|', substr($line, 8), 2)[0]);
            if ($name === '' || strlen($name) > 128) {
                fwrite(STDERR, 'FAIL: configuration name exceeds sysconfig capacity in '
                    . $file->getPathname() . "\n");
                exit(1);
            }
        }
    }
}
echo "PASS: sysconfig identifier capacity covers active module declarations.\n";
?>
