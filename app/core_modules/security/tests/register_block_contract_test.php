<?php
/** Registration acquisition block visibility contract. */
$root = dirname(__DIR__);
$block = file_get_contents($root . '/classes/block_register_class_inc.php');
$login = file_get_contents($root . '/classes/block_login_class_inc.php');
$register = file_get_contents($root . '/register.conf');
$checks = array(
    'authenticated users make the block invisible' => str_contains(
        $block,
        '|| $this->objUser->isLoggedIn()'
    ) && str_contains($block, '$this->blockType="invisible"'),
    'authenticated rendering is empty' => str_contains(
        $block,
        "if (\$this->objUser->isLoggedIn()) {\n                return '';"
    ),
    'login acquisition block is invisible after authentication' => str_contains(
        $login,
        "\$this->blockType = 'invisible';"
    ) && str_contains($login, "\$this->title = '';")
        && str_contains($login, "if (\$this->objUser->isLoggedIn()) {\n                return '';"),
    'module update recorded' => str_contains($register, 'MODULE_VERSION: 3.103'),
);
foreach ($checks as $name => $passed) {
    if (!$passed) { fwrite(STDERR, "FAIL: {$name}\n"); exit(1); }
}
fwrite(STDOUT, "PASS: authenticated acquisition blocks contract\n");
?>
