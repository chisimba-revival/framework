<?php
/** Profile workspace, reusing account validation and the shared photo picker. @author Derek Keats */
$e = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$lang = $this->getObject('language', 'language');
$t = static fn($key) => ucfirst($lang->code2Txt('mod_userdetails_'.$key, 'userdetails'));
$icons = $this->getObject('iconservice', 'ui');
$ops = $this->getObject('userdetailsops', 'userdetails');
$user = $this->getObject('user', 'security');
ob_start();
?>
<?php if ($this->getObject('modules','modulecatalogue')->checkIfRegistered('help')) echo $this->getObject('contextualhelp','help')->show('userdetails','author-biography'); ?>
<section class="chisimba-form-page"><div class="chisimba-form-card chisimba-form-card--wide">
<nav class="chisimba-form-actions" aria-label="<?php echo $e($t('profile_navigation')); ?>"><span aria-current="page"><?php echo $e($t('profile_details')); ?></span><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array('action'=>'biography'),'userdetails')); ?>"><?php echo $icons->render('contact',array('decorative'=>true)); ?><span><?php echo $e($t('my_bio')); ?></span></a></nav>
<header class="chisimba-form-card__header"><h1><?php echo $e($t('profile_details')); ?></h1><p><?php echo $e($user->fullName()); ?></p></header>
<div class="chisimba-profile-columns"><section class="chisimba-form-section"><?php echo $ops->showMain(); ?></section>
<aside id="profile-photo" class="chisimba-form-section"><h2><?php echo $e($t('bio_photo')); ?></h2><div class="chisimba-biography-portrait"><?php echo $this->getObject('authorbiographyrenderer','userdetails')->portrait($user->userId(),$user->fullName()); ?></div><?php echo $ops->showUserImage(); ?>
<p><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array('action'=>'biography'),'userdetails')); ?>"><?php echo $icons->render('arrow-left',array('decorative'=>true)); ?><span><?php echo $e($t('return_bio')); ?></span></a></p>
<?php if ($this->getObject('modules','modulecatalogue')->checkIfRegistered('schoolusers')) echo $ops->showGrades(); ?>
</aside></div></div></section>
<?php $this->setVar('pageContent', ob_get_clean()); ?>
