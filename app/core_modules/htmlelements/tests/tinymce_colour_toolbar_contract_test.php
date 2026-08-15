<?php
/**
 * Verify TinyMCE colour controls in the Chisimba editor adapter.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   htmlelements
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */

$root = dirname(__DIR__);
$adapter = file_get_contents(
    $root . '/classes/editoradapter_class_inc.php'
);
$register = file_get_contents($root . '/register.conf');
$tinyMce = file_get_contents($root . '/resources/tinymce8/tinymce.min.js');

$advancedColourGroup = ": 'undo redo | blocks | bold italic underline | "
    . "forecolor backcolor | '";
$simpleToolbar = 'undo redo | bold italic | bullist numlist | '
    . 'link image | removeformat';

$checks = array(
    'module version records toolbar change' => str_contains(
        $register,
        'MODULE_VERSION: 0.613'
    ),
    'advanced toolbar exposes text and background colour' => str_contains(
        $adapter,
        $advancedColourGroup
    ),
    'simple toolbar remains intentionally simple' => str_contains(
        $adapter,
        $simpleToolbar
    ),
    'bundled TinyMCE contains foreground colour support' => str_contains(
        $tinyMce,
        'forecolor'
    ),
    'bundled TinyMCE contains background colour support' => str_contains(
        $tinyMce,
        'backcolor'
    ) || str_contains($tinyMce, 'hilitecolor'),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
