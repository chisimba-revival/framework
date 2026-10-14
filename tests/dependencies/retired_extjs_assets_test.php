<?php
/** Guard removal of the replaced ExtJS context-members browser. @author Derek Keats */
$root = dirname(__DIR__, 2);
foreach (['lecturer', 'student', 'users', 'members'] as $name) {
    if (file_exists($root . '/app/core_modules/contextgroups/resources/' . $name . '.js')) {
        throw new RuntimeException('Retired member browser returned: ' . $name);
    }
}
$block = file_get_contents($root . '/app/core_modules/contextgroups/classes/block_contextmembers_class_inc.php');
if (!str_contains($block, "getObject('groupservice', 'groupadmin')") || str_contains($block, 'members.js')) {
    throw new RuntimeException('Course members must retain their native group-service renderer');
}
echo "PASS: retired ExtJS member assets absent; native renderer retained\n";
