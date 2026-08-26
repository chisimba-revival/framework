<?php
/** Verify that batch installation cannot corrupt its post/redirect/get response. */
$source = file_get_contents(dirname(__DIR__) . '/controller.php');

$checks = array(
    'batch registration starts an output boundary' => preg_match(
        '~function batchRegister\(\$modArray\).*?ob_start\(\)~s',
        $source
    ) === 1,
    'successful batch diagnostics are contained' => preg_match(
        '~function batchRegister\(\$modArray\).*?ob_get_clean\(\).*?return \$success~s',
        $source
    ) === 1,
    'exception path restores the prior buffer level' => str_contains(
        $source,
        'while (ob_get_level() > $bufferLevel)'
    ),
    'batch action retains post redirect get' => str_contains(
        $source,
        "return \$this->nextAction ( 'list', array ('cat' => \$activeCat, 'lastError' => \$error ) );"
    ),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
