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
$groups = array('toolbar' => array(), 'menu' => array(), 'page' => array());
foreach ($data as $row) {
    $kind = strpos($row['category'], 'menu_') === 0
        ? 'menu'
        : (strpos($row['category'], 'page_') === 0 ? 'page' : 'toolbar');
    $groups[$kind][] = $row;
}
?>
<h1><?php echo $escape($lang->languageText('mod_toolbar_confmodulelinks', 'toolbar', 'Configure Module Links')); ?></h1>
<p><strong><?php echo $escape($lang->languageText('mod_toolbar_module', 'toolbar', 'Module')); ?>:</strong>
<?php echo $escape($moduleName); ?></p>
<form method="get" action="<?php echo $escape($this->uri(array('action' => 'editlinks'))); ?>">
  <input type="hidden" name="module" value="toolbar" />
  <input type="hidden" name="action" value="editlinks" />
  <label for="toolbar-module"><?php echo $escape($lang->languageText('mod_toolbar_selectmodule', 'toolbar', 'Select Module')); ?></label>
  <select id="toolbar-module" name="modulename" onchange="this.form.submit()">
<?php foreach ($moduleList as $item): ?>
    <option value="<?php echo $escape($item['module_id']); ?>"<?php echo $item['module_id'] === $moduleName ? ' selected="selected"' : ''; ?>><?php echo $escape($lang->code2Txt('mod_' . $item['module_id'] . '_name', $item['module_id'])); ?></option>
<?php endforeach; ?>
  </select>
  <button type="submit"><?php echo $escape($lang->languageText('word_go', 'security', 'Go')); ?></button>
</form>
<?php
$definitions = array(
    'toolbar' => array('mod_toolbar_toolbar', 'Toolbar', 'addtool', 'edittool'),
    'menu' => array('mod_toolbar_sidemenu', 'Side Menu', 'addmenu', 'editmenu'),
    'page' => array('mod_toolbar_page', 'Page', 'addpage', 'editpage'),
);
foreach ($definitions as $kind => $definition):
?>
<section>
  <h2><?php echo $escape($lang->languageText($definition[0], 'toolbar', $definition[1])); ?></h2>
  <p><a href="<?php echo $escape($this->uri(array('action' => $definition[2], 'modulename' => $moduleName))); ?>"><?php echo $escape($lang->languageText('mod_toolbar_addnewlink', 'toolbar', 'Add New Link')); ?></a></p>
<?php if ($groups[$kind] === array()): ?>
  <p><?php echo $escape($lang->languageText('mod_toolbar_nolinks', 'toolbar', 'No links')); ?></p>
<?php else: ?>
  <table>
    <thead><tr><th><?php echo $escape($lang->languageText('mod_toolbar_category', 'toolbar', 'Category')); ?></th><th><?php echo $escape($lang->languageText('mod_toolbar_permissions', 'toolbar', 'Permissions')); ?></th><th><?php echo $escape($lang->languageText('word_actions', 'security', 'Actions')); ?></th></tr></thead>
    <tbody>
<?php foreach ($groups[$kind] as $row): ?>
      <tr>
        <td><?php echo $escape($row['category']); ?></td>
        <td><?php echo $row['permissions'] === '' ? $escape($lang->languageText('mod_toolbar_settosite', 'toolbar', 'Display to everyone')) : $escape($row['permissions']); ?></td>
        <td>
          <a href="<?php echo $escape($this->uri(array('action' => $definition[3], 'id' => $row['id'], 'modulename' => $moduleName))); ?>"><?php echo $escape($lang->languageText('word_edit', 'security', 'Edit')); ?></a>
          <form method="post" action="<?php echo $escape($this->uri(array('action' => 'delete'))); ?>" style="display:inline">
            <input type="hidden" name="toolbar_csrf" value="<?php echo $escape($toolbarCsrf); ?>" />
            <input type="hidden" name="id" value="<?php echo $escape($row['id']); ?>" />
            <input type="hidden" name="modulename" value="<?php echo $escape($moduleName); ?>" />
            <button type="submit"><?php echo $escape($lang->languageText('word_delete', 'security', 'Delete')); ?></button>
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
