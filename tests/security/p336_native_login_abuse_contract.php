<?php
$root = dirname(__DIR__, 2);
$guarded = file_get_contents($root . '/app/core_modules/security/classes/nativeauth/guardedloginapplicationservice.php');
$controller = file_get_contents($root . '/app/core_modules/security/controller.php');
$template = file_get_contents($root . '/app/core_modules/security/templates/content/native_login_tpl.php');
$factory = file_get_contents($root . '/app/core_modules/security/classes/nativeauth/nativeauthwebcompositionfactory.php');
$register = file_get_contents($root . '/app/core_modules/security/register.conf');
$assert = function ($ok, $label) { if (!$ok) { fwrite(STDERR, "FAIL: $label\n"); exit(1); } };
$evaluate = strpos($guarded, '$this->abuse->evaluate(');
$verify = strpos($guarded, '$this->credentials->verifyCredentials(');
$assert($evaluate !== false && $verify !== false && $evaluate < $verify, 'evaluate before password');
$assert(substr_count($guarded, '$this->abuse->record(\'native.login\'') === 2, 'failure and success evidence');
$assert(strpos($controller, "'abuse_issued_at'") !== false, 'controller passes evidence');
$assert(strpos($template, 'name="website"') !== false, 'honeypot present');
$assert(strpos($factory, "'abuse' => \$abuse") !== false, 'composition exposes service');
$assert(strpos($register, 'DEPENDS: abuseprotection') !== false, 'dependency declared');
$assert(strpos($guarded, 'sleep(') === false && strpos($guarded, 'usleep(') === false, 'no worker blocking delay');
echo "P336_CONTRACT=PASS\n";
