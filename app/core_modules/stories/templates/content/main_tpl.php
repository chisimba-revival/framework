<?php
// Administration view for tbl_stories.

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

$textModule = 'stories';
$allowAdmin = true;
$title = $this->objLanguage->code2Txt('mod_stories_title', $textModule);
$addLabel = $this->objLanguage->code2Txt('mod_stories_addalt', $textModule);
$instructions = $this->objLanguage->code2Txt(
    'mod_stories_mainleftside',
    $textModule
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
    $this->objLanguage->languageText('word_category'),
    $this->objLanguage->languageText('word_author'),
    $this->objLanguage->languageText('word_language'),
    $this->objLanguage->languageText('word_title'),
    $this->objLanguage->languageText('phrase_dateposted'),
    $this->objLanguage->code2Txt('phrase_expirationdate'),
    $this->objLanguage->code2Txt('mod_stories_alwaysontop', $textModule),
    $this->objLanguage->code2Txt('phrase_isactive'),
    $this->objLanguage->code2Txt('mod_stories_actions', $textModule),
);
$this->Table->addHeader($tableHd, 'heading');

$objExp = $this->getObject('dateandtime', 'utilities');
$rowcount = 0;
if (isset($ar) && (is_countable($ar) ? count($ar) : 0) > 0) {
    foreach ($ar as $line) {
        $oddOrEven = ($rowcount === 0) ? 'odd' : 'even';
        $tableRow = array();

        $activeLabel = ((int) $line['isactive'] === 1)
            ? $this->objLanguage->code2Txt('mod_stories_isactivealt', $textModule)
            : $this->objLanguage->code2Txt('mod_stories_isnotactivealt', $textModule);
        $activeIcon = ((int) $line['isactive'] === 1) ? 'circle-check' : 'x';
        $activeClass = ((int) $line['isactive'] === 1)
            ? 'story-admin-page__status--active'
            : 'story-admin-page__status--inactive';
        $activeState = '<span class="story-admin-page__status '
            . $activeClass . '">'
            . $objIconService->render($activeIcon, array(
                'label' => $activeLabel,
                'class' => 'story-admin-page__status-icon',
            )) . '</span>';

        $stickyLabel = ((int) $line['issticky'] === 1)
            ? $this->objLanguage->code2Txt('mod_stories_alwaysontopalt', $textModule)
            : $this->objLanguage->code2Txt('mod_stories_notalwaysontopalt', $textModule);
        $stickyIcon = ((int) $line['issticky'] === 1) ? 'pin' : 'minus';
        $stickyClass = ((int) $line['issticky'] === 1)
            ? 'story-admin-page__status--pinned'
            : 'story-admin-page__status--inactive';
        $stickyState = '<span class="story-admin-page__status '
            . $stickyClass . '">'
            . $objIconService->render($stickyIcon, array(
                'label' => $stickyLabel,
                'class' => 'story-admin-page__status-icon',
            )) . '</span>';

        $formattedExpiration = $this->formatDate($line['expirationdate']);
        $expirationDate = '<span class="story-admin-page__date">';
        if ($objExp->hasExpired($formattedExpiration)) {
            $expiredLabel = $this->objLanguage->code2Txt(
                'mod_stories_expiredalt',
                $textModule
            );
            $expirationDate .= '<span class="story-admin-page__status '
                . 'story-admin-page__status--expired">'
                . $objIconService->render('clock', array(
                    'label' => $expiredLabel,
                    'class' => 'story-admin-page__status-icon',
                )) . '</span>';
        }
        $expirationDate .= '<span>' . $escape($formattedExpiration) . '</span></span>';

        $tableRow[] = $escape($line['category']);
        $tableRow[] = $escape($this->objUser->fullName($line['creatorid']));
        $tableRow[] = $escape($line['language']);
        $tableRow[] = $escape($line['title']);
        $tableRow[] = $escape($this->formatDate($line['datecreated']));
        $tableRow[] = $expirationDate;
        $tableRow[] = $stickyState;
        $tableRow[] = $activeState;

        $actions = array();
        $translateLabel = $this->objLanguage->code2Txt(
            'mod_stories_translate',
            $textModule
        );
        $actions[] = $iconLink(
            $this->uri(array(
                'action' => 'translate',
                'category' => $line['category'],
                'parentid' => $line['id'],
            ), 'stories'),
            'languages',
            $translateLabel
        );

        if ($allowAdmin) {
            $editLabel = $this->objLanguage->code2Txt(
                'mod_stories_editalt',
                $textModule
            );
            $actions[] = $iconLink(
                $this->uri(array('action' => 'edit', 'id' => $line['id'])),
                'pencil',
                $editLabel
            );

            $deleteLabel = $this->objLanguage->code2Txt(
                'mod_stories_delalt',
                $textModule
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
                $this->objLanguage->code2Txt('mod_stories_confirm', $textModule)
            );
            $actions[] = $objConfirm->show();
        }

        $tableRow[] = '<span class="story-admin-page__actions">'
            . implode('', $actions) . '</span>';
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

