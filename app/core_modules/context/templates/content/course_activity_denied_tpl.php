<?php
/** Safe denial for a course-aware deep link. @author Derek Keats */
$e = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$icons = $this->getObject('iconservice', 'ui');
$text = function ($code, $fallback) { return $this->objLanguage->code2Txt($code, 'context', null, $fallback); };
?>
<section class="dashboard-panel course-launch" role="alert" aria-labelledby="course-launch-denied-title">
    <header class="dashboard-panel__header"><div>
        <p class="dashboard-eyebrow"><?php echo $e($text('mod_context_accessrequiredheading', '[-context-] access required')); ?></p>
        <h1 id="course-launch-denied-title"><?php echo $e($text('mod_context_deniednotmember', 'You are not a member of this [-context-]')); ?></h1>
        <p><?php echo $e($text('mod_context_deniedactivity', 'This activity cannot be opened until you have access to its [-context-].')); ?></p>
    </div><?php echo $icons->render('shield-alert', array('decorative'=>true)); ?></header>
    <div class="chisimba-actions">
        <a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array(), 'mylearning')); ?>"><?php echo $icons->render('arrow-left', array('decorative'=>true)); ?> Return to My Learning</a>
    </div>
</section>
