<?php

$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('form', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');


$objIcon = $this->newObject('geticon', 'htmlelements');
$objIcon->setIcon('loader');

$formAction = 'savestep2';
$headerTitle = $context['title'].' - '.$this->objLanguage->code2Txt('mod_contextadmin_contextinformation', 'contextadmin', NULL, '[-context-] Information');
$formButton = $this->objLanguage->languageText('mod_contextadmin_gotonextstep', 'contextadmin', 'Go to Next Step');


$objStepMenu = $this->newObject('stepmenu', 'navigation');
if ($mode == 'edit') {
    $objStepMenu->addStep(str_replace('[-num-]', 1, $this->objLanguage->code2Txt('mod_contextadmin_stepnumber', 'contextadmin', NULL, 'Step [-num-]')).' - '.ucwords($this->objLanguage->code2Txt('mod_context_contextsettings', 'context', NULL, '[-context-] Settings')), ucwords($this->objLanguage->code2Txt('mod_contextadmin_updatecontextitlesettings', 'contextadmin', NULL, 'Update [-context-] Title and Settings')));
} else {
    $objStepMenu->addStep(str_replace('[-num-]', 1, $this->objLanguage->code2Txt('mod_contextadmin_stepnumber', 'contextadmin', NULL, 'Step [-num-]')).' - '.ucwords($this->objLanguage->code2Txt('mod_context_contextsettings', 'context', NULL, '[-context-] Settings')), $this->objLanguage->code2Txt('mod_contextadmin_checkcontextcodeavailable', 'contextadmin', NULL, 'Enter [-context-] settings and check whether [-context-] code is available'));
}
$objStepMenu->addStep(str_replace('[-num-]', 2, $this->objLanguage->code2Txt('mod_contextadmin_stepnumber', 'contextadmin', NULL, 'Step [-num-]')).' - '.ucwords($this->objLanguage->code2Txt('mod_contextadmin_contextinformation', 'contextadmin', NULL, '[-context-] Information')), $this->objLanguage->code2Txt('mod_contextadmin_enterinfoaboutcontext', 'contextadmin', NULL, 'Enter more information about your [-context-] and select a [-context-] image'));
$objStepMenu->addStep(str_replace('[-num-]', 3, $this->objLanguage->code2Txt('mod_contextadmin_stepnumber', 'contextadmin', NULL, 'Step [-num-]')).' - '.ucwords($this->objLanguage->code2Txt('mod_contextadmin_courseoutcome', 'contextadmin', NULL, '[-context-] Outcomes')), $this->objLanguage->code2Txt('mod_context_enteroutcomecontext', 'contextadmin', NULL, 'Enter the main Outcomes / Goals of the [-context-]'));

$objStepMenu->addStep(str_replace('[-num-]', 4, $this->objLanguage->code2Txt('mod_contextadmin_stepnumber', 'contextadmin', NULL, 'Step [-num-]')).' - '.ucwords($this->objLanguage->code2Txt('mod_context_contextpluginsabs', 'context', array('plugins'=>'plugins'), '[-context-] [-plugins-]')), $this->objLanguage->code2Txt('mod_contextadmin_selectpluginsforcontextabs', 'contextadmin', array('plugins'=>'plugins'), 'Select the [-plugins-] you would like to use in this [-context-]'));
$objStepMenu->setCurrent(2);
echo $objStepMenu->show();


$header = new htmlheading();
$header->type = 1;
$header->str = htmlspecialchars(ucwords($headerTitle), ENT_QUOTES, 'UTF-8');

echo '<div class="chisimba-wizard-heading">'.$header->show().'</div>';

$e = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
if (!empty($informationError)) {
    $notice = $informationError === 'expired' ? 'mod_contextadmin_informationexpired'
        : ($informationError === 'image' ? 'mod_contextadmin_contextimagenotsaved' : 'mod_contextadmin_informationfailed');
    echo '<p class="error" role="alert">'.$e($this->objLanguage->languageText($notice, 'contextadmin')).'</p>';
}
echo '<p>'.$e($this->objLanguage->languageText('mod_contextadmin_informationhelp', 'contextadmin')).'</p>';

$objSelectImage = $this->getObject('selectimage', 'filemanager');
$selectedImage = $informationDraft['imageselect'] ?? '';
if ($selectedImage !== '' && $this->getObject('filereadpolicy', 'filemanager')->mayRead(
    $this->getObject('dbfile', 'filemanager')->getFile($selectedImage))) {
    $objSelectImage->setDefaultFile($selectedImage);
}
if ($this->getObject('modules', 'modulecatalogue')->checkIfRegistered('help')) {
    echo $this->getObject('contextualhelp', 'help')->show('contextadmin', 'information', true);
}


$htmlEditor = $this->newObject('htmlarea', 'htmlelements');
$htmlEditor->name = 'about';
$htmlEditor->value = $context['about'];


$table = $this->newObject('htmltable', 'htmlelements');
$table->startRow();

$objContextImage = $this->getObject('contextimage', 'context');
$hasContextImage = $objContextImage->getContextImage($contextCode);

$leftCol = '';

if ($hasContextImage != FALSE) {
    $leftCol .= '<div id="contextexistingimage"><p>'.$this->objLanguage->code2Txt('mod_contextadmin_existingcontextimage', 'contextadmin', NULL, 'Existing [-context-] Image').':</p>';
    $leftCol .= '<img src="'.$e($hasContextImage).'" width="100" height="100" alt="">';
    $leftCol .= '<label><input type="checkbox" name="removeimage" value="1"'
        . (($informationDraft['removeimage'] ?? '') === '1' ? ' checked' : '') . '> '
        . $e($this->objLanguage->languageText('mod_contextadmin_removeimageonsave', 'contextadmin')).'</label></div>';
}

$leftCol .= '<p>'.$this->objLanguage->code2Txt('mod_contextadmin_selectcontextimage', 'contextadmin', NULL, 'Select a [-context-] image').':</p>';

$leftCol .= $objSelectImage->show();

$table->addCell($leftCol, 200);


$table->addCell('<p>'.$this->objLanguage->code2Txt('mod_contextadmin_enterdescriptioncontext', 'contextadmin', NULL, 'Enter a description of the [-context-]').':</p>'.$htmlEditor->show());
$table->endRow();




$button = new button ('savecontext', $formButton);
$button->cssClass = 'contextadmin-wizard-button contextadmin-wizard-button-primary';
$button->sexyButtons = FALSE;
$button->setToSubmit();



$form = new form ('createcontext', $this->uri(array('action'=>$formAction)));

$backUri = $this->uri(array('action'=>'edit','contextcode'=>$contextCode),'contextadmin');
$backButton = new button('back', $this->objLanguage->languageText('word_back'),"document.location='$backUri'");
$backButton->cssClass = 'contextadmin-wizard-button contextadmin-wizard-button-secondary';
$backButton->sexyButtons = FALSE;

$form->addToForm((new hiddeninput('csrf_token', $informationCsrf))->show());
$form->addToForm($table->show());
$form->addToForm($backButton->show()." ".$button->show());

$hiddenInput = new hiddeninput('mode', $mode);
$form->addToForm($hiddenInput->show());

$hiddenInput = new hiddeninput('contextCode', $contextCode);
$form->addToForm($hiddenInput->show());

echo $form->show();




?>
