<?php
$controller = file_get_contents(dirname(__DIR__) . '/controller.php');
$expect = static function ($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$expect(
    str_contains($controller, "(string) (\$record['userid'] ?? '') === \$selectedId")
        && !str_contains($controller, 'findByUserId($selectedId)'),
    'The editor must use the same canonical directory row that populated the visible user listing.'
);

fwrite(STDOUT, "PASS: user editor and directory share one canonical record\n");
?>
