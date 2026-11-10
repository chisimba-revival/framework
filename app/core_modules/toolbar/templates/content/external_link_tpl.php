<?php
/** External navigation editor. @author Derek Keats */
$this->setLayoutTemplate('admin_layout_tpl.php');
$lang=$this->getObject('language','language');
$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$t=fn($key)=>$e(html_entity_decode($lang->code2Txt('mod_toolbar_nav_'.$key,'toolbar'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
$v=fn($key,$default='')=>$e($externalLink[$key]??$default);
?>
<div class="chisimba-flow chisimba-full-width-page">
<h1><?php echo $t($externalId===''?'external_add':'external_edit'); ?></h1>
<?php echo $this->getObject('contextualhelp','help')->show('toolbar','navigation'); ?>
<?php if($externalError): ?><p class="chisimba-notice chisimba-notice--error" role="alert"><?php echo $t('external_'.$externalError); ?></p><?php endif; ?>
<form class="chisimba-form-card chisimba-form-card--wide" method="post" action="<?php echo $e($this->uri(['action'=>'saveexternal'],'toolbar')); ?>">
<input type="hidden" name="toolbar_csrf" value="<?php echo $e($toolbarCsrf); ?>">
<input type="hidden" name="id" value="<?php echo $e($externalId); ?>">
<div class="chisimba-form-grid">
<div class="chisimba-form-field chisimba-form-field--full"><label for="external-placement"><?php echo $t('placement'); ?></label><select id="external-placement" name="placement" aria-describedby="external-placement-help"><?php foreach(['site','learning'] as $place): ?><option value="<?php echo $place; ?>"<?php echo ($externalLink['placement']??'site')===$place?' selected':''; ?>><?php echo $t('placement_'.$place); ?></option><?php endforeach; ?></select><small id="external-placement-help"><?php echo $t('placement_help'); ?></small></div>
<div class="chisimba-form-field"><label for="external-label"><?php echo $t('link_label'); ?></label><input type="text" id="external-label" name="label" required maxlength="150" value="<?php echo $v('label'); ?>"></div>
<div class="chisimba-form-field"><label for="external-url"><?php echo $t('external_url'); ?></label><input type="url" id="external-url" name="url" required maxlength="2048" placeholder="https://" value="<?php echo $v('url'); ?>" aria-describedby="external-url-help"><small id="external-url-help"><?php echo $t('external_url_help'); ?></small></div>
<div class="chisimba-form-field"><label for="external-group"><?php echo $t('menu_title'); ?></label><select data-site-link-field id="external-group" name="group_key"><option value=""><?php echo $t('direct_links'); ?></option>
<?php foreach($externalGroups as $key=>$name): ?><option value="<?php echo $e($key); ?>"<?php echo ($externalLink['group_key']??'')===$key?' selected':''; ?>><?php echo $e($name); ?></option><?php endforeach; ?></select></div>
<div class="chisimba-form-field"><label for="external-position"><?php echo $t('link_order'); ?></label><input type="number" id="external-position" name="position" min="0" max="999" value="<?php echo $v('position'); ?>" aria-describedby="link-order-help"><small id="link-order-help"><?php echo $t('link_order_help'); ?></small></div>
<div class="chisimba-form-field"><label for="external-audience"><?php echo $t('audience'); ?></label><select data-site-link-field id="external-audience" name="audience"><?php foreach(['everyone','visitors','members'] as $a): ?><option value="<?php echo $a; ?>"<?php echo ($externalLink['audience']??'everyone')===$a?' selected':''; ?>><?php echo $t('audience_'.$a); ?></option><?php endforeach; ?></select></div>
</div>
<div class="chisimba-form-actions chisimba-form-actions--equal"><button type="submit"><?php echo $this->getObject('iconservice','ui')->render('save',['decorative'=>true]); ?> <?php echo $e($lang->code2Txt('word_save','system')); ?></button><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(['action'=>'editlinks'],'toolbar')); ?>"><?php echo $t('cancel'); ?></a></div>
</form></div>

<script defer src="<?php echo $e($this->getResourceUri('navigation-editor.js','toolbar')); ?>"></script>
