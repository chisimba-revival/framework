<?php
/**
 * Verify that the reusable image selector uses the native File Manager.
 *
 * @category  Chisimba
 * @package   filemanager
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */
$coreModules = dirname(__DIR__, 2);
$selector = file_get_contents(__DIR__ . '/../classes/selectimage_class_inc.php');
$controller = file_get_contents(__DIR__ . '/../controller.php');
$stepTwo = file_get_contents($coreModules . '/contextadmin/templates/content/step2.php');
$contextController = file_get_contents($coreModules . '/contextadmin/controller.php');

$checks = array(
    'native picker route exists' => str_contains($controller, 'private function __filepicker()'),
    'selector requests native route' => str_contains($selector, "'action' => 'filepicker'"),
    'selector applies image policy' => str_contains($selector, "'policy' => 'image'"),
    'legacy popup is not invoked' => !str_contains($selector, 'selectimagewindow'),
    'picker returns File Manager ID' => str_contains($selector, 'field.value=file.id'),
    'picker returns preview URL' => str_contains($selector, 'preview.src=file.url'),
    'saved image can be supplied as a preview' => str_contains(
        $selector,
        'setDefaultPreviewUrl'
    ) && str_contains($selector, 'baseline='),
    'reset restores the saved preview' => str_contains(
        $selector,
        'preview.src=baseline'
    ),
    'selector uses shared button primitives' => str_contains(
        $selector,
        'button chisimba-button-danger'
    ) && !str_contains($selector, '<style>'),
    'course form retains selector component' => str_contains(
        $stepTwo,
        "getObject('selectimage', 'filemanager')"
    ),
    'course save retains record-ID field' => str_contains(
        $contextController,
        "getParam('imageselect')"
    ),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
