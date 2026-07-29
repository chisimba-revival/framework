<?php
/**
 * Accessible MFA enrollment, challenge, and recovery-code views.
 *
 * All visible strings are supplied by the security controller through the
 * owning module's language service.
 *
 * @author Derek Keats
 * @package security
 */
$mfa = isset($mfa) && is_array($mfa) ? $mfa : array();
$labels = isset($mfaLabels) && is_array($mfaLabels) ? $mfaLabels : array();
$esc = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$view = isset($mfa['view']) ? $mfa['view'] : 'invalid';
?>
<main class="security-mfa" aria-labelledby="security-mfa-title">
  <h1 id="security-mfa-title"><?php echo $esc($labels['title']); ?></h1>
  <?php if ($view === 'enrolment' && !empty($mfa['provisioning_uri'])): ?>
    <p><?php echo $esc($labels['scan']); ?></p>
    <div data-chisimba-mfa-qr="<?php
        echo $esc($mfa['provisioning_uri']);
    ?>" role="img" aria-label="<?php echo $esc($labels['qr_alt']); ?>"></div>
    <p><strong><?php echo $esc($labels['manual_key']); ?></strong></p>
    <code><?php echo $esc($mfa['manual_key']); ?></code>
    <form method="post">
      <input type="hidden" name="action" value="mfa_enrol_confirm">
      <input type="hidden" name="csrf_token"
        value="<?php echo $esc($mfa['csrf_token']); ?>">
      <input type="hidden" name="enrolment_id"
        value="<?php echo $esc($mfa['enrolment_id']); ?>">
      <label for="mfa-code"><?php echo $esc($labels['code']); ?></label>
      <input id="mfa-code" name="code" inputmode="numeric"
        autocomplete="one-time-code" required pattern="[0-9 ]{6,10}">
      <button type="submit"><?php echo $esc($labels['confirm']); ?></button>
    </form>
  <?php elseif ($view === 'challenge'): ?>
    <?php if (($mfa['status'] ?? '') === 'invalid_code'): ?>
      <p role="alert"><?php echo $esc($labels['invalid_code']); ?></p>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="action" value="mfa_totp">
      <input type="hidden" name="csrf_token"
        value="<?php echo $esc($mfa['totp_csrf_token']); ?>">
      <input type="hidden" name="transaction_id"
        value="<?php echo $esc($mfa['transaction_id']); ?>">
      <label for="mfa-code"><?php echo $esc($labels['code']); ?></label>
      <input id="mfa-code" name="code" inputmode="numeric"
        autocomplete="one-time-code" required>
      <button type="submit"><?php echo $esc($labels['verify']); ?></button>
    </form>
    <form method="post">
      <input type="hidden" name="action" value="mfa_recovery">
      <input type="hidden" name="csrf_token"
        value="<?php echo $esc($mfa['recovery_csrf_token']); ?>">
      <input type="hidden" name="transaction_id"
        value="<?php echo $esc($mfa['transaction_id']); ?>">
      <label for="recovery-code"><?php
          echo $esc($labels['recovery_code']);
      ?></label>
      <input id="recovery-code" name="recovery_code"
        autocomplete="one-time-code" required>
      <button type="submit"><?php echo $esc($labels['use_recovery']); ?></button>
    </form>
    <form method="post">
      <input type="hidden" name="action" value="mfa_cancel">
      <input type="hidden" name="csrf_token"
        value="<?php echo $esc($mfa['cancel_csrf_token']); ?>">
      <button type="submit"><?php echo $esc($labels['cancel']); ?></button>
    </form>
  <?php elseif ($view === 'recovery_codes'): ?>
    <p role="status"><?php echo $esc($labels['save_codes']); ?></p>
    <ul><?php foreach ($mfa['recovery_codes'] as $code): ?>
      <li><code><?php echo $esc($code); ?></code></li>
    <?php endforeach; ?></ul>
  <?php else: ?>
    <p role="alert"><?php echo $esc($labels['expired']); ?></p>
  <?php endif; ?>
</main>
