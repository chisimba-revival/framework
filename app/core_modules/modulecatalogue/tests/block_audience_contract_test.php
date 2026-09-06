<?php
$root = dirname(__DIR__);
$database = file_get_contents($root . '/classes/dbmoduleblocks_class_inc.php');
$registration = file_get_contents($root . '/classes/modulesadmin_class_inc.php');
$schema = file_get_contents($root . '/sql/tbl_module_blocks.sql');
$updates = file_get_contents($root . '/sql/sql_updates.xml');

$checks = array(
    'block registry stores audience metadata' => strpos($schema, "'blockaudience'") !== false,
    'existing installations add the audience field' => strpos($updates, '<name>blockaudience</name>') !== false,
    'manifest registration accepts an audience field' => strpos($registration, "isset(\$blockInfo[2]) ? \$blockInfo[2] : 'general'") !== false,
    'unclassified blocks remain general' => strpos($database, "empty(\$values) ? 'general'") !== false,
    'only supported audience names are accepted' => strpos($database, "array('general', 'readonly', 'author', 'admin', 'root')") !== false,
    'block queries can filter for their destination audience' => strpos($database, 'FIND_IN_SET') !== false,
);

$failed = array();
foreach ($checks as $label => $passed) {
    if (!$passed) { $failed[] = $label; }
}
if ($failed) {
    fwrite(STDERR, "Block audience contract failed:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}
echo "Block audience contract passed.\n";
