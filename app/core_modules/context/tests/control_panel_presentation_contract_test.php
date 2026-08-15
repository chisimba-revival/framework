<?php
/**
 * Verify the modern course-control-panel presentation contract.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   context
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */

$root = dirname(__DIR__);
$app = dirname(dirname($root));
$settings = file_get_contents($root . '/classes/block_contextsettings_class_inc.php');
$plugins = file_get_contents($root . '/classes/block_contextmodules_class_inc.php');
$register = file_get_contents($root . '/register.conf');
$reborn = file_get_contents($app . '/skins/chisimba-reborn/stylesheet.css');
$kenga = file_get_contents($app . '/skins/kenga-learn/stylesheet.css');

$marker = 'CHISIMBA COURSE CONTROL PANEL COMPONENTS';
$checks = array(
    'Context version records the repair' => str_contains(
        $register,
        'MODULE_VERSION: 1.99'
    ),
    'settings use a semantic spaced layout' => str_contains(
        $settings,
        'course-control-settings__details'
    ),
    'settings image has a presentation hook' => str_contains(
        $settings,
        'course-control-settings__image'
    ),
    'plugins use registered module icons' => str_contains(
        $plugins,
        "getObject(\n                'moduleiconresolver'"
    ) && str_contains($plugins, 'resolver->render($moduleId)'),
    'legacy bitmap module icon path is absent' => !str_contains(
        $plugins,
        'setModuleIcon'
    ),
    'plugin links separate icons from labels' => str_contains(
        $plugins,
        'course-control-plugin__label'
    ),
    'both supported skins contain the contract' => str_contains(
        $reborn,
        $marker
    ) && str_contains($kenga, $marker),
    'supported skin contracts remain identical' => substr(
        $reborn,
        strpos($reborn, $marker)
    ) === substr($kenga, strpos($kenga, $marker)),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
