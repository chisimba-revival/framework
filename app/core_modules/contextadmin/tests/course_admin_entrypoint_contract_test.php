<?php
/**
 * Verify the Course Admin entry-point routing contract.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   contextadmin
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */

$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$register = file_get_contents($root . '/register.conf');

$homeStart = strpos($controller, 'private function __home()');
$homeEnd = strpos($controller, 'private function __add()', $homeStart);
$home = substr($controller, $homeStart, $homeEnd - $homeStart);

$checks = array(
    'ContextAdmin has release metadata' => preg_match(
        '/^MODULE_VERSION:\s*\d+(?:\.\d+)?$/m',
        $register
    ) === 1,
    'active manageable course opens its control panel' => str_contains(
        $home,
        "'controlpanel'"
    ) && str_contains($home, "'context'")
        && str_contains($home, '$this->canManageContext($contextCode)'),
    'root scope retains the lecturer course list' => str_contains(
        $home,
        "getUserContextsFormatted"
    ) && str_contains($home, '$contextCode !== \'root\''),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
