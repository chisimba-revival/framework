<?php
$auth = file_get_contents(dirname(__FILE__)
    . '/../classes/auth_database_class_inc.php');
$required = array(
    'authenticateNatively($username, $password, (bool) $remember)',
    'issuePersistentLogin($record[\'userid\'])',
    'require_mfa_site_admins',
    'persistent_login_lifetime_days',
);
foreach ($required as $signature) {
    if (strpos($auth, $signature) === false) {
        fwrite(STDERR, 'missing: ' . $signature . PHP_EOL); exit(1);
    }
}
$repo = file_get_contents(dirname(__FILE__)
    . '/../classes/nativeauth/mdb2persistentloginrepository.php');
foreach (array('FOR UPDATE', 'beginTransaction', 'replaced_by_id') as $gate) {
    if (strpos($repo, $gate) === false) exit(2);
}
$mfa = file_get_contents(dirname(__FILE__)
    . '/../classes/nativeauth/mdb2mfarepository.php');
foreach (array('last_accepted_step<?', 'FOR UPDATE', 'used_at IS NULL') as $gate) {
    if (strpos($mfa, $gate) === false) exit(3);
}
fwrite(STDOUT, 'PASS: V14 integration source gates' . PHP_EOL);
