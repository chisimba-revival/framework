<?php
/** Return targets remain local and generate no suppressed PHP diagnostics. @author Derek Keats */
class controller {}
$GLOBALS['kewl_entry_point_run'] = true;
require dirname(__DIR__, 2) . '/app/core_modules/security/controller.php';
set_error_handler(static function ($severity, $message) { throw new RuntimeException($message); });
$method = new ReflectionMethod('security', 'validatedReturnTo');
$controller = new security();
foreach (array('/index.php', '/ch/index.php') as $script) {
    $_SERVER['SCRIPT_NAME'] = $script;
    $safe = $script . '?module=webinar&action=archive';
    if ($method->invoke($controller, $safe) !== $safe) throw new RuntimeException('Local return target rejected');
    foreach (array('https://example.org/', '//example.org/', '/\\example.org/', "{$script}?x=\nvalue", "{$script}?x=\x7f", array('bad'), $script.'?module=security&action=logout') as $bad) {
        if ($method->invoke($controller, $bad) !== null) throw new RuntimeException('Unsafe return target accepted');
    }
}
$_SERVER['SCRIPT_NAME']='/ch/index.php';
if ($method->invoke($controller,'/elsewhere/index.php') !== null) throw new RuntimeException('Other installation accepted');
echo "PASS: root/subdirectory local returns, external URLs, network paths, backslashes, controls, malformed values and authentication loops; strict PHP diagnostics.\n";
