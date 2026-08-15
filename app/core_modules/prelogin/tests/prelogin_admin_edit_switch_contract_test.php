<?php
/**
 * Verify the administrator-only prelogin editing switch.
 *
 * @category  Chisimba
 * @package   prelogin
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$template = file_get_contents(
    $root . '/templates/content/prelogin_tpl.php'
);

$checks = array(
    'controller supplies administrator and editing decisions' =>
        str_contains($controller, "setVar('preloginCanEdit'")
        && str_contains($controller, "setVar('preloginEditing'"),
    'public template guards the editing switch' =>
        str_contains($template, 'if ($canEdit)'),
    'editing-on action is protected' =>
        str_contains($template, "array('action' => 'admin')")
        && str_contains($controller, "case 'admin':")
        && str_contains($controller, "array('admin', 'addregisteredblock'"),
    'editing-off returns to the public page' =>
        str_contains($template, "uri(NULL, 'prelogin')"),
    'switch labels use the language system' =>
        str_contains($template, 'mod_context_turneditingon')
        && str_contains($template, 'mod_context_turneditingoff'),
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
    echo "PASS: {$name}\n";
}
?>
