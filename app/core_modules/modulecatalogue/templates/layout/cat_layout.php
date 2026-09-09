<?php
//print_r($this->tagCloud); die();
//create an instance of the css layout class
$cssLayout = $this->newObject('csslayout', 'htmlelements');

//set columns to 2
$cssLayout->setNumColumns(2);

//add left column
$ret = $this->objSideMenu->show($activeCat).$this->objTagCloud;
$ret = "<div class='modcat_left'>$ret</div>";
$cssLayout->setLeftColumnContent($ret);
unset($ret);
//set middle content
$content = $this->getContent();
// Visible on Updates as well as category/search pages, so new modules are discoverable.
$moduleFilter = $moduleFilter ?? 'all';
$localIds = $this->getObject('catalogueviewfilter', 'modulecatalogue')
    ->localIds($this->objModFile->getLocalModuleList());
$installedIds = array_column($this->objModule->getAll(), 'module_id');
$uninstalledCount = count(array_diff($localIds, $installedIds));
$viewFilter = $this->getObject('catalogueviewfilter', 'modulecatalogue');
$newCount = count(array_filter($localIds, fn($id) => $viewFilter->isRecent($viewFilter->createdDate($id))));
$escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$filterBar = '<nav class="chisimba-actions" aria-label="'
    . $escape($this->objLanguage->languageText('mod_modulecatalogue_filterlabel', 'modulecatalogue')) . '">';
foreach (array('all' => 'allmodules', 'installed' => 'installedmodules', 'uninstalled' => 'uninstalledmodules', 'new' => 'newmodules') as $value => $key) {
    $label = $this->objLanguage->languageText('mod_modulecatalogue_' . $key, 'modulecatalogue');
    if ($value === 'uninstalled') { $label .= ' (' . $uninstalledCount . ')'; }
    if ($value === 'new') { $label .= ' (' . $newCount . ')'; }
    $filterBar .= '<a class="button chisimba-button-secondary chisimba-selectable"'
        . ($moduleFilter === $value ? ' aria-current="page"' : '')
        . ' href="' . $escape(html_entity_decode($this->uri(array('action'=>'list', 'cat'=>'all', 'modulefilter'=>$value), 'modulecatalogue'), ENT_QUOTES, 'UTF-8')) . '">'
        . $escape($label) . '</a>';
}
$filterBar .= '<a class="button chisimba-button-secondary" href="'
    . $escape(html_entity_decode($this->uri(array('action'=>'updatexml','cat'=>$activeCat,'modulefilter'=>$moduleFilter), 'modulecatalogue'), ENT_QUOTES, 'UTF-8')) . '">'
    . $escape($this->objLanguage->languageText('mod_modulecatalogue_refreshcatalogue', 'modulecatalogue')) . '</a></nav>';
if ($moduleFilter === 'new') {
    $filterBar .= '<p class="chisimba-notice">' . $escape($this->objLanguage->languageText('mod_modulecatalogue_newmoduleshelp', 'modulecatalogue')) . '</p>';
}
$ret = $filterBar . $content;
$ret = "<div class='modcat_main'>$ret</div>";
$cssLayout->setMiddleColumnContent($ret);

// Render module catalogue.
echo $cssLayout->show();
?>