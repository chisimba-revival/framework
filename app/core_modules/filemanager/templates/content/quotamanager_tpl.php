<?php
/** Site-wide quota search, restricted to current administrators by the controller. */
$e = fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$t = fn($key, $module = 'filemanager') => ucfirst($this->objLanguage->code2Txt($key, $module));
$param = fn($name, $default = '') => is_string($this->getParam($name)) ? $this->getParam($name) : $default;
$searchType = $param('searchType') === 'context' ? 'context' : 'users';
$searchField = $param('searchField_' . ($searchType === 'context' ? 'context' : 'user'), $searchType === 'context' ? 'title' : 'firstname');
$orderBy = $param('orderBy_' . ($searchType === 'context' ? 'context' : 'user'), 'quotausage_desc');
$this->appendArrayVar('headerParams', $this->getJavaScriptFile('quota-search.js', 'filemanager'));
$options = function($name, $items, $default) use ($e, $param) {
    $selected = $param($name, $default);
    foreach ($items as $value => $label) echo '<option value="' . $e($value) . '"' . ($selected === $value ? ' selected' : '') . '>' . $e($label) . '</option>';
};
$userFields = ['firstname' => $t('phrase_firstname', 'system'), 'surname' => $t('word_surname', 'system')];
$contextFields = ['title' => $t('mod_filemanager_quota_context_title', 'filemanager'), 'contextcode' => $t('mod_filemanager_quota_context_code', 'filemanager')];
$orders = ['quotausage_desc' => $t('mod_filemanager_mostusage'), 'quotausage' => $t('mod_filemanager_leastusage')];
?>
<div class="chisimba-form-actions"><h1><?= $e($t('mod_filemanager_quotamanager')) ?></h1>
<?= $this->getObject('contextualhelp', 'help')->show('filemanager', 'quotas') ?></div>
<?php if ($param('message') === 'quotatupdated'): ?><p role="status" class="success"><?= $e($t('mod_filemanager_quota_saved')) ?></p><?php endif; ?>
<form method="get" action="<?= $e($this->uri(['action' => 'quotamanager'])) ?>" class="chisimba-form-card chisimba-form-card--wide">
<input type="hidden" name="module" value="filemanager"><input type="hidden" name="action" value="quotamanager">
<div class="chisimba-form-grid">
<div class="chisimba-form-field"><label for="quota-scope"><?= $e($t('word_type', 'system')) ?></label>
<select id="quota-scope" name="searchType" data-quota-search-type><?php $options('searchType', ['users' => $t('mod_filemanager_users'), 'context' => $t('mod_filemanager_quota_contexts', 'filemanager')], 'users'); ?></select></div>
<?php foreach (['user' => $userFields, 'context' => $contextFields] as $scope => $items): ?>
<div class="<?= $scope ?>form chisimba-form-field" <?= ($scope === 'context') !== ($searchType === 'context') ? 'hidden' : '' ?>>
<label for="quota-field-<?= $scope ?>"><?= $e($t('mod_filemanager_searchfield')) ?></label>
<select id="quota-field-<?= $scope ?>" name="searchField_<?= $scope ?>"><?php $options('searchField_' . $scope, $items, array_key_first($items)); ?></select></div>
<div class="<?= $scope ?>form chisimba-form-field" <?= ($scope === 'context') !== ($searchType === 'context') ? 'hidden' : '' ?>>
<label for="quota-order-<?= $scope ?>"><?= $e($t('mod_filemanager_orderby')) ?></label>
<select id="quota-order-<?= $scope ?>" name="orderBy_<?= $scope ?>"><?php $options('orderBy_' . $scope, $orders + $items, 'quotausage_desc'); ?></select></div>
<?php endforeach; ?>
<div class="chisimba-form-field"><label for="quota-search"><?= $e($t('mod_forum_searchfor', 'system')) ?></label>
<input id="quota-search" name="searchfor" value="<?= $e($param('searchfor')) ?>"></div>
</div><div class="chisimba-form-actions"><button type="submit"><?= $e($t('word_go', 'system')) ?></button></div>
</form>
<?php
$objPagination = $this->newObject('pagination', 'navigation');
$objPagination->id = 'quotalist';

$numItemsPerPage = 20;

$objPagination->numPageLinks = $this->objQuotas->getNumPages($searchType, $searchField, $this->getParam('searchfor'), $orderBy, $numItemsPerPage);

$objPagination->module = 'filemanager';
$objPagination->action = 'ajaxgetquotas';
$objPagination->extra = array('searchType'=>$searchType, 'searchField'=>$searchField, 'searchFor'=>$this->getParam('searchfor'), 'orderBy'=>$orderBy, 'numItemsPerPage'=>$numItemsPerPage);

echo $objPagination->show();

?>