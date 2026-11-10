<?php
/** Operator-facing site link form: presentation comes from registered defaults. @author Derek Keats */
$this->setLayoutTemplate('admin_layout_tpl.php');
$lang=$this->getObject('language','language');$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$t=fn($k)=>$e(html_entity_decode($lang->code2Txt('mod_toolbar_nav_'.$k,'toolbar'),ENT_QUOTES|ENT_HTML5,'UTF-8'));
$editing=$mode==='edit';$parts=$editing?explode('|',$data['category']):[];
$values=$siteSubmitted??['position'=>$editing?(int)substr($parts[0],5):'','groupCode'=>$parts[5]??'',
 'menuLabel'=>$data['menu_label']??'','destination'=>$editing?'current':'default','adminOnly'=>$data['adminonly']??0,'dependsContext'=>$data['dependscontext']??0,'permissions'=>$selectedRight];
?>
<div class="chisimba-flow chisimba-full-width-page">
<header class="chisimba-cluster chisimba-section-heading"><h1><?php echo ($moduleName==='sitepages'?$t($editing?'page_edit':'page_add'):$t($editing?'edit':'add_module_link').' — '.$e($siteModuleTitle)); ?></h1><?php echo $this->getObject('contextualhelp','help')->show('toolbar','navigation'); ?></header>
<?php if(!empty($siteInputError)): ?><p class="chisimba-notice chisimba-notice--error" role="alert"><?php echo $t($siteInputError); ?></p><?php endif; ?>
<form class="chisimba-form-card chisimba-form-card--wide chisimba-flow" method="post" action="<?php echo $e($this->uri(['action'=>'savesite'],'toolbar')); ?>">
<input type="hidden" name="toolbar_csrf" value="<?php echo $e($toolbarCsrf); ?>"><input type="hidden" name="moduleName" value="<?php echo $e($moduleName); ?>">
<?php if($editing): ?><input type="hidden" name="id" value="<?php echo $e($data['id']); ?>"><?php endif; ?>
<?php if($moduleName!=='sitepages'): ?><p><?php echo $t('module_link_help'); ?></p><?php endif; ?>
<?php if($moduleName==='sitepages'): ?><p><?php echo $t('page_hint'); ?></p><?php endif; ?>
<div class="chisimba-form-grid">
<div class="chisimba-form-field"><label for="site-destination"><?php echo $t($moduleName==='sitepages'?'page_field':'destination'); ?></label><select id="site-destination" name="destination" required><?php if($moduleName==='sitepages' && !$editing): ?><option value=""><?php echo $t('page_choose'); ?></option><?php endif; ?><?php foreach($siteDestinations as $key=>$destination): ?><option value="<?php echo $e($key); ?>"<?php echo $values['destination']===$key?' selected':''; ?>><?php echo $e($destination['label']).($key==='default'?' — '.$t('default_destination'):($key==='current'?' — '.$t('saved_destination'):'')); ?></option><?php endforeach; ?></select></div>
<div class="chisimba-form-field"><label for="site-label"><?php echo $t('menu_label'); ?></label><input id="site-label" name="menuLabel" maxlength="150" value="<?php echo $e($values['menuLabel']??''); ?>" aria-describedby="site-label-help"><small id="site-label-help"><?php echo $t('menu_label_help'); ?></small></div>
<div class="chisimba-form-field"><label for="site-category"><?php echo $t('group'); ?></label><select id="site-category" name="groupCode"><option value=""><?php echo $t('direct_links'); ?></option><?php foreach($siteGroups as $key=>$label): ?><option value="<?php echo $e($key); ?>"<?php echo $values['groupCode']===$key?' selected':''; ?>><?php echo $e($label); ?></option><?php endforeach; ?></select></div>
<div class="chisimba-form-field"><label for="site-order"><?php echo $t('link_order'); ?></label><input id="site-order" name="position" type="number" min="0" max="999" value="<?php echo $e($values['position']); ?>" aria-describedby="site-order-help"><small id="site-order-help"><?php echo $t('link_order_help'); ?></small></div>
</div>
<details class="chisimba-form-disclosure"<?php echo $values['adminOnly']||$values['dependsContext']||$values['permissions']!==''?' open':''; ?>><summary><?php echo $t('access_settings'); ?></summary>
<div class="chisimba-flow">
<label class="chisimba-choice"><input type="checkbox" name="adminOnly" value="1"<?php echo $values['adminOnly']?' checked':''; ?>><?php echo $t('admin_only'); ?></label>
<label class="chisimba-choice"><input type="checkbox" name="dependsContext" value="1"<?php echo $values['dependsContext']?' checked':''; ?>><?php echo $t('context_only'); ?></label>
<div class="chisimba-form-field"><label for="site-permissions"><?php echo $t('permissions'); ?></label><select id="site-permissions" name="permissions"><option value=""><?php echo $t('audience_everyone'); ?></option><?php foreach($rightOptions as $right): ?><option value="<?php echo $e($right['rightId']); ?>"<?php echo (string)$values['permissions']===(string)$right['rightId']?' selected':''; ?>><?php echo $e($right['name']); ?></option><?php endforeach; ?></select></div>
</div></details>
<div class="chisimba-form-actions chisimba-form-actions--equal"><button type="submit"><?php echo $this->getObject('iconservice','ui')->render('save',['decorative'=>true]); ?> <?php echo $e($lang->code2Txt('word_save','system')); ?></button><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(['action'=>'editlinks'],'toolbar')); ?>"><?php echo $t('cancel'); ?></a><?php if(!$editing): ?><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(['action'=>'addsite'],'toolbar')); ?>"><?php echo $t('change_module'); ?></a><?php endif; ?></div>
</form></div>
