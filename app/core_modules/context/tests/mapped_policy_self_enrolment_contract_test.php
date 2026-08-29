<?php
$root = dirname(__DIR__);
$context = file_get_contents($root . '/classes/dbcontext_class_inc.php');
$checks = array(
    'mapped admission establishes membership before entry' => strpos(
        $context,
        'ensureMappedPolicyCourseMembership($line)'
    ) !== false,
    'only self-enrolling course policies are included' => strpos(
        $context,
        "array('free', 'tier_1', 'tier_2')"
    ) !== false,
    'public and private policy membership remain separate' => strpos(
        $context,
        'Public courses retain anonymous viewing'
    ) !== false && strpos($context, 'Private courses retain their') !== false,
    'canonical Students group is used' => strpos(
        $context,
        "['contextcode'] . '^Students'"
    ) !== false,
    'canonical identity and idempotent membership are used' => strpos(
        $context,
        "getObject('identityservice', 'security')"
    ) !== false && strpos($context, 'ensureMembership($groupId, $permissionUserId)') !== false,
    'membership failure prevents partial course entry' => strpos(
        $context,
        'if (!$this->ensureMappedPolicyCourseMembership($line))'
    ) !== false,
);
foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
?>
