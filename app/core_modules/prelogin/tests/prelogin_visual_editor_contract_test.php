<?php
/**
 * Verify the visual prelogin editor and anonymous visitor boundary.
 *
 * @category  Chisimba
 * @package   prelogin
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */
$preloginRoot = dirname(__DIR__);
$applicationRoot = dirname(dirname($preloginRoot));
$controller = file_get_contents($preloginRoot . '/controller.php');
$editor = file_get_contents(
    $preloginRoot . '/templates/content/admin_tpl.php'
);
$addBlock = file_get_contents(
    $preloginRoot . '/templates/content/addblock_tpl.php'
);
$blocks = file_get_contents(
    $applicationRoot . '/core_modules/blocks/classes/blocks_class_inc.php'
);
$register = file_get_contents($preloginRoot . '/register.conf');

$checks = array(
    'visitor preview cannot expose the editing control' => str_contains(
        $controller,
        '&& !$isVisitorPreview'
    ),
    'visitor preview requests a credential-free browser context' => str_contains(
        $editor,
        '<iframe credentialless'
    ),
    'editor presents the three actual page columns' => str_contains(
        $editor,
        "'left' =>"
    ) && str_contains($editor, "'middle' =>")
        && str_contains($editor, "'right' =>"),
    'placed blocks retain move edit delete and visibility controls' =>
        str_contains($editor, "'action' => 'moveup'")
        && str_contains($editor, "'action' => 'movedown'")
        && str_contains($editor, "'action' => 'editblock'")
        && str_contains($editor, "'action' => 'delete'")
        && str_contains($editor, '_vis'),
    'block catalogue rejects authenticated audiences' => str_contains(
        $addBlock,
        'isAnonymousSafe('
    ),
    'anonymous block policy checks all authenticated restrictions' =>
        str_contains($blocks, 'public function isAnonymousSafe(')
        && str_contains($blocks, 'requiresLogin')
        && str_contains($blocks, 'requiresAdmin')
        && str_contains($blocks, 'requiresGroup'),
    'new interface text is owned by the language system' =>
        str_contains($register, 'mod_prelogin_visitorpreview|')
        && str_contains($register, 'mod_prelogin_editlayout|')
        && str_contains($register, 'mod_prelogin_hiddenblock|'),
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
    echo "PASS: {$name}\n";
}
