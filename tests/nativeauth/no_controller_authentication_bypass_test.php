<?php
/**
 * Prevent controllers from bypassing the canonical authentication boundary.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
$root = dirname(__DIR__, 2);
$controllers = array(
    $root . '/app/core_modules/security/controller.php',
    $root . '/app/core_modules/login/controller.php',
);
$failures = array();
foreach ($controllers as $controller) {
    $source = file_get_contents($controller);
    if ($source === false) {
        $failures[] = 'Cannot read ' . $controller;
        continue;
    }
    $forbiddenValues = array(
        "case 'ajax_login'",
        'function __ajaxlogin(',
    );
    if (strpos($controller, '/login/controller.php') !== false) {
        $forbiddenValues[] = 'authenticateUser(';
    }
    foreach ($forbiddenValues as $forbidden) {
        if (strpos($source, $forbidden) !== false) {
            $failures[] = basename(dirname($controller))
                . '/controller.php contains ' . $forbidden;
        }
    }
}
if ($failures) {
    fwrite(STDERR, "FAIL\n" . implode("\n", $failures) . "\n");
    exit(1);
}
echo "PASS: controllers do not expose legacy authentication side doors.\n";
