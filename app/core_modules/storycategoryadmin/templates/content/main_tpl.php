<?php
// Administration view for tbl_storycategory.

$cssLayout = $this->newObject('csslayout', 'htmlelements');
$cssLayout->setNumColumns(2);

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$normaliseUri = static function ($value) {
    return html_entity_decode(
        (string) $value,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
};

$objIconService = $this->getObject('iconservice', 'ui');
$iconLink = static function ($href, $iconName, $label) use ($escape, $normaliseUri, $objIconService) {
    return '<a class="story-admin-page__action" href="' . $escape($normaliseUri($href))
        . '" title="' . $escape($label) . '">'
        . $objIconService->render($iconName, array(
            'label' => $label,
            'class' => 'story-admin-page__action-icon',
        )) . '</a>';
};

$allowAdmin = true;
$title = $objLanguage->code2Txt(
    'mod_storycategoryadmin_title',
    'storycategoryadmin'
);
$addLabel = $objLanguage->code2Txt(
    'mod_storycategoryadmin_addnew',
    'storycategoryadmin'
);
$instructions = $objLanguage->code2Txt(
    'mod_storycategoryadmin_leftinstructions',
    'storycategoryadmin'
);
$leftSideColumn = '<p class="story-admin-page__instructions">'
    . $escape($instructions) . '</p>';

$header = '<div class="story-admin-page__header">'
    . '<h1 class="story-admin-page__title">' . $escape($title) . '</h1>';
if ($allowAdmin) {
    $header .= '<a class="story-admin-page__add" href="'
        . $escape($normaliseUri($this->uri(array('action' => 'add')))) . '">'
        . $objIconService->render('plus', array(
            'decorative' => true,
            'class' => 'story-admin-page__add-icon',
        ))
        . '<span>' . $escape($addLabel) . '</span></a>';
}
$header .= '</div>';

$this->Table = $this->newObject('htmltable', 'htmlelements');
$this->Table->cellspacing = '0';
$this->Table->width = '100%';
$this->Table->attributes = 'class="story-admin-page__table"';

$tableHd = array(
    $objLanguage->code2Txt(
        'mod_storycategoryadmin_category',
        'storycategoryadmin'
    ),
    $objLanguage->code2Txt(
        'mod_storycategoryadmin_titleth',
        'storycategoryadmin'
    ),
    $objLanguage->languageText(
        'mod_storycategoryadmin_datecreated',
        'storycategoryadmin'
    ),
    $objLanguage->languageText(
        'mod_storycategoryadmin_creatorid',
        'storycategoryadmin'
    ),
    $objLanguage->languageText(
        'mod_storycategoryadmin_datemodified',
        'storycategoryadmin'
    ),
    $objLanguage->languageText(
        'mod_storycategoryadmin_modifierid',
        'storycategoryadmin'
    ),
);
if ($allowAdmin) {
    $tableHd[] = $objLanguage->languageText(
        'mod_storycategoryadmin_action',
        'storycategoryadmin'
    );
}
$this->Table->addHeader($tableHd, 'heading');

$rowcount = 0;
if (isset($ar) && (is_countable($ar) ? count($ar) : 0) > 0) {
    foreach ($ar as $line) {
        $oddOrEven = ($rowcount === 0) ? 'odd' : 'even';
        $tableRow = array(
            $escape($line['category']),
            $escape($line['title']),
            $escape($line['datecreated']),
            $escape($this->objUser->fullName($line['creatorid'])),
            $escape($line['datemodified']),
        );

        $modifierId = $line['modifierid'];
        $tableRow[] = ($modifierId !== '')
            ? $escape($this->objUser->fullName($modifierId))
            : '';

        if ($allowAdmin) {
            $editLabel = $this->objLanguage->code2Txt(
                'mod_storycategoryadmin_editalt',
                'storycategoryadmin'
            );
            $actions = array(
                $iconLink(
                    $this->uri(array('action' => 'edit', 'id' => $line['id'])),
                    'pencil',
                    $editLabel
                ),
            );

            $deleteLabel = $this->objLanguage->code2Txt(
                'mod_storycategoryadmin_delalt',
                'storycategoryadmin'
            );
            $deleteLink = $normaliseUri($this->uri(array(
                'action' => 'delete',
                'confirm' => 'yes',
                'id' => $line['id'],
            )));
            $objConfirm = $this->newObject('confirm', 'utilities');
            $objConfirm->setConfirm(
                $objIconService->render('trash-2', array(
                    'label' => $deleteLabel,
                    'class' => 'story-admin-page__action-icon',
                )),
                $deleteLink,
                $this->objLanguage->code2Txt(
                    'mod_storycategory_confirm',
                    'storycategoryadmin',
                    array('ITEM' => $line['category'])
                )
            );
            $actions[] = $objConfirm->show();
            $tableRow[] = '<span class="story-admin-page__actions">'
                . implode('', $actions) . '</span>';
        }

        $this->Table->addRow($tableRow, $oddOrEven);
        $rowcount = ($rowcount === 0) ? 1 : 0;
    }
}

$rightSideColumn = '<section class="story-admin-page">' . $header
    . '<div class="story-admin-page__table-wrap">'
    . $this->Table->show() . '</div></section>';

$cssLayout->setLeftColumnContent($leftSideColumn);
$cssLayout->setMiddleColumnContent($rightSideColumn);
echo $cssLayout->show();

