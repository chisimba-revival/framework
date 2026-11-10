<?php
/** Navigation workspace: named menu groups and bounded module configuration. */
$this->setLayoutTemplate('admin_layout_tpl.php');
$lang=$this->getObject('language','language');$icons=$this->getObject('iconservice','ui');
$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$t=fn($key)=>$e(html_entity_decode($lang->code2Txt('mod_toolbar_nav_'.$key,'toolbar'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
$url=fn($args)=>$e($this->uri($args,'toolbar'));
$groups=['site'=>[],'toolbar'=>[],'menu'=>[],'page'=>[]];
foreach($data as $row){$kind=str_starts_with($row['category'],'site_')?'site':(str_starts_with($row['category'],'menu_')?'menu':(str_starts_with($row['category'],'page_')?'page':'toolbar'));$groups[$kind][]=$row;}
$menuGroups=[]; $learningLinks=[];
foreach($navigationInventory as $item){if(($item['placement']??'site')==='learning'){$learningLinks[]=$item;continue;}$key=$item['group']===''?'direct':'group:'.$item['group'];$menuGroups[$key][]=$item;}
$actions=function($row,$edit,$delete,$module,$label)use($e,$t,$url,$icons,$toolbarCsrf){ ?>
<div class="chisimba-cluster chisimba-row-actions">
<a class="chisimba-icon-button" aria-label="<?php echo $t('edit').' '.$e($label); ?>" title="<?php echo $t('edit').' '.$e($label); ?>" href="<?php echo $url(['action'=>$edit,'id'=>$row['id'],'modulename'=>$module]); ?>"><?php echo $icons->render('pencil',['decorative'=>true]); ?></a>
<form method="post" action="<?php echo $url(['action'=>$delete]); ?>" data-navigation-confirm="<?php echo $t($delete==='deletecategory'?'category_delete_confirm':'delete_confirm'); ?>">
<input type="hidden" name="toolbar_csrf" value="<?php echo $e($toolbarCsrf); ?>"><input type="hidden" name="id" value="<?php echo $e($row['id']); ?>"><input type="hidden" name="modulename" value="<?php echo $e($module); ?>">
<button class="chisimba-icon-button chisimba-button-danger" type="submit" aria-label="<?php echo $t('delete').' '.$e($label); ?>" title="<?php echo $t('delete').' '.$e($label); ?>"><?php echo $icons->render('trash-2',['decorative'=>true]); ?></button></form>
</div>
<?php };
?>
<div class="chisimba-flow chisimba-full-width-page">
<header class="chisimba-cluster chisimba-section-heading"><h1><?php echo $t('configure'); ?></h1><?php echo $this->getObject('contextualhelp','help')->show('toolbar','navigation'); ?></header>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow" aria-labelledby="nav-layout-title">
<h2 id="nav-layout-title"><?php echo $t('profile'); ?></h2>
<form method="post" class="chisimba-form-actions" action="<?php echo $url(['action'=>'saveprofile']); ?>">
<input type="hidden" name="toolbar_csrf" value="<?php echo $e($toolbarCsrf); ?>"><label for="toolbar-profile"><?php echo $t('profile'); ?></label>
<select id="toolbar-profile" name="profile"><?php foreach(['dropdown','site','flat','elearning'] as $profile): ?><option value="<?php echo $profile; ?>"<?php echo $toolbarProfile===$profile?' selected':''; ?>><?php echo $t('profile_'.$profile); ?></option><?php endforeach; ?></select>
<button type="submit"><?php echo $icons->render('save',['decorative'=>true]); ?> <?php echo $e($lang->code2Txt('word_save','system')); ?></button></form>
<p><?php echo $t('hint'); ?></p>
</section>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow" aria-labelledby="nav-categories-title">
<header class="chisimba-cluster chisimba-section-heading"><h2 id="nav-categories-title"><?php echo $t('categories'); ?></h2><a class="button" href="<?php echo $url(['action'=>'addcategory']); ?>"><?php echo $icons->render('plus',['decorative'=>true]); ?> <?php echo $t('category_add'); ?></a></header>
<p><?php echo $t('categories_help'); ?></p>
<?php if(!empty($categoryError)): ?><p class="chisimba-notice chisimba-notice--error" role="alert"><?php echo $t($categoryError); ?></p><?php endif; ?>
<?php if(!$navigationCategories): ?><p><?php echo $t('categories_empty'); ?></p><?php else: ?>
<div class="chisimba-table-wrap"><table class="chisimba-table chisimba-management-table"><thead><tr><th scope="col"><?php echo $t('menu_title'); ?></th><th scope="col"><?php echo $t('category_position'); ?></th><th scope="col"><?php echo $t('actions'); ?></th></tr></thead><tbody>
<?php foreach($navigationCategories as $category): ?><tr><td><?php echo $e($category['label']); ?></td><td><?php echo (int)$category['position']; ?></td><td><?php $actions($category,'editcategory','deletecategory','toolbar',$category['label']); ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow" aria-labelledby="nav-overview-title">
<header class="chisimba-cluster chisimba-section-heading"><h2 id="nav-overview-title"><?php echo $t('menu_overview'); ?></h2><?php if($this->getObject('modules','modulecatalogue')->checkIfRegistered('sitepages')): ?><a class="button" href="<?php echo $url(['action'=>'addsite','modulename'=>'sitepages']); ?>"><?php echo $icons->render('plus',['decorative'=>true]); ?> <?php echo $t('page_add'); ?></a><?php endif; ?><a class="button" href="<?php echo $url(['action'=>'addsite']); ?>"><?php echo $icons->render('plus',['decorative'=>true]); ?> <?php echo $t('add_module_link'); ?></a><a class="button chisimba-button-secondary" href="<?php echo $url(['action'=>'addexternal']); ?>"><?php echo $icons->render('plus',['decorative'=>true]); ?> <?php echo $t('external_add'); ?></a></header>
<p><?php echo $t('overview_help'); ?></p>
<?php if(!$menuGroups): ?><p><?php echo $t('empty'); ?></p><?php endif; ?>
<?php foreach($menuGroups as $key=>$items): ?>
<section class="chisimba-flow"><h3><?php echo $key==='direct'?$t('direct_links'):$e($items[0]['groupLabel']); ?></h3>
<div class="chisimba-table-wrap"><table class="chisimba-table chisimba-management-table"><thead><tr><th scope="col"><?php echo $t('link_label'); ?></th><th scope="col"><?php echo $t('link_order'); ?></th><th scope="col"><?php echo $t('actions'); ?></th></tr></thead><tbody>
<?php foreach($items as $item): ?><tr><td><strong><?php echo $e($item['label']); ?></strong><div class="chisimba-muted"><?php echo $item['external']?$e($item['url']).' · '.$t('audience_'.$item['audience']):$e($item['module']); ?></div></td><td><?php echo $item['order']; ?></td><td><?php $actions($item,$item['external']?'editexternal':'editsite',$item['external']?'deleteexternal':'delete',$item['module'],$item['label']); ?></td></tr><?php endforeach; ?>
</tbody></table></div></section><?php endforeach; ?>
</section>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow" aria-labelledby="learning-links-title">
<header class="chisimba-cluster chisimba-section-heading"><h2 id="learning-links-title"><?php echo $t('placement_learning'); ?></h2><a class="button" href="<?php echo $url(['action'=>'addexternal','placement'=>'learning']); ?>"><?php echo $icons->render('plus',['decorative'=>true]); ?> <?php echo $t('learning_add'); ?></a></header>
<p><?php echo $t('learning_help'); ?></p>
<?php if(!$learningLinks): ?><p><?php echo $t('empty'); ?></p><?php else: ?>
<div class="chisimba-table-wrap"><table class="chisimba-table chisimba-management-table"><thead><tr><th scope="col"><?php echo $t('link_label'); ?></th><th scope="col"><?php echo $t('link_order'); ?></th><th scope="col"><?php echo $t('actions'); ?></th></tr></thead><tbody>
<?php foreach($learningLinks as $item): ?><tr><td><strong><?php echo $e($item['label']); ?></strong><div class="chisimba-muted"><?php echo $e($item['url']); ?></div></td><td><?php echo (int)$item['order']; ?></td><td><?php $actions($item,'editexternal','deleteexternal','toolbar',$item['label']); ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<details class="chisimba-form-card chisimba-form-card--wide chisimba-flow"><summary><?php echo $t('advanced_module_settings'); ?></summary>
<section class="chisimba-flow" aria-labelledby="module-links-title">
<h2 id="module-links-title"><?php echo $t('module_links'); ?></h2><p><?php echo $t('module_help'); ?></p>
<form method="get" class="chisimba-form-actions" action="<?php echo $url(['action'=>'editlinks']); ?>"><input type="hidden" name="module" value="toolbar"><input type="hidden" name="action" value="editlinks"><label for="toolbar-module"><?php echo $t('module'); ?></label><select id="toolbar-module" name="modulename"><?php foreach($moduleList as $item): ?><option value="<?php echo $e($item['module_id']); ?>"<?php echo $item['module_id']===$moduleName?' selected':''; ?>><?php echo $e($item['module_id']); ?></option><?php endforeach; ?></select><button type="submit"><?php echo $t('show_module'); ?></button></form>
</section>
<?php
$definitions=['site'=>['site','addsite','editsite'],'toolbar'=>['toolbar_section','addtool','edittool'],'menu'=>['side_section','addmenu','editmenu'],'page'=>['page_section','addpage','editpage']];
foreach($definitions as $kind=>$definition): ?>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow" aria-labelledby="nav-<?php echo $kind; ?>">
<header class="chisimba-cluster chisimba-section-heading"><h2 id="nav-<?php echo $kind; ?>"><?php echo $t($definition[0]); ?></h2><a class="button chisimba-button-secondary" href="<?php echo $url(['action'=>$definition[1],'modulename'=>$moduleName]); ?>"><?php echo $icons->render('plus',['decorative'=>true]); ?> <?php echo $t('add_module_link'); ?></a></header>
<?php if(!$groups[$kind]): ?><p><?php echo $t('empty'); ?></p><?php else: ?>
<div class="chisimba-table-wrap"><table class="chisimba-table chisimba-management-table"><thead><tr><th scope="col"><?php echo $t('link_label'); ?></th><th scope="col"><?php echo $t('permissions'); ?></th><th scope="col"><?php echo $t('actions'); ?></th></tr></thead><tbody>
<?php foreach($groups[$kind] as $row): $p=explode('|',$row['category']);$code=$kind==='page'?($p[3]??''):($p[4]??'');$label=$kind==='site'?$this->getObject('modulelinkdefaults','toolbar')->label($code,$moduleName):($code!==''?$lang->code2Txt($code,$moduleName):ucfirst($p[0])); ?>
<tr><td><strong><?php echo $e($label); ?></strong><div class="chisimba-muted"><?php echo $e($moduleName); ?></div><details><summary><?php echo $t('link_details'); ?></summary><code><?php echo $e($row['category']); ?></code></details></td><td><?php echo empty($row['permissions'])?$t('audience_everyone'):$t('right').' '.$e($row['permissions']); ?><?php if(!empty($row['adminonly'])): ?><div><?php echo $t('admin_only'); ?></div><?php endif; ?><?php if(!empty($row['dependscontext'])): ?><div><?php echo $t('context_only'); ?></div><?php endif; ?></td><td><?php $actions($row,$definition[2],'delete',$moduleName,$label); ?></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php endforeach; ?>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow" aria-labelledby="restore-title"><h2 id="restore-title"><?php echo $t('restore_title'); ?></h2><p><?php echo $t('restore_help').' <strong>'.$e($moduleName).'</strong>'; ?></p>
<form method="post" action="<?php echo $url(['action'=>'restore']); ?>" data-navigation-confirm="<?php echo $t('restore_confirm'); ?>"><input type="hidden" name="toolbar_csrf" value="<?php echo $e($toolbarCsrf); ?>"><input type="hidden" name="modulename" value="<?php echo $e($moduleName); ?>"><button class="chisimba-button-secondary" type="submit"><?php echo $icons->render('rotate-ccw',['decorative'=>true]); ?> <?php echo $t('restore_title'); ?></button></form></section>
</details>
</div>
<script defer src="<?php echo $e($this->getResourceUri('navigation-editor.js','toolbar')); ?>"></script>
