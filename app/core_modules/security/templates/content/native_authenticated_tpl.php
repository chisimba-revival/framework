<?php
/**
 * Deliberately minimal native authentication landing page.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
$labels = isset($nativeLandingLabels) && is_array($nativeLandingLabels)
    ? $nativeLandingLabels
    : array();
$esc = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<main class="security-native-auth" aria-labelledby="native-auth-title">
  <h1 id="native-auth-title"><?php echo $esc($labels['title']); ?></h1>
  <p role="status"><?php echo $esc($labels['message']); ?></p>
</main>
