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
$filters = isset($aiFilters) && is_array($aiFilters) ? $aiFilters : array('days'=>30);
$icons = $this->getObject('iconservice', 'ui');
$totalTokens = (int) ($usage['inputTokens'] ?? 0) + (int) ($usage['outputTokens'] ?? 0);
$maxTrend = 1;
foreach ((array) ($usage['trend'] ?? array()) as $point) {
    $maxTrend = max($maxTrend, (int) ($point['requests'] ?? 0));
}
$metric = static function ($icon, $label, $value, $tone = '') use ($icons, $esc) {
    return '<article class="dashboard-metric' . ($tone ? ' dashboard-metric--' . $esc($tone) : '') . '">'
        . '<span class="dashboard-metric__icon" title="' . $esc($label) . '">'
        . $icons->render($icon, array('decorative'=>true)) . '</span><div><span>'
        . $esc($label) . '</span><strong>' . $esc($value) . '</strong></div></article>';
};
?>
<div class="ai-dashboard">
    <section class="dashboard-panel ai-dashboard__hero">
        <header class="dashboard-panel__header"><div><p class="dashboard-eyebrow">System intelligence</p>
            <h1><?php echo $esc($text['title'] ?? 'AI services dashboard'); ?></h1>
            <p>Usage, reliability and cost without retaining prompts or responses.</p></div>
            <span class="chisimba-state-action <?php echo $configured ? 'chisimba-state-action--online' : 'chisimba-state-action--offline'; ?>"
                title="<?php echo $esc($configured ? 'AI provider configured' : 'AI provider unavailable'); ?>">
                <?php echo $icons->render($configured ? 'circle-check-big' : 'circle-alert', array('decorative'=>true)); ?>
                <span><?php echo $esc(ucfirst($provider)); ?></span>
            </span>
        </header>
        <form class="dashboard-filterbar" method="get" action="index.php">
            <input type="hidden" name="module" value="ai">
            <label><span>Period</span><select name="days">
                <?php foreach (array(7=>'7 days',30=>'30 days',90=>'90 days') as $days=>$label): ?>
                    <option value="<?php echo $days; ?>"<?php echo (int)($filters['days'] ?? 30)===$days?' selected':''; ?>><?php echo $esc($label); ?></option>
                <?php endforeach; ?>
            </select></label>
            <label><span>Module</span><select name="consumer"><option value="">All modules</option>
                <?php foreach ((array)($usage['availableConsumers'] ?? array()) as $value): ?><option value="<?php echo $esc($value); ?>"<?php echo ($filters['consumer'] ?? '')===$value?' selected':''; ?>><?php echo $esc(ucwords(str_replace('_',' ',$value))); ?></option><?php endforeach; ?>
            </select></label>
            <label><span>Provider</span><select name="provider"><option value="">All providers</option>
                <?php foreach ((array)($usage['availableProviders'] ?? array()) as $value): ?><option value="<?php echo $esc($value); ?>"<?php echo ($filters['provider'] ?? '')===$value?' selected':''; ?>><?php echo $esc(ucfirst($value)); ?></option><?php endforeach; ?>
            </select></label>
            <button class="chisimba-icon-button" type="submit" title="Apply dashboard filters" aria-label="Apply dashboard filters"><?php echo $icons->render('list-filter', array('decorative'=>true)); ?></button>
        </form>
        <div class="dashboard-metric-grid">
            <?php
            echo $metric('sparkles', 'Requests', number_format((int)($usage['requests'] ?? 0)));
            echo $metric('badge-check', 'Success rate', number_format((float)($usage['successRate'] ?? 0), 1) . '%', ((float)($usage['successRate'] ?? 0) >= 95 ? 'success' : 'warning'));
            echo $metric('braces', 'Tokens', number_format($totalTokens));
            echo $metric('timer', '95th percentile', number_format((int)($usage['durationP95Ms'] ?? 0)) . ' ms');
            echo $metric('circle-dollar-sign', 'Estimated cost', number_format((float)($usage['estimatedCost'] ?? 0), 4));
            echo $metric('triangle-alert', 'Failures', number_format((int)($usage['failed'] ?? 0)), ((int)($usage['failed'] ?? 0) > 0 ? 'danger' : 'success'));
            ?>
        </div>
    </section>

    <div class="dashboard-split">
        <section class="dashboard-panel dashboard-chart-panel"><header><div><p class="dashboard-eyebrow">Activity</p><h2>Request pulse</h2></div><span><?php echo (int)($filters['days'] ?? 30); ?> day view</span></header>
            <div class="dashboard-bar-chart" role="img" aria-label="AI request volume for recent days">
                <?php foreach ((array)($usage['trend'] ?? array()) as $date=>$point): $height=max(4,(int)round(((int)$point['requests']/$maxTrend)*100)); ?>
                    <div class="dashboard-bar-chart__column" title="<?php echo $esc($date . ': ' . (int)$point['requests'] . ' requests'); ?>"><span style="height:<?php echo $height; ?>%"<?php echo !empty($point['failed'])?' class="has-failures"':''; ?>></span><small><?php echo $esc(substr($date,8,2)); ?></small></div>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="dashboard-panel dashboard-breakdown"><header><div><p class="dashboard-eyebrow">Consumers</p><h2>Where AI is working</h2></div></header>
            <div class="dashboard-breakdown__list">
                <?php if (empty($usage['consumers'])): ?><p class="dashboard-muted">No AI requests in this period.</p><?php endif; ?>
                <?php foreach (array_slice((array)($usage['consumers'] ?? array()),0,6,true) as $name=>$count): ?>
                    <div><span><?php echo $icons->render('boxes', array('decorative'=>true)); ?> <?php echo $esc(ucwords(str_replace('_',' ',$name))); ?></span><strong><?php echo number_format($count); ?></strong></div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <?php if (!empty($usage['errors'])): ?>
    <section class="dashboard-panel dashboard-attention"><header><div><p class="dashboard-eyebrow">Needs attention</p><h2>Recent failure patterns</h2></div><?php echo $icons->render('triangle-alert', array('decorative'=>true)); ?></header>
        <div class="dashboard-breakdown__list"><?php foreach (array_slice($usage['errors'],0,5,true) as $error=>$count): ?><div><span><?php echo $esc(str_replace('_',' ',$error)); ?></span><strong><?php echo (int)$count; ?></strong></div><?php endforeach; ?></div>
    </section>
    <?php endif; ?>

    <section class="dashboard-panel ai-dashboard__diagnostic">
        <h2><?php echo $esc($text['diagnostic'] ?? ''); ?></h2>
        <p><?php echo $esc($text['diagnosticintro'] ?? ''); ?></p>
        <form method="post" action="index.php?module=ai&amp;action=diagnostic">
            <input type="hidden" name="csrf_token" value="<?php echo $esc($aiToken ?? ''); ?>" />
            <button class="button" type="submit"><?php echo $icons->render('stethoscope', array('decorative'=>true)); ?> <?php echo $esc($text['run'] ?? ''); ?></button>
        </form>
    </section>

    <section class="dashboard-panel ai-dashboard__diagnostic">
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
    </section>
</div>
