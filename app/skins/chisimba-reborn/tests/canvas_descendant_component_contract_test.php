<?php
/**
 * Verify wrapper-depth-independent rendering of complete Content blocks.
 *
 * @category  Chisimba
 * @package   skin
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */
$skins = dirname(__DIR__, 2);
$reborn = file_get_contents(
    $skins . '/chisimba-reborn/canvases/_default/stylesheet.css'
);
$checks = array(
    'layout has one canonical owner' => !str_contains(
        file_get_contents(
            $skins . '/chisimba-reborn/canvases/kenga-learn/stylesheet.css'
        ),
        'grid-template-areas'
    ),
    'descendant region recognised' => str_contains(
        $reborn,
        '):has(.content-block) {'
    ),
    'nested generic wrapper recognised' => str_contains(
        $reborn,
        ') .block:has(.content-block) {'
    ),
    'nested placement list recognised' => str_contains(
        $reborn,
        ') :is(#leftblocks, #middleblocks, #rightblocks):has(.content-block) {'
    ),
    'ordinary blocks remain boxed' => !preg_match(
        '~(?:^|,)\s*\.block\s*\{[^}]*background:\s*transparent~m',
        $reborn
    ),
);
foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
