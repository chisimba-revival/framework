<?php
/** Course-entry confirmation for a course-aware deep link. @author Derek Keats */
$e = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$icons = $this->getObject('iconservice', 'ui');
?>
<section class="dashboard-panel course-launch" role="dialog" aria-labelledby="course-launch-title">
    <header class="dashboard-panel__header"><div>
        <p class="dashboard-eyebrow">Course required</p>
        <h1 id="course-launch-title">You are not in this course</h1>
        <p>Would you like to enter <?php echo $e($courseLaunchTitle); ?> now?</p>
    </div><?php echo $icons->render('log-in', array('decorative'=>true)); ?></header>
    <form class="chisimba-form" method="post" action="<?php echo $e($this->uri(array('action'=>'entercourseactivity'), 'context')); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $e($courseLaunchCsrf); ?>">
        <input type="hidden" name="coursecode" value="<?php echo $e($courseLaunchTarget['coursecode']); ?>">
        <input type="hidden" name="targetmodule" value="<?php echo $e($courseLaunchTarget['module']); ?>">
        <input type="hidden" name="targetaction" value="<?php echo $e($courseLaunchTarget['action']); ?>">
        <input type="hidden" name="targetparams" value="<?php echo $e($this->getParam('targetparams', '')); ?>">
        <div class="chisimba-actions">
            <button type="submit"><?php echo $icons->render('log-in', array('decorative'=>true)); ?> Enter course</button>
            <a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array(), 'mylearning')); ?>"><?php echo $icons->render('arrow-left', array('decorative'=>true)); ?> Return to My Learning</a>
        </div>
    </form>
</section>
