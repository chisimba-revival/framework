<?php
/** Administrator quota form; rejected writes retain the submitted draft. */
$e = fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$t = fn($key) => $this->objLanguage->languageText('mod_filemanager_quota_' . $key, 'filemanager');
$isContext = str_starts_with($quota['path'], 'context/');
$defaultQuota = $isContext ? $this->objQuotas->getDefaultContextQuota() : $this->objQuotas->getDefaultUserQuota();
$name = $isContext ? $this->objContext->getTitle(substr($quota['path'], 8)) : $this->objUser->fullName(substr($quota['path'], 6));
$draft = $quotaDraft ?? ['usedefault' => $quota['usedefault'], 'quota' => $quota['usedefault'] === 'Y' ? $defaultQuota : $quota['quota']];
?>
<div class="chisimba-form-card chisimba-form-card--wide">
<h1><?= $e($this->objLanguage->languageText('mod_filemanager_editquota', 'filemanager')) ?>: <?= $e($name) ?></h1>
<?= $this->getObject('contextualhelp', 'help')->show('filemanager', 'quotas') ?>
<?php if (!empty($quotaError)): ?><p role="alert" class="warning"><?= $e($t($quotaError)) ?></p><?php endif; ?>
<form method="post" action="<?= $e($this->uri(['action' => 'updatequota'])) ?>" class="chisimba-form chisimba-flow">
<input type="hidden" name="id" value="<?= $e($quota['id']) ?>">
<input type="hidden" name="quota_revision" value="<?= $e($quotaRevision) ?>">
<input type="hidden" name="csrf_token" value="<?= $e($quotaToken) ?>">
<fieldset>
<legend><?= $e($t('allocation')) ?></legend>
<div class="chisimba-cluster"><label><input type="radio" name="quotatype" value="Y" <?= $draft['usedefault'] === 'Y' ? 'checked' : '' ?>> <?= $e($this->objLanguage->languageText('mod_filemanager_usedefaultquotaof', 'filemanager')) ?> <?= $e($defaultQuota) ?> MB</label>
<label><input type="radio" name="quotatype" value="N" <?= $draft['usedefault'] === 'N' ? 'checked' : '' ?>> <?= $e($this->objLanguage->languageText('mod_filemanager_usecustomquota', 'filemanager')) ?></label></div>
<div class="chisimba-form-field"><label for="quota-size"><?= $e($t('size')) ?></label>
<input id="quota-size" name="customquota" type="text" inputmode="numeric" value="<?= $e($draft['quota']) ?>" aria-describedby="quota-size-help">
<p id="quota-size-help"><?= $e($t('size_help')) ?></p></div>
</fieldset>
<div class="chisimba-form-actions"><button type="submit"><?= $e($this->objLanguage->languageText('mod_filemanager_updatequota', 'filemanager')) ?></button>
<a class="button" href="<?= $e($this->uri(['action' => 'quotamanager'])) ?>"><?= $e($t('back')) ?></a></div>
</form></div>
