<?php
/** Site category editing is independent of child links. @author Derek Keats */
$this->setLayoutTemplate('admin_layout_tpl.php');
$lang=$this->getObject('language','language');
$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$t=fn($key)=>$e(html_entity_decode($lang->code2Txt('mod_toolbar_nav_'.$key,'toolbar'),ENT_QUOTES|ENT_HTML5,'UTF-8'));
?>
<div class="chisimba-flow chisimba-full-width-page">
<h1><?php echo $t($categoryId===''?'category_add':'category_edit'); ?></h1>
<?php echo $this->getObject('contextualhelp','help')->show('toolbar','navigation'); ?>
<?php if($categoryError): ?><p class="chisimba-notice chisimba-notice--error" role="alert"><?php echo $t($categoryError); ?></p><?php endif; ?>
<form class="chisimba-form-card chisimba-form-card--wide" method="post" action="<?php echo $e($this->uri(['action'=>'savecategory'],'toolbar')); ?>">
<input type="hidden" name="toolbar_csrf" value="<?php echo $e($toolbarCsrf); ?>"><input type="hidden" name="id" value="<?php echo $e($categoryId); ?>">
<div class="chisimba-form-grid">
<div class="chisimba-form-field"><label for="category-label"><?php echo $t('menu_title'); ?></label><input id="category-label" name="label" required maxlength="150" value="<?php echo $e($category['label']??''); ?>"></div>
<div class="chisimba-form-field"><label for="category-position"><?php echo $t('category_position'); ?></label><input id="category-position" name="position" type="number" min="0" max="999" value="<?php echo $e($category['position']??''); ?>" aria-describedby="category-position-help"><small id="category-position-help"><?php echo $t('category_position_help'); ?></small></div>
</div>
<div class="chisimba-form-actions chisimba-form-actions--equal"><button type="submit"><?php echo $this->getObject('iconservice','ui')->render('save',['decorative'=>true]); ?> <?php echo $e($lang->code2Txt('word_save','system')); ?></button><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(['action'=>'editlinks'],'toolbar')); ?>"><?php echo $t('cancel'); ?></a></div>
</form></div>
