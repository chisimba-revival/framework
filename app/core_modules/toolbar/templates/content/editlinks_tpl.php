<?php
/**
 * Canonical toolbar-link administration list.
 *
 * @package toolbar
 * @author Derek Keats
 */
$this->setLayoutTemplate('admin_layout_tpl.php');
$lang = $this->getObject('language', 'language');
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$groups = array('toolbar' => array(), 'menu' => array(), 'page' => array(), 'site' => array());
foreach ($data as $row) {
    $kind = strpos($row['category'], 'menu_') === 0
        ? 'menu'
        : (strpos($row['category'], 'page_') === 0 ? 'page' : 'toolbar');
    if (strpos($row['category'], 'site_') === 0) $kind = 'site';
    $groups[$kind][] = $row;
}
?>
<h1><?php echo $escape($lang->languageText('mod_toolbar_confmodulelinks', 'toolbar', 'Configure Module Links')); ?></h1>
<?php echo $this->getObject('contextualhelp', 'help')->show('toolbar', 'navigation'); ?>
<section class="chisimba-form-card">
<form method="post" class="chisimba-form-actions" action="<?php echo $escape($this->uri(array('action'=>'saveprofile'))); ?>">
<input type="hidden" name="toolbar_csrf" value="<?php echo $escape($toolbarCsrf); ?>">
<label for="toolbar-profile"><?php echo $escape($lang->code2Txt('mod_toolbar_nav_profile','toolbar')); ?></label>
<select id="toolbar-profile" name="profile">
<?php foreach (array('dropdown','site','flat','elearning') as $profile): ?>
<option value="<?php echo $profile; ?>"<?php echo $toolbarProfile === $profile ? ' selected' : ''; ?>><?php echo $escape($lang->code2Txt('mod_toolbar_nav_profile_'.$profile,'toolbar')); ?></option>
<?php endforeach; ?></select>
<button type="submit"><?php echo $this->getObject('iconservice','ui')->render('save',array('decorative'=>true)); ?> <?php echo $escape($lang->code2Txt('word_save','system')); ?></button>
</form>
<p><?php echo $escape($lang->code2Txt('mod_toolbar_nav_hint','toolbar')); ?></p>
<?php if ($siteNavigationRows): ?><ul>
<?php foreach ($siteNavigationRows as $siteRow): $siteParts=explode('|',$siteRow['category']); ?>
<li><a href="<?php echo $escape($this->uri(array('action'=>'editsite','modulename'=>$siteRow['module'],'id'=>$siteRow['id']))); ?>"><?php echo $escape(substr($siteParts[0],5).' · '.$lang->code2Txt($siteParts[4] ?? '',$siteRow['module']).' · '.$siteRow['module']); ?></a></li>
<?php endforeach; ?></ul><?php endif; ?>
</section>
<p><strong><?php echo $escape($lang->languageText('mod_toolbar_module', 'toolbar', 'Module')); ?>:</strong>
<?php echo $escape($moduleName); ?></p>
<form method="get" action="<?php echo $escape($this->uri(array('action' => 'editlinks'))); ?>">
  <input type="hidden" name="module" value="toolbar" />
  <input type="hidden" name="action" value="editlinks" />
  <label for="toolbar-module"><?php echo $escape($lang->languageText('mod_toolbar_selectmodule', 'toolbar', 'Select Module')); ?></label>
  <select id="toolbar-module" name="modulename" onchange="this.form.submit()">
<?php foreach ($moduleList as $item): ?>
    <option value="<?php echo $escape($item['module_id']); ?>"<?php echo $item['module_id'] === $moduleName ? ' selected="selected"' : ''; ?>><?php echo $escape($item['module_id']); ?></option>
<?php endforeach; ?>
  </select>
  <button type="submit"><?php echo $escape($lang->languageText('word_go', 'security', 'Go')); ?></button>
</form>
<?php
$definitions = array(
    'site' => array('mod_toolbar_nav_site', 'Site navigation', 'addsite', 'editsite'),
    'toolbar' => array('mod_toolbar_toolbar', 'Toolbar', 'addtool', 'edittool'),
    'menu' => array('mod_toolbar_sidemenu', 'Side Menu', 'addmenu', 'editmenu'),
    'page' => array('mod_toolbar_page', 'Page', 'addpage', 'editpage'),
);
foreach ($definitions as $kind => $definition):
?>
<section>
  <h2><?php echo $escape($lang->languageText($definition[0], 'toolbar', $definition[1])); ?></h2>
  <p><a class="button" href="<?php echo $escape($this->uri(array('action' => $definition[2], 'modulename' => $moduleName))); ?>"><?php echo $this->getObject('iconservice','ui')->render('plus',array('decorative'=>true)); ?> <?php echo $escape($lang->languageText('mod_toolbar_addnewlink', 'toolbar', 'Add New Link')); ?></a></p>
<?php if ($groups[$kind] === array()): ?>
  <p><?php echo $escape($lang->languageText('mod_toolbar_nolinks', 'toolbar', 'No links')); ?></p>
<?php else: ?>
  <table>
    <thead><tr><th><?php echo $escape($lang->languageText('mod_toolbar_category', 'toolbar', 'Category')); ?></th><th><?php echo $escape($lang->languageText('mod_toolbar_permissions', 'toolbar', 'Permissions')); ?></th><th><?php echo $escape($lang->languageText('word_actions', 'security', 'Actions')); ?></th></tr></thead>
    <tbody>
<?php foreach ($groups[$kind] as $row): ?>
      <tr>
        <td><?php $displayParts=explode('|',$row['category']); echo $escape($kind === 'site' ? (int) substr($displayParts[0],5).' · '.$lang->code2Txt($displayParts[4] ?? '',$moduleName) : $row['category']); ?></td>
        <td><?php echo $row['permissions'] === '' ? $escape($lang->languageText('mod_toolbar_settosite', 'toolbar', 'Display to everyone')) : $escape($row['permissions']); ?></td>
        <td>
          <a href="<?php echo $escape($this->uri(array('action' => $definition[3], 'id' => $row['id'], 'modulename' => $moduleName))); ?>"><?php echo $this->getObject('iconservice','ui')->render('pencil',array('decorative'=>true)); ?> <?php echo $escape($lang->languageText('word_edit', 'security', 'Edit')); ?></a>
          <form method="post" action="<?php echo $escape($this->uri(array('action' => 'delete'))); ?>" style="display:inline">
            <input type="hidden" name="toolbar_csrf" value="<?php echo $escape($toolbarCsrf); ?>" />
            <input type="hidden" name="id" value="<?php echo $escape($row['id']); ?>" />
            <input type="hidden" name="modulename" value="<?php echo $escape($moduleName); ?>" />
            <button type="submit"><?php echo $this->getObject('iconservice','ui')->render('trash-2',array('decorative'=>true)); ?> <?php echo $escape($lang->languageText('word_delete', 'security', 'Delete')); ?></button>
          </form>
        </td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</section>
<?php endforeach; ?>
<form method="post" action="<?php echo $escape($this->uri(array('action' => 'restore'))); ?>">
  <input type="hidden" name="toolbar_csrf" value="<?php echo $escape($toolbarCsrf); ?>" />
  <input type="hidden" name="modulename" value="<?php echo $escape($moduleName); ?>" />
  <button type="submit"><?php echo $escape($lang->languageText('mod_toolbar_restoredefaults', 'toolbar', 'Restore Defaults')); ?></button>
</form>
