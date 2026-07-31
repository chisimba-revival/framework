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
$token = isset($nativeLogoutToken) ? $nativeLogoutToken : '';
$esc = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$logoutAction = html_entity_decode(
    $this->uri(array('action' => 'logout'), 'security'),
    ENT_QUOTES,
    'UTF-8'
);
?>
<main class="security-native-auth" aria-labelledby="native-auth-title">
  <h1 id="native-auth-title"><?php echo $esc($labels['title']); ?></h1>
  <p role="status"><?php echo $esc($labels['message']); ?></p>
  <form method="post" action="<?php echo $esc($logoutAction); ?>">
    <input type="hidden" name="action" value="logout">
    <input type="hidden" name="native_auth_logout"
      value="<?php echo $esc($token); ?>">
    <button type="submit"><?php echo $esc($labels['logout']); ?></button>
  </form>
</main>
