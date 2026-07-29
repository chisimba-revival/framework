<?php
/**
 * Accessible native login form.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
$labels = isset($nativeLoginLabels) && is_array($nativeLoginLabels)
    ? $nativeLoginLabels
    : array();
$token = isset($nativeAuthBeginToken) ? $nativeAuthBeginToken : '';
$esc = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<main class="security-native-auth" aria-labelledby="native-login-title">
  <h1 id="native-login-title"><?php echo $esc($labels['title']); ?></h1>
  <?php if (!empty($labels['failure'])): ?>
    <p role="alert"><?php echo $esc($labels['failure']); ?></p>
  <?php endif; ?>
  <form method="post">
    <input type="hidden" name="action" value="login">
    <input type="hidden" name="native_auth_begin"
      value="<?php echo $esc($token); ?>">
    <p>
      <label for="native-login-username"><?php
          echo $esc($labels['username']);
      ?></label>
      <input id="native-login-username" name="username" type="text"
        autocomplete="username" required autofocus>
    </p>
    <p>
      <label for="native-login-password"><?php
          echo $esc($labels['password']);
      ?></label>
      <input id="native-login-password" name="password" type="password"
        autocomplete="current-password" required>
    </p>
    <p>
      <input id="native-login-remember" name="remember" type="checkbox">
      <label for="native-login-remember"><?php
          echo $esc($labels['remember']);
      ?></label>
    </p>
    <button type="submit"><?php echo $esc($labels['submit']); ?></button>
  </form>
</main>
