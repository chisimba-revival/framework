<?php
/**
 * AI services dashboard template.
 *
 * @category  Chisimba
 * @package   ai
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die(); }

$text = isset($aiText) && is_array($aiText) ? $aiText : array();
$status = isset($aiStatus) && is_array($aiStatus) ? $aiStatus : array();
$usage = isset($aiUsage) && is_array($aiUsage) ? $aiUsage : array();
$result = isset($aiDiagnostic) && is_array($aiDiagnostic) ? $aiDiagnostic : null;
$esc = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };

$provider = isset($status['provider']) ? $status['provider'] : '';
$model = isset($status['model']) ? $status['model'] : '';
$configured = !empty($status['configured']);
?>
<div class="ai-dashboard">
    <h1><?php echo $esc($text['title'] ?? ''); ?></h1>
    <p><?php echo $esc($text['intro'] ?? ''); ?></p>

    <div class="featurebox">
        <dl>
            <dt><?php echo $esc($text['provider'] ?? ''); ?></dt>
            <dd><?php echo $esc($provider); ?></dd>
            <dt><?php echo $esc($text['model'] ?? ''); ?></dt>
            <dd><?php echo $esc($model); ?></dd>
            <dt><?php echo $esc($text['configured'] ?? ''); ?></dt>
            <dd><?php echo $esc($configured ? ($text['yes'] ?? '') : ($text['no'] ?? '')); ?></dd>
            <dt><?php echo $esc($text['requests'] ?? ''); ?></dt>
            <dd><?php echo $esc($usage['requests'] ?? 0); ?></dd>
            <dt><?php echo $esc($text['inputtokens'] ?? ''); ?></dt>
            <dd><?php echo $esc($usage['inputTokens'] ?? 0); ?></dd>
            <dt><?php echo $esc($text['outputtokens'] ?? ''); ?></dt>
            <dd><?php echo $esc($usage['outputTokens'] ?? 0); ?></dd>
        </dl>
    </div>

    <div class="featurebox">
        <h2><?php echo $esc($text['diagnostic'] ?? ''); ?></h2>
        <p><?php echo $esc($text['diagnosticintro'] ?? ''); ?></p>
        <form method="post" action="index.php?module=ai&amp;action=diagnostic">
            <input type="hidden" name="csrf_token" value="<?php echo $esc($aiToken ?? ''); ?>" />
            <button type="submit"><?php echo $esc($text['run'] ?? ''); ?></button>
        </form>
    </div>

    <div class="featurebox">
        <h2><?php echo $esc($text['result'] ?? ''); ?></h2>
        <?php if ($result === null): ?>
            <p><?php echo $esc($text['notrun'] ?? ''); ?></p>
        <?php elseif (!empty($result['ok'])): ?>
            <p><strong><?php echo $esc($text['success'] ?? ''); ?></strong></p>
            <dl>
                <dt><?php echo $esc($text['message'] ?? ''); ?></dt>
                <dd><?php echo $esc($result['data']['message'] ?? ''); ?></dd>
                <dt><?php echo $esc($text['confidence'] ?? ''); ?></dt>
                <dd><?php echo $esc($result['data']['confidence'] ?? ''); ?></dd>
            </dl>
        <?php else: ?>
            <p><strong><?php echo $esc($text['failure'] ?? ''); ?></strong></p>
            <dl>
                <dt><?php echo $esc($text['error'] ?? ''); ?></dt>
                <dd><?php echo $esc($result['error'] ?? ''); ?></dd>
            </dl>
        <?php endif; ?>
    </div>
</div>
