<?php
/**
 * Verify the course-image persistence contract.
 *
 * @category  Chisimba
 * @package   context
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */
$context = dirname(__DIR__);
$coreModules = dirname($context);
$image = file_get_contents($context . '/classes/contextimage_class_inc.php');
$controller = file_get_contents($coreModules . '/contextadmin/controller.php');
$template = file_get_contents(
    $coreModules . '/contextadmin/templates/content/step2.php'
);
$register = file_get_contents($coreModules . '/contextadmin/register.conf');

$checks = array(
    'modern stored formats' => str_contains(
        $image,
        "array('jpg', 'png', 'gif', 'webp', 'avif')"
    ),
    'detected MIME controls extension' => str_contains(
        $image,
        "'image/webp' => 'webp'"
    ) && str_contains($image, "'image/avif' => 'avif'"),
    'source is validated as an image' => str_contains($image, 'getimagesize($sourceFile)'),
    'copy is atomic' => str_contains($image, 'rename($temporary, $destination)'),
    'failed save returns to step two' => str_contains(
        $controller,
        'array(\'mode\' => $mode, \'imageerror\' => \'1\')'
    ),
    'failure is not ignored' => str_contains(
        $controller,
        'if (!$objContextImage->setContextImage($contextCode, $image))'
    ),
    'translated failure notice' => str_contains(
        $template,
        'mod_contextadmin_contextimagenotsaved'
    ) && str_contains($register, 'mod_contextadmin_contextimagenotsaved'),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
