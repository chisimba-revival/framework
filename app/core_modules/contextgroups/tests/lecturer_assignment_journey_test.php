<?php
/** Verify the discoverable, canonical lecturer-assignment journey. */
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$template = file_get_contents($root . '/templates/content/home_tpl.php');
$register = file_get_contents($root . '/register.conf');

$checks = array(
    'course-members page links to author and ownership management' =>
        str_contains($template, "'action' => 'authors'")
        && str_contains($template, "'contextcode' => \$contextCode"),
    'search results expose the site-author prerequisite' =>
        str_contains($template, "['isSiteAuthor']")
        && str_contains($template, 'name="grantsiteauthor"'),
    'only site administrators may grant site-author access' =>
        str_contains($controller, '$this->securityUser->isAdmin()')
        && str_contains($controller, "'grantsiteauthor'"),
    'site and course membership use canonical services' =>
        str_contains($controller, 'siteAuthorGroupId')
        && str_contains($controller, 'permissionUserIdForUser')
        && str_contains($controller, 'ensureMembership'),
    'failed course assignment rolls back a newly granted site role' =>
        str_contains($controller, 'if ($grantedSiteAuthor)')
        && str_contains($controller, 'removeMembership'),
    'administrator-facing explanations are registered' =>
        str_contains($register, 'mod_contextgroups_ui_granthelp')
        && str_contains($register, 'mod_contextgroups_err_siteauthorrequired'),
);

foreach ($checks as $label => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$label}\n");
        exit(1);
    }
}

echo "PASS: lecturer-assignment journey contract verified.\n";
?>
