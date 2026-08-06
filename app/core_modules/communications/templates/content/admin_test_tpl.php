<?php
/**
 * Communications diagnostic and deliberate test page.
 *
 * @category  Chisimba
 * @package   communications
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
$esc = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
?>
<div class="communications-admin-diagnostic">
  <h1><?php echo $esc($communicationsText['title']); ?></h1>
  <p><?php echo $esc($communicationsText['intro']); ?></p>
  <?php if (!is_array($diagnosticAttempt)): ?>
    <p><?php echo $esc($communicationsText['noattempt']); ?></p>
  <?php else: ?>
    <dl>
      <dt><?php echo $esc($communicationsText['messageid']); ?></dt><dd><?php echo $esc($diagnosticAttempt['outbox_id'] ?? ''); ?></dd>
      <dt><?php echo $esc($communicationsText['attemptnumber']); ?></dt><dd><?php echo $esc($diagnosticAttempt['attempt_number'] ?? ''); ?></dd>
      <dt><?php echo $esc($communicationsText['transport']); ?></dt><dd><?php echo $esc($diagnosticAttempt['transport'] ?? ''); ?></dd>
      <dt><?php echo $esc($communicationsText['outcome']); ?></dt><dd><?php echo $esc($diagnosticAttempt['outcome'] ?? ''); ?></dd>
      <dt><?php echo $esc($communicationsText['responsecode']); ?></dt><dd><?php echo $esc($diagnosticAttempt['response_code'] ?? ''); ?></dd>
      <dt><?php echo $esc($communicationsText['errordetail']); ?></dt><dd><pre><?php echo $esc($diagnosticAttempt['error_detail'] ?? ''); ?></pre></dd>
      <dt><?php echo $esc($communicationsText['created']); ?></dt><dd><?php echo $esc($diagnosticAttempt['date_created'] ?? ''); ?></dd>
    </dl>
  <?php endif; ?>
  <?php if (is_array($sendResult)): ?>
    <h2><?php echo $esc($communicationsText['result']); ?></h2>
    <p class="<?php echo !empty($sendResult['ok']) ? 'success' : 'error'; ?>">
      <?php echo $esc(!empty($sendResult['ok']) ? $communicationsText['success'] : $communicationsText['failure']); ?>
    </p>
    <dl>
      <dt><?php echo $esc($communicationsText['queuecode']); ?></dt><dd><?php echo $esc($sendResult['queued']['code'] ?? $sendResult['code'] ?? ''); ?></dd>
      <dt><?php echo $esc($communicationsText['finalstatus']); ?></dt><dd><?php echo $esc($sendResult['status']['status'] ?? ''); ?></dd>
      <dt><?php echo $esc($communicationsText['messageid']); ?></dt><dd><?php echo $esc($sendResult['queued']['messageId'] ?? ''); ?></dd>
    </dl>
  <?php endif; ?>
  <p><?php echo $esc($communicationsText['formintro']); ?></p>
  <form method="post" action="index.php?module=communications&amp;action=sendtest">
    <input type="hidden" name="csrf_token" value="<?php echo $esc($sendToken); ?>">
    <label for="communications-test-recipient"><?php echo $esc($communicationsText['recipient']); ?></label>
    <input id="communications-test-recipient" name="recipient" type="email" required>
    <button type="submit"><?php echo $esc($communicationsText['button']); ?></button>
  </form>
</div>
