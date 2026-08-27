<?php
/** The Updates block must leave no visible shell for non-administrators. */
$source = file_get_contents(dirname(__DIR__) . '/classes/block_updates_class_inc.php');
$checks = array(
    "\$this->blockType = 'invisible'",
    "\$this->title = ''",
    "return '';",
);
foreach ($checks as $check) {
    if (!str_contains($source, $check)) {
        fwrite(STDERR, "FAIL: missing non-admin invisibility contract: {$check}\n");
        exit(1);
    }
}
echo "PASS: updates block is invisible to non-administrators\n";
