<?php
/** Account archive and hard-delete safety contract. */
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$template = file_get_contents($root . '/templates/content/native_admin_tpl.php');
$lifecycle = file_get_contents(
    dirname($root) . '/security/classes/useraccountlifecycleservice_class_inc.php'
);
$checks = array(
    'single and batch archive actions exist' => str_contains($template, '>Archive</button>')
        && str_contains($template, 'name="userids[]"')
        && str_contains($template, 'class="ua-batch-controls"')
        && str_contains($controller, "case 'batcharchive':"),
    'archive uses canonical active status' => str_contains(
        $controller,
        'setActive($userId, false)'
    ),
    'current administrator cannot archive self' => str_contains(
        $controller,
        'cannot_archive_current_account'
    ),
    'hard deletion stays disabled' => str_contains(
        $lifecycle,
        'user_deletion_not_yet_supported'
    ),
    'required markers remain inside their labels' => substr_count(
        $template,
        'class="ua-field-label"'
    ) >= 6,
);
foreach ($checks as $name => $passed) {
    if (!$passed) { fwrite(STDERR, "FAIL: {$name}\n"); exit(1); }
}
fwrite(STDOUT, "PASS: safe account archive contract\n");
?>
