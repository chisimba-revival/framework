<?php
/** First step: choose a module by its human-readable name. @author Derek Keats */
$this->setLayoutTemplate('admin_layout_tpl.php');
$lang=$this->getObject('language','language');$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$t=fn($k)=>$e(html_entity_decode($lang->code2Txt('mod_toolbar_nav_'.$k,'toolbar'),ENT_QUOTES|ENT_HTML5,'UTF-8'));
?>
<div class="chisimba-flow chisimba-full-width-page">
<header class="chisimba-cluster chisimba-section-heading"><h1><?php echo $t('add_module_link'); ?></h1><?php echo $this->getObject('contextualhelp','help')->show('toolbar','navigation'); ?></header>
<form class="chisimba-form-card chisimba-form-card--wide chisimba-flow" method="get" action="<?php echo $e($this->uri([],'toolbar')); ?>">
<input type="hidden" name="module" value="toolbar"><input type="hidden" name="action" value="addsite">
<p><?php echo $t('module_choose_help'); ?></p>
<div class="chisimba-form-field"><label for="site-module"><?php echo $t('module_choose'); ?></label><select id="site-module" name="modulename" required><option value=""><?php echo $t('module_choose'); ?></option><?php foreach($siteModuleChoices as $key=>$label): ?><option value="<?php echo $e($key); ?>"><?php echo $e($label); ?></option><?php endforeach; ?></select></div>
<div class="chisimba-form-actions chisimba-form-actions--equal"><button type="submit"><?php echo $t('continue'); ?></button><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(['action'=>'editlinks'],'toolbar')); ?>"><?php echo $t('cancel'); ?></a></div>
</form></div>
