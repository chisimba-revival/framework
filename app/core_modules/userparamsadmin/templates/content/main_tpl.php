<?php
/** Semantic preference list. Values and mutations stay out of navigation URLs. */
$escape=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
$text=fn($key,$fallback)=>$this->objLanguage->languageText($key,'userparamsadmin',$fallback);
$icons=$this->getObject('iconservice','ui');
?>
<h1><?php echo $escape($text('mod_userparamsadmin_title','Manage user parameters list')); ?></h1>
<?php if ($this->getObject('modules','modulecatalogue')->checkIfRegistered('help')) { echo $this->getObject('contextualhelp','help')->show('userparamsadmin','preferences'); } ?>
<?php if ($preferencesError): ?><p role="alert"><?php echo $escape($preferencesError); ?></p><?php endif; ?>
<p><a class="button" href="<?php echo $escape($this->uri(array('action'=>'add'))); ?>"><?php echo $escape($text('mod_userparamsadmin_addnew','Add New Parameter')); ?></a></p>
<table>
<thead><tr><th><?php echo $escape($text('mod_userparamsadmin_pname','Parameter name')); ?></th><th><?php echo $escape($text('mod_userparamsadmin_pvalue','Parameter value')); ?></th><th><?php echo $escape($text('mod_userparamsadmin_action','Action')); ?></th></tr></thead>
<tbody>
<?php foreach (($ar['root']['Settings'] ?? array()) as $key=>$value): ?>
<tr><th scope="row"><?php echo $escape($key); ?></th><td><?php echo $escape(is_array($value)?implode(', ',$value):$value); ?></td><td>
<div class="chisimba-form-actions chisimba-form-actions--equal">
<a class="button" href="<?php echo $escape($this->uri(array('action'=>'edit','key'=>$key))); ?>"><?php echo $icons->render('pencil',array('decorative'=>true)); ?> <?php echo $escape($text('word_edit','Edit')); ?></a>
<form method="post" onsubmit="return window.confirm(<?php echo $escape(json_encode($text('mod_userparams_confirmdelete','Confirm deletion of this parameter'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)); ?>);" action="<?php echo $escape($this->uri(array('action'=>'delete'))); ?>">
<input type="hidden" name="key" value="<?php echo $escape($key); ?>">
<input type="hidden" name="preferences_csrf" value="<?php echo $escape($preferencesCsrf); ?>">
<input type="hidden" name="preferences_revision" value="<?php echo $escape($preferencesRevision); ?>">
<button type="submit"><?php echo $icons->render('trash-2',array('decorative'=>true)); ?> <?php echo $escape($text('word_delete','Delete')); ?></button>
</form></div></td></tr>
<?php endforeach; ?>
</tbody></table>
