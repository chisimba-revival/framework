<?php
/** Author biography editor and preview. @author Derek Keats */
$e = static fn($value) => htmlspecialchars(is_scalar($value) ? (string)$value : '', ENT_QUOTES, 'UTF-8');
$lang = $this->getObject('language', 'language');
$t = static fn($key) => ucfirst($lang->code2Txt('mod_userdetails_'.$key, 'userdetails'));
$icons = $this->getObject('iconservice', 'ui');
$user = $this->getObject('user', 'security');
$value = $this->getVar('biographyValue');
$errors = $this->getVar('biographyErrors');
ob_start();
?>
<section class="chisimba-form-page">
<div class="chisimba-form-card">
<header class="chisimba-form-card__header"><h1><?php echo $e($t('author_bio')); ?></h1><p><?php echo $e($t('bio_intro')); ?></p></header>
<?php if ($errors): ?><div class="chisimba-form-notice error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?php echo $e($t($error)); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if ($this->getVar('biographySaved')): ?><p class="chisimba-form-notice success" role="status"><?php echo $e($t('bio_saved')); ?></p><?php endif; ?>
<form class="chisimba-form chisimba-form-section" method="post" action="<?php echo $e($this->uri(array('action'=>'biography'), 'userdetails')); ?>">
<input type="hidden" name="csrf_token" value="<?php echo $e($this->getVar('biographyToken')); ?>">
<div class="chisimba-biography-card"><div class="chisimba-biography-portrait"><?php echo $this->getObject('authorbiographyrenderer','userdetails')->portrait($user->userId(), $user->fullName()); ?></div><div class="chisimba-biography-copy"><h2><?php echo $e($t('bio_photo')); ?></h2><p><?php echo $e($t('bio_photo_help')); ?></p><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array('action'=>'main'), 'userdetails')); ?>"><?php echo $icons->render('image', array('decorative'=>true)); ?><span><?php echo $e($t('bio_change_photo')); ?></span></a></div></div>
<div class="chisimba-form-field chisimba-form-section"><label for="author-biography"><?php echo $e($t('bio_text')); ?></label><textarea id="author-biography" name="biography" rows="9" maxlength="6000" required aria-describedby="biography-help" <?php if (isset($errors['biography'])) echo 'aria-invalid="true"'; ?>><?php echo $e($value['biography']); ?></textarea><small id="biography-help" class="chisimba-field-help"><?php echo $e($t('bio_text_help')); ?></small></div>
<details class="chisimba-form-section" <?php if (isset($errors['links'])) echo 'open'; ?>><summary><?php echo $e($t('bio_links')); ?></summary><p class="chisimba-field-help"><?php echo $e($t('bio_links_help')); ?></p>
<?php for ($i=0; $i<5; $i++): $link = is_array($value['links'][$i] ?? null) ? $value['links'][$i] : array(); ?>
<fieldset class="chisimba-form-section"><legend><?php echo $e($t('bio_link')).' '.($i+1); ?></legend><div class="chisimba-form-grid"><div class="chisimba-form-field"><label for="bio-label-<?php echo $i; ?>"><?php echo $e($t('bio_link_label')); ?></label><input id="bio-label-<?php echo $i; ?>" type="text" name="links[<?php echo $i; ?>][label]" maxlength="80" value="<?php echo $e($link['label'] ?? ''); ?>"></div><div class="chisimba-form-field"><label for="bio-url-<?php echo $i; ?>"><?php echo $e($t('bio_link_url')); ?></label><input id="bio-url-<?php echo $i; ?>" type="url" name="links[<?php echo $i; ?>][url]" maxlength="2048" placeholder="https://" value="<?php echo $e($link['url'] ?? ''); ?>"></div></div></fieldset>
<?php endfor; ?></details>
<div class="chisimba-form-actions"><button class="button chisimba-button-primary" type="submit"><?php echo $icons->render('save', array('decorative'=>true)); ?><span><?php echo $e($t('bio_save')); ?></span></button><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array('action'=>'main'), 'userdetails')); ?>"><?php echo $icons->render('arrow-left', array('decorative'=>true)); ?><span><?php echo $e($t('bio_back')); ?></span></a></div>
</form>
<?php if (!$errors && trim($value['biography']) !== ''): ?><section class="chisimba-form-section"><h2><?php echo $e($t('bio_preview')); ?></h2><?php echo $this->getObject('authorbiographyrenderer','userdetails')->person(array_merge($value,array('userid'=>$user->userId(),'name'=>$user->fullName())), true); ?></section><?php endif; ?>
</div></section>
<?php $this->setVar('pageContent', ob_get_clean()); ?>
