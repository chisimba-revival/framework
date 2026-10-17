<?php

$this->loadClass('form', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');

$objIcon = $this->newObject('geticon', 'htmlelements');

$this->appendArrayVar('headerParams', $this->getJavascriptFile('selectall.js', 'htmlelements'));

echo '<h1>'.$this->objLanguage->languageText('mod_filemanager_fileswithtag', 'filemanager', 'Files with tag').': '.htmlspecialchars((string) $tag, ENT_QUOTES, 'UTF-8').'</h1>';

echo $this->getObject('contextualhelp', 'help')->show('filemanager', 'taggedfiles');

if ((is_countable($files) ? count($files) : 0) > 0) {
    $form = new form('deletefiles', $this->uri(array('action'=>'multidelete')));
    $form->addToForm($table);

    $button = new button ('submitform', $this->objLanguage->languageText('mod_filemanager_deleteselecteditems', 'filemanager', 'Delete Selected Items'));
    $button->setToSubmit();

    $selectallbutton = new button ('selectall', $this->objLanguage->languageText('phrase_selectall', 'system', 'Select All'));
    $selectallbutton->setOnClick("javascript:SetAllCheckBoxes('deletefiles', 'files[]', true);");

    $deselectallbutton = new button ('deselectall', $this->objLanguage->languageText('phrase_deselectall', 'system', 'Deselect all'));
    $deselectallbutton->setOnClick("javascript:SetAllCheckBoxes('deletefiles', 'files[]', false);");

    $form->addToForm('<div class="chisimba-form-actions">'.$button->show().' '.$selectallbutton->show().' '.$deselectallbutton->show().'</div>');
    
    echo $form->show();
} else {
    echo $table;
}


?>