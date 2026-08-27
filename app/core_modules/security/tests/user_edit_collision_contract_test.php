<?php
$source = file_get_contents(dirname(__DIR__) . '/classes/userservice_class_inc.php');
$expect = static function ($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$expect(
    substr_count($source, 'strcasecmp(') >= 2
        && str_contains($source, "(string) (\$existing['username'] ?? '')")
        && str_contains($source, "(string) (\$existing['emailaddress'] ?? '')"),
    'User edits must compare canonical current values before collision checks.'
);
$expect(
    strpos($source, "(string) (\$existing['username'] ?? '')")
        < strpos($source, "usernameAvailable(\$values['username'], \$userId)"),
    'An unchanged username must not be checked as a new collision.'
);
$expect(
    strpos($source, "(string) (\$existing['emailaddress'] ?? '')")
        < strpos($source, "emailAvailable(\$values['emailaddress'], \$userId)"),
    'An unchanged email address must not be checked as a new collision.'
);
$expect(
    !str_contains($source, 'SELECT * FROM tbl_users')
        && str_contains($source, 'SELECT id, userid, username, title, firstname, surname, pass,'),
    'Single-user editing must load an explicit canonical field set rather than a stale legacy wildcard row.'
);

fwrite(STDOUT, "PASS: unchanged user identity values bypass edit collision checks\n");
?>
