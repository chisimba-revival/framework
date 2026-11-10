<?php

$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('form', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');
$this->loadClass('button', 'htmlelements');

$courseTitle = htmlspecialchars($context['title'], ENT_QUOTES, 'UTF-8');
$deleteTitle = ucwords($this->objLanguage->code2Txt(
    'mod_contextadmin_deletecontext',
    'contextadmin',
    NULL,
    'Delete [-context-]'
));
$question = $this->objLanguage->languageText(
    'mod_contextadmin_confirmdeletecontext',
    'contextadmin',
    'Are you sure you want to delete this context?'
);

$header = new htmlheading();
$header->type = 2;
$header->str = $deleteTitle . ': ' . $courseTitle;

echo '<section class="chisimba-delete-confirmation">';
echo $header->show();
echo '<p class="chisimba-delete-question">' . $question . '</p>';

$form = new form('deletecontext', $this->uri(array('action' => 'deleteconfirm')));

$confirmButton = new button(
    'confirm',
    $this->objLanguage->languageText(
        'mod_contextadmin_confirmdeletion',
        'contextadmin',
        'Confirm deletion'
    )
);
$confirmButton->setToSubmit();

$cancelButton = new button(
    'cancel',
    $this->objLanguage->languageText(
        'mod_contextadmin_canceldeletion',
        'contextadmin',
        'Cancel deletion'
    )
);
$cancelButton->setOnClick('history.go(-1);');

$form->addToForm(
    '<div class="chisimba-delete-actions">'
    . $confirmButton->show()
    . ' '
    . $cancelButton->show()
    . '</div>'
);

$deleteConfirmation = new hiddeninput('deleteconfirm', 'yes');
$form->addToForm($deleteConfirmation->show());

$contextCode = new hiddeninput('contextcode', $context['contextcode']);
$form->addToForm($contextCode->show());

echo $form->show();
echo '</section>';

?>
