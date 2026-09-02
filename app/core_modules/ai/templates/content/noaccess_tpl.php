<?php
/** Administrator-only AI operations access boundary. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) { die(); }
$icon = $this->getObject('iconservice', 'ui');
?>
<section class="dashboard-panel dashboard-empty-state" role="alert">
    <span class="dashboard-empty-state__icon" aria-hidden="true"><?php echo $icon->render('shield-alert', array('decorative'=>true)); ?></span>
    <div><h1>Administrator access required</h1><p>AI service operations and usage information are available only to site administrators.</p></div>
</section>
