<?php
/** Static CPD Phase 1 contract test. @author Derek Keats @package cpd */
$root = dirname(__DIR__);
$required = array('register.conf', 'classes/cpdservice_class_inc.php', 'classes/cpdschemes_class_inc.php',
    'classes/cpdcategories_class_inc.php', 'classes/cpdrecognitions_class_inc.php', 'classes/cpdledger_class_inc.php',
    'sql/tbl_cpd_schemes.sql', 'sql/tbl_cpd_categories.sql', 'sql/tbl_cpd_context_recognition.sql', 'sql/tbl_cpd_ledger.sql');
foreach ($required as $path) { if (!is_file($root . '/' . $path)) { fwrite(STDERR, "Missing: $path\n"); exit(1); } }
$register = file_get_contents($root . '/register.conf');
foreach (array('MODULE_ID: cpd', 'TABLE: tbl_cpd_schemes', 'TABLE: tbl_cpd_categories', 'TABLE: tbl_cpd_context_recognition', 'TABLE: tbl_cpd_ledger') as $needle) {
    if (strpos($register, $needle) === false) { fwrite(STDERR, "Missing contract: $needle\n"); exit(1); }
}
$service = file_get_contents($root . '/classes/cpdservice_class_inc.php');
foreach (array('function createScheme', 'function createCategory', 'function recogniseContext', 'function allocateManual', 'function correct', 'function reverse', 'function historyForIdentity') as $needle) {
    if (strpos($service, $needle) === false) { fwrite(STDERR, "Missing service operation: $needle\n"); exit(1); }
}
if (preg_match('/->(?:update|delete)\s*\(/', $service)) { fwrite(STDERR, "Ledger service must not update or delete records\n"); exit(1); }
echo "cpd phase 1 contracts: OK\n";
?>
