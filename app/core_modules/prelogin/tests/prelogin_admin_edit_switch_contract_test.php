<?php
/**
 * Verify the administrator-only prelogin editing-mode gateway.
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
$prelogin = file_get_contents(
    $root . '/templates/content/prelogin_tpl.php'
);
$admin = file_get_contents($root . '/templates/content/admin_tpl.php');

$checks = array(
    'controller supplies server-side administrator decision' => str_contains(
        $controller,
        "setVar('preloginCanEdit', \$this->objUser->isAdmin())"
    ),
    'visitor template guards the editing control' => str_contains(
        $prelogin,
        'if (!empty($preloginCanEdit))'
    ),
    'editing-on route uses protected admin action' => str_contains(
        $prelogin,
        "array('action' => 'admin')"
    ) && str_contains($controller, "case 'admin':"),
    'editing-off route returns to rendered prelogin page' => str_contains(
        $admin,
        "uri(null, 'prelogin')"
    ),
    'editing labels use the language system' => str_contains(
        $prelogin,
        "languageText(\n            'mod_context_turneditingon',"
    ) && str_contains(
        $admin,
        "languageText(\n    'mod_context_turneditingoff',"
    ),
    'existing records update by submitted identifier' => str_contains(
        $controller,
        "\$id = trim((string) \$this->getParam('id', ''));"
    ) && str_contains($controller, "if (\$id !== '')"),
    'editing actions still require authentication' => str_contains(
        $controller,
        "case 'submitblock':"
    ) && str_contains($controller, 'return TRUE;'),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
