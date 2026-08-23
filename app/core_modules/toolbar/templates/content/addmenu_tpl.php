<?php
/**
 * Canonical side-menu and page-link editor.
 *
 * @package toolbar
 * @author Derek Keats
 */
$this->setLayoutTemplate('admin_layout_tpl.php');
$lang = $this->getObject('language', 'language');
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$editing = $mode === 'edit';
$parts = $editing ? explode('|', $data['category']) : array();
$head = isset($parts[0]) ? $parts[0] : '';
$menu = '';
$position = $page ? 'users' : '2';
if ($page && strpos($head, 'page_') === 0) {
    $headParts = explode('_', $head, 3);
    $menu = isset($headParts[1]) ? $headParts[1] : '';
    $position = isset($headParts[2]) ? $headParts[2] : 'users';
} elseif (!$page && strpos($head, 'menu_') === 0) {
    $headParts = explode('-', substr($head, 5), 2);
    $menu = isset($headParts[0]) ? $headParts[0] : '';
    $position = isset($headParts[1]) ? $headParts[1] : '2';
}
$action = $editing ? (isset($parts[$page ? 1 : 2]) ? $parts[$page ? 1 : 2] : '') : '';
$icon = $editing ? (isset($parts[$page ? 2 : 3]) ? $parts[$page ? 2 : 3] : '') : '';
$code = $editing ? (isset($parts[$page ? 3 : 4]) ? $parts[$page ? 3 : 4] : '') : '';
$menuOptions = $page
    ? array('lecturer', 'admin', 'manage')
    : array('user', 'alumni', 'context', 'postlogin', 'postgrad');
$positionOptions = $page
    ? array('users', 'content', 'organise', 'site', 'shared', 'develop', 'assign')
    : array('1', '2', '3', '4', '5');
$heading = $lang->languageText(
    $editing ? 'mod_toolbar_editlink' : 'mod_toolbar_addnewlink',
    'toolbar',
    $editing ? 'Edit Link' : 'Add New Link'
);
$actionName = $page ? 'savepage' : 'savemenu';
?>
<h1><?php echo $escape($heading); ?></h1>
<p><strong><?php echo $escape($lang->languageText('mod_toolbar_module', 'toolbar', 'Module')); ?>:</strong>
<?php echo $escape($moduleName); ?></p>
<form method="post" action="<?php echo $escape($this->uri(array('action' => $actionName))); ?>">
  <input type="hidden" name="toolbar_csrf" value="<?php echo $escape($toolbarCsrf); ?>" />
  <input type="hidden" name="moduleName" value="<?php echo $escape($moduleName); ?>" />
<?php if ($editing): ?>
  <input type="hidden" name="id" value="<?php echo $escape($data['id']); ?>" />
<?php endif; ?>
  <p><label for="toolbar-menu"><?php echo $escape($lang->languageText($page ? 'mod_toolbar_page' : 'mod_toolbar_sidemenu', 'toolbar', $page ? 'Page' : 'Side Menu')); ?></label>
  <select id="toolbar-menu" name="menu">
<?php foreach ($menuOptions as $value): ?>
    <option value="<?php echo $escape($value); ?>"<?php echo $menu === $value ? ' selected="selected"' : ''; ?>><?php echo $escape($value); ?></option>
<?php endforeach; ?>
  </select></p>
  <p><label for="toolbar-position"><?php echo $escape($lang->languageText($page ? 'mod_toolbar_selectcategory' : 'mod_toolbar_positioninmenu', 'toolbar', $page ? 'Select Category' : 'Position in Menu')); ?></label>
  <select id="toolbar-position" name="position">
<?php foreach ($positionOptions as $value): ?>
    <option value="<?php echo $escape($value); ?>"<?php echo (string) $position === (string) $value ? ' selected="selected"' : ''; ?>><?php echo $escape($value); ?></option>
<?php endforeach; ?>
  </select></p>
  <p><label for="toolbar-action"><?php echo $escape($lang->languageText('mod_toolbar_action', 'toolbar', 'Action')); ?></label>
  <input id="toolbar-action" name="actionName" value="<?php echo $escape($action); ?>" /></p>
  <p><label for="toolbar-icon"><?php echo $escape($lang->languageText('mod_toolbar_icon', 'toolbar', 'Icon')); ?></label>
  <input id="toolbar-icon" name="icon" value="<?php echo $escape($icon); ?>" /></p>
  <p><label for="toolbar-code"><?php echo $escape($lang->languageText('mod_toolbar_langcode', 'toolbar', 'Language Code')); ?></label>
  <input id="toolbar-code" name="code" value="<?php echo $escape($code); ?>" required="required" /></p>
  <p><label><input type="checkbox" name="adminOnly" value="1"<?php echo $editing && !empty($data['adminonly']) ? ' checked="checked"' : ''; ?> />
  <?php echo $escape($lang->languageText('mod_toolbar_adminonly', 'toolbar', 'Admin Only')); ?></label></p>
  <p><label><input type="checkbox" name="dependsContext" value="1"<?php echo $editing && !empty($data['dependscontext']) ? ' checked="checked"' : ''; ?> />
  <?php echo $escape($lang->languageText('mod_toolbar_dependscontext', 'toolbar', 'Depends Context')); ?></label></p>
  <p><label for="toolbar-right"><?php echo $escape($lang->languageText('mod_toolbar_permissions', 'toolbar', 'Permissions')); ?></label>
  <select id="toolbar-right" name="permissions">
    <option value=""><?php echo $escape($lang->languageText('mod_toolbar_settosite', 'toolbar', 'Display to everyone')); ?></option>
<?php foreach ($rightOptions as $right): ?>
    <option value="<?php echo $escape($right['rightId']); ?>"<?php echo (string) $selectedRight === (string) $right['rightId'] ? ' selected="selected"' : ''; ?>><?php echo $escape($right['name']); ?></option>
<?php endforeach; ?>
  </select></p>
  <p><button type="submit" name="save" value="<?php echo $escape($lang->languageText('word_save', 'security', 'Save')); ?>"><?php echo $escape($lang->languageText('word_save', 'security', 'Save')); ?></button>
  <button type="submit" name="save" value="<?php echo $escape($lang->languageText('word_back', 'security', 'Back')); ?>"><?php echo $escape($lang->languageText('word_back', 'security', 'Back')); ?></button></p>
</form>
