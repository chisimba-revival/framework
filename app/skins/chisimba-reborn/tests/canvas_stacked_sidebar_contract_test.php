<?php
/**
 * Verify the stacked supplementary-sidebar width contract.
 *
 * @category  Chisimba
 * @package   skin
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */
$skins = dirname(__DIR__, 2);
$reborn = file_get_contents($skins . '/chisimba-reborn/canvases/_default/stylesheet.css');
$kenga = file_get_contents($skins . '/kenga-learn/canvases/_default/stylesheet.css');
$marker = 'CHISIMBA STACKED SIDEBAR WIDTH CONTRACT — p381';
$section = strstr($reborn, $marker);
$checks = array(
    'skin canvas parity' => hash('sha256', $reborn) === hash('sha256', $kenga),
    'sidebar contract exists' => $section !== false,
    'both logical block lists covered' => str_contains($section, '#leftblocks')
        && str_contains($section, '#rightblocks'),
    'block lists use full width' => preg_match(
        '~:is\(#leftblocks, #rightblocks, #leftaddblock, #rightaddblock\) \{[^}]*width: 100%;~s',
        $section
    ) === 1,
    'complete child surfaces use full width' => str_contains($section, 'max-inline-size: none;')
        && str_contains($section, 'max-width: none;'),
    'legacy edit inset overridden' => str_contains($section, '> #editmode {')
        && str_contains($section, 'padding-inline: 0;'),
    'edit switch uses full width' => str_contains($section, '> #editmodeswitchbutton {')
        && substr_count($section, 'width: 100%;') >= 5,
    'direct User block is not targeted' => !preg_match(
        '~Region3\)\s*>\s*:is\(\.featurebox~',
        $section
    ),
    'plain block wrappers remain protected' => !preg_match(
        '~> \.block\s*\{[^}]*background:\s*transparent~s',
        $section
    ),
);
foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
