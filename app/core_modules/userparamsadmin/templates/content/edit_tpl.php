<?php
/** Preference editor with retained draft and revision after failed submission. */
$escape=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
$text=fn($key,$fallback)=>$this->objLanguage->languageText($key,'userparamsadmin',$fallback);
?>
<h1><?php echo $escape($mode==='add'?$text('mod_userparamsadmin_titadd','Add user parameter'):$text('mod_userparamsadmin_titedit','Edit user parameter')); ?></h1>
<?php if ($this->getObject('modules','modulecatalogue')->checkIfRegistered('help')) { echo $this->getObject('contextualhelp','help')->show('userparamsadmin','preferences'); } ?>
<?php if ($preferencesError): ?><p role="alert"><?php echo $escape($preferencesError); ?></p><?php endif; ?>
<form method="post" class="chisimba-form-card chisimba-form" action="<?php echo $escape($this->uri(array('action'=>'save'))); ?>">
<input type="hidden" name="mode" value="<?php echo $escape($mode); ?>">
<input type="hidden" name="preferences_csrf" value="<?php echo $escape($preferencesCsrf); ?>">
<input type="hidden" name="preferences_revision" value="<?php echo $escape($preferencesRevision); ?>">
<div class="chisimba-form-field"><label for="preference-name"><?php echo $escape($text('mod_userparamsadmin_pname','Parameter name')); ?></label>
<input type="text" id="preference-name" name="pname" required value="<?php echo $escape($keyEdit); ?>" <?php echo $mode==='edit'?'readonly':''; ?>></div>
<div class="chisimba-form-field"><label for="preference-value"><?php echo $escape($text('mod_userparamsadmin_pvalue','Parameter value')); ?></label>
<input type="text" id="preference-value" name="ptag" value="<?php echo $escape($valueEdit); ?>"></div>
<div class="chisimba-form-actions chisimba-form-actions--equal"><button type="submit"><?php echo $escape($text('word_save','Save')); ?></button>
<a class="button chisimba-button-secondary" href="<?php echo $escape($this->uri(array('action'=>'view'))); ?>"><?php echo $escape($text('mod_userparamsadmin_cancel','Cancel')); ?></a></div>
</form>
