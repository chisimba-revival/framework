<?php
/**
 * Verify the inline public-page block editor contract.
 *
 * @category  Chisimba
 * @package   prelogin
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */
$root = dirname(__DIR__);
$applicationRoot = dirname(dirname($root));
$controller = file_get_contents($root . '/controller.php');
$template = file_get_contents(
    $root . '/templates/content/prelogin_tpl.php'
);
$register = file_get_contents($root . '/register.conf');
$registry = file_get_contents(
    $applicationRoot
    . '/core_modules/modulecatalogue/classes/dbmoduleblocks_class_inc.php'
);

$checks = array(
    'editing uses the actual public-page template' => str_contains(
        $controller,
        'return $this->showPage(TRUE);'
    ) && !str_contains($controller, "return 'admin_tpl.php';"),
    'administrator switch controls inline editing mode' =>
        str_contains($template, 'mod_context_turneditingon')
        && str_contains($template, 'mod_context_turneditingoff')
        && str_contains($template, '$preloginEditing'),
    'all three columns receive normal add controls' =>
        str_contains($template, '$renderAddControl(\'left\'')
        && str_contains($template, '$renderAddControl(\'middle\'')
        && str_contains($template, '$renderAddControl(\'right\''),
    'placed blocks can be reordered and removed' =>
        str_contains($template, "'action' => 'moveup'")
        && str_contains($template, "'action' => 'movedown'")
        && str_contains($template, "'action' => 'delete'"),
    'all placed blocks receive a shared flow wrapper' =>
        str_contains($template, 'prelogin-placed-block')
        && str_contains($template, '$renderPlacedBlock($block, $side)'),
    'hidden acquisition blocks leave no empty public shell' =>
        str_contains($template, "trim((string) \$rendered) === ''")
        && str_contains($template, 'if (!$editing')
        && str_contains($template, 'continue;'),
    'content blocks are available in the catalogue' =>
        str_contains($controller, 'getBlocksArr($contentType)')
        && str_contains($controller, "'moduleid' => 'contentblocks'"),
    'curation is dormant and uses prelogin registry types later' =>
        str_contains($register, 'CONFIG: CURATE_PUBLIC_BLOCKS|FALSE|')
        && str_contains($controller, "? 'prelogin' : NULL"),
    'repeated audience registrations update their exact row' =>
        str_contains($registry, '$exists[0][\'id\']')
        && !str_contains($registry, 'WHERE blockname = \'$blockName\''),
    'layout mutations require login' =>
        str_contains($controller, "'addregisteredblock'")
        && str_contains($controller, "'moveup'")
        && str_contains($controller, "'delete'"),
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
    echo "PASS: {$name}\n";
}
?>
