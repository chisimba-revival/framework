<?php
/**
 * Verify the complete-component canvas surface and width contract.
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
$checks = array(
    'skin canvas parity' => hash('sha256', $reborn) === hash('sha256', $kenga),
    'semantic component recognised' => str_contains($reborn, '.block:has(> .content-block)'),
    'region shell flattened' => str_contains($reborn, '):has(> .content-block),'),
    'placement list owns spacing' => str_contains($reborn, 'gap: var(--chisimba-space-3, 12px);'),
    'structural wrapper uses full width' => preg_match(
        '~\.block:has\(> \.content-block\) \{[^}]*width: 100%;~s',
        $reborn
    ) === 1,
    'structural wrapper defeats legacy important inset' => preg_match(
        '~\.block:has\(> \.content-block\) \{[^}]*padding: 0 !important;~s',
        $reborn
    ) === 1,
    'component width is unconstrained' => str_contains($reborn, 'max-inline-size: none;')
        && str_contains($reborn, 'max-width: none;'),
    'plain blocks are not globally flattened' => !preg_match(
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
