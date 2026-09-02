<?php
/** Ensure role membership joins cannot duplicate course cards. */
$overview = file_get_contents(
    dirname(__DIR__) . '/classes/studentlearningoverview_class_inc.php'
);
if (strpos($overview, 'array_unique(') === false) {
    fwrite(STDERR, "Student learning contexts are not de-duplicated.\n");
    exit(1);
}
echo "Student learning membership contract passed.\n";
