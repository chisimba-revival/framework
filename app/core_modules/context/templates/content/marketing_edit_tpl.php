<?php
/** Course marketing editor. @author Derek Keats */
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8',false);
$lang=$this->getObject('language','language');$t=static fn($k)=>ucfirst($lang->code2Txt('mod_context_'.$k,'context'));
$course=$this->getVar('marketingCourse');$page=$this->getVar('marketingPage');$content=$page['content'];$icons=$this->getObject('iconservice','ui');
?>
<section class="chisimba-form-page"><div class="chisimba-form-card chisimba-form-card--wide"><h1><?php echo $e($t('marketing_edit')); ?></h1><h2><?php echo $e($course['title']); ?></h2><p><?php echo $e($t('marketing_help')); ?></p>
<?php foreach ($this->getVar('marketingErrors') as $error): ?><p role="alert"><?php echo $e($t($error)); ?></p><?php endforeach; ?>
<?php if ($this->getParam('saved')==='1'): ?><p role="status"><?php echo $e($t('marketing_saved')); ?></p><?php endif; ?>
<form class="chisimba-form" method="post" action="<?php echo $e($this->uri(array('action'=>'editmarketing','contextcode'=>$course['contextcode']),'context')); ?>">
<input type="hidden" name="csrf_token" value="<?php echo $e($this->getVar('marketingToken')); ?>">
<?php foreach (array('introduction'=>3000,'audience'=>3000,'outcomes'=>6000,'outline'=>6000) as $field=>$limit): ?>
<div class="chisimba-form-field"><label for="marketing-<?php echo $field; ?>"><?php echo $e($t('marketing_'.$field)); ?></label><textarea id="marketing-<?php echo $field; ?>" name="<?php echo $field; ?>" rows="4" maxlength="<?php echo $limit; ?>"><?php echo $e($content[$field]); ?></textarea></div>
<?php endforeach; ?>
<div class="chisimba-form-field"><label for="marketing-video"><?php echo $e($t('marketing_video')); ?></label><input id="marketing-video" name="video_url" type="url" value="<?php echo $e($content['video_url']); ?>" maxlength="2048"><small class="chisimba-field-help"><?php echo $e($t('marketing_video_help')); ?></small></div>
<label class="chisimba-checkbox-field"><input type="checkbox" name="published" value="1" <?php if($page['published'])echo 'checked'; ?>><?php echo $e($t('marketing_publish')); ?></label>
<div class="chisimba-form-actions"><button type="submit" class="button chisimba-button-primary"><?php echo $icons->render('save',array('decorative'=>true)); ?><span><?php echo $e($t('marketing_save')); ?></span></button><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array('action'=>'marketing','contextcode'=>$course['contextcode']),'context')); ?>"><?php echo $icons->render('eye',array('decorative'=>true)); ?><span><?php echo $e($t('marketing_preview')); ?></span></a></div></form>
</div></section>
