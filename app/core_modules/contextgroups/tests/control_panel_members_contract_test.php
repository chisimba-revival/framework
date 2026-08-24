<?php
/**
 * Verify canonical server-rendered course lecturers in the control panel.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   contextgroups
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */

$root = dirname(__DIR__);
$block = file_get_contents(
    $root . '/classes/block_contextmembers_class_inc.php'
);
$register = file_get_contents($root . '/register.conf');
$checks = array(
    'Context Groups version records the repair' => preg_match(
        '/^MODULE_VERSION:\s+([0-9.]+)$/m',
        $register,
        $versionMatch
    ) === 1 && version_compare($versionMatch[1], '1.073', '>='),
    'canonical GroupService owns member reads' => str_contains(
        $block,
        "getObject('groupservice', 'groupadmin')"
    ),
    'course lecturer group is resolved by canonical name' => str_contains(
        $block,
        "contextCode . '^Lecturers'"
    ) && str_contains($block, 'groupIdForName'),
    'canonical normalized members are rendered' => str_contains(
        $block,
        'getMembers($lecturerGroupId)'
    ) && str_contains($block, "lecturer['displayName']"),
    'lecturer presentation has a semantic icon' => str_contains(
        $block,
        "render('user'"
    ),
    'membership manager remains linked' => str_contains(
        $block,
        "uri(null, 'contextgroups')"
    ),
    'obsolete ExtJS member browser is absent' => !str_contains(
        $block,
        'memberbrowser'
    ) && !str_contains($block, "getObject('extjs'")
        && !str_contains($block, 'members.js'),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
