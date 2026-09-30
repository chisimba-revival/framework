<?php

$root = dirname(__DIR__, 2) . '/app';
$sources = array(
    $root . '/classes/core/dbtable_class_inc.php',
    $root . '/core_modules/api/classes/xmlrpcapi_class_inc.php',
    $root . '/core_modules/config/classes/altconfig_class_inc.php',
    $root . '/core_modules/sysconfig/classes/dbsysconfig_class_inc.php',
    $root . '/installer/steps/createconfigs.inc',
    $root . '/lib/logging.php',
);
$retiredMarkers = array(
    'ENABLE_ADM',
    'KEWL_MIRROR_WSDL_URL',
    'mirror_wsdl_url',
    'adm.getFullLog',
    'adm.registerServer',
    "getObject('admapi')",
    'sql_log(',
);

foreach ($sources as $source) {
    $contents = file_get_contents($source);
    foreach ($retiredMarkers as $marker) {
        if (str_contains($contents, $marker)) {
            fwrite(STDERR, "FAIL: {$marker} remains in {$source}\n");
            exit(1);
        }
    }
}

if (file_exists($root . '/core_modules/api/classes/admapi_class_inc.php')) {
    fwrite(STDERR, "FAIL: ADM XML-RPC implementation remains on disk\n");
    exit(1);
}

echo "PASS: Active Dynamic Mirroring retirement contract\n";
