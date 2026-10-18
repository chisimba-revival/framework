<?php
/** Removed provider libraries must stay absent while the native login remains available. */
$root = dirname(__DIR__, 2);
foreach (['app/lib/Auth', 'app/core_modules/security/classes/Auth',
    'app/core_modules/security/classes/epiwrapper_class_inc.php',
    'app/core_modules/security/resources/twitteroauth',
    'app/core_modules/security/resources/twitteroauth2',
    'app/core_modules/security/resources/openid'] as $path) {
    if (file_exists($root . '/' . $path)) throw new RuntimeException('Retired login dependency: ' . $path);
}
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject {}
require $root . '/app/core_modules/security/classes/logininterface_class_inc.php';
foreach (['oauthDisp', 'doTwitterLogin', 'openIdConnect', 'openIdLogin'] as $method) {
    if (method_exists('logininterface', $method)) throw new RuntimeException('Retired login method: ' . $method);
}
if (!method_exists('logininterface', 'renderLoginBox')) throw new RuntimeException('Native login renderer lost');
$config = file_get_contents($root . '/app/core_modules/security/register.conf');
if (preg_match('/^CONFIG: (show_twitter_auth|show_openidconnect_auth|twitter_consumer_key|twitter_consumer_secret)\|/m', $config)) {
    throw new RuntimeException('Retired provider configuration returned');
}
echo "PASS: OpenID/Twitter libraries, methods and settings absent; native renderer retained\n";
