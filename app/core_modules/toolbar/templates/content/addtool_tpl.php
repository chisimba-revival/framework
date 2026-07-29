<?php
/**
 * Canonical toolbar-link editor.
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
$depends = $editing && !empty($data['dependscontext']);
$adminOnly = $editing && !empty($data['adminonly']);
$category = $editing && isset($data['category']) ? $data['category'] : '';
$heading = $lang->languageText(
    $editing ? 'mod_toolbar_editlink' : 'mod_toolbar_addnewlink',
    'toolbar',
    $editing ? 'Edit Link' : 'Add New Link'
);
$labels = array(
    'organise' => $lang->languageText('category_organise', 'toolbar', 'Organise'),
    'communicate' => $lang->languageText('category_communicate', 'toolbar', 'Communicate'),
    'learn' => $lang->languageText('category_learn', 'toolbar', 'Learn'),
    'admin' => $lang->languageText('category_admin', 'toolbar', 'Admin'),
    'about' => $lang->languageText('category_about', 'toolbar', 'About'),
    'postgrad' => $lang->languageText('category_postgrad', 'toolbar', 'Postgraduate'),
    'user' => $lang->languageText('category_user', 'security', 'User'),
    'course' => $lang->languageText('category_course', 'security', 'Course'),
    'assessment' => $lang->languageText('category_assessment', 'security', 'Assessment'),
    'site' => $lang->languageText('category_site', 'security', 'Site'),
    'sems' => 'SEMS',
);
?>
<h1><?php echo $escape($heading); ?></h1>
<p><strong><?php echo $escape($lang->languageText('mod_toolbar_module', 'toolbar', 'Module')); ?>:</strong>
<?php echo $escape($moduleName); ?></p>
<form method="post" action="<?php echo $escape($this->uri(array('action' => 'savetool'))); ?>">
  <input type="hidden" name="toolbar_csrf" value="<?php echo $escape($toolbarCsrf); ?>" />
  <input type="hidden" name="moduleName" value="<?php echo $escape($moduleName); ?>" />
<?php if ($editing): ?>
  <input type="hidden" name="id" value="<?php echo $escape($data['id']); ?>" />
<?php endif; ?>
  <p><label for="toolbar-category"><?php echo $escape($lang->languageText('mod_toolbar_selectcategory', 'toolbar', 'Select Category')); ?></label>
  <select id="toolbar-category" name="category">
<?php foreach ($labels as $value => $label): ?>
    <option value="<?php echo $escape($value); ?>"<?php echo $category === $value ? ' selected="selected"' : ''; ?>><?php echo $escape($label); ?></option>
<?php endforeach; ?>
  </select></p>
  <p><label><input type="checkbox" name="adminOnly" value="1"<?php echo $adminOnly ? ' checked="checked"' : ''; ?> />
  <?php echo $escape($lang->languageText('mod_toolbar_adminonly', 'toolbar', 'Admin Only')); ?></label></p>
  <p><label><input type="checkbox" name="dependsContext" value="1"<?php echo $depends ? ' checked="checked"' : ''; ?> />
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
