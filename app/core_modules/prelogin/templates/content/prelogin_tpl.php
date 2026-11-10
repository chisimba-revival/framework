<?php
/**
 * Render the public page and its administrator-only inline block editor.
 *
 * @category  Chisimba
 * @package   prelogin
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$editing = !empty($preloginEditing);
$canEdit = !empty($preloginCanEdit);
$icons = $this->getObject('iconservice', 'ui');

$renderPlacedBlock = function ($block, $side) use ($escape, $editing, $icons) {
    if ($block['isblock'] == $this->TRUE) {
        $blockType = $side === 'middle' ? 'none' : NULL;
        $content = $this->objBlocks->showBlock(
            $block['blockname'],
            $block['blockmodule'],
            $blockType
        );
    } else {
        $rawContent = html_entity_decode($block['content'], ENT_QUOTES);
        if ($side === 'middle') {
            $content = '<div class="currentstory"><div class="storytitle"><h3>'
                . $escape($block['title'])
                . '</h3></div><div class="abstract">'
                . $rawContent
                . '</div></div>';
        } else {
            $featureBox = $this->newObject('featurebox', 'navigation');
            $content = $featureBox->show($block['title'], $rawContent);
        }
    }

    if (!$editing) {
        return $content;
    }

    $id = $block['id'];
    $moveUp = $this->objLanguage->languageText('phrase_moveup');
    $moveDown = $this->objLanguage->languageText('phrase_movedown');
    $delete = $this->objLanguage->languageText('word_delete');
    $links = array(
        '<a class="chisimba-icon-button" aria-label="' . $escape($moveUp)
            . '" title="' . $escape($moveUp) . '" href="' . $this->uri(array(
            'action' => 'moveup',
            'id' => $id,
        ), 'prelogin') . '">' . $icons->render('arrow-up', array('decorative' => true)) . '</a>',
        '<a class="chisimba-icon-button" aria-label="' . $escape($moveDown)
            . '" title="' . $escape($moveDown) . '" href="' . $this->uri(array(
            'action' => 'movedown',
            'id' => $id,
        ), 'prelogin') . '">' . $icons->render('arrow-down', array('decorative' => true)) . '</a>',
        '<a class="chisimba-icon-button chisimba-button-danger" aria-label="'
            . $escape($delete) . '" title="' . $escape($delete) . '" href="' . $this->uri(array(
            'action' => 'delete',
            'id' => $id,
        ), 'prelogin') . '" data-prelogin-delete="1">'
            . $icons->render('trash-2', array('decorative' => true)) . '</a>',
    );
    return '<div class="prelogin-edit-block"><div class="prelogin-edit-block__tools"><strong>'
        . $escape($block['title'])
        . '</strong><span class="prelogin-edit-block__actions">' . implode('', $links) . '</span></div>'
        . $content . '</div>';
};

$renderColumn = function ($side) use ($editing, $renderPlacedBlock) {
    $output = '';
    $blocks = $editing
        ? $this->objPLBlocks->getBlocks($side)
        : $this->objPLBlocks->getVisibleBlocks($side);
    if (!is_array($blocks)) {
        return $output;
    }
    foreach ($blocks as $block) {
        // Login is part of the public page itself. Ignore stale installations
        // that still hold it as a movable pre-login block.
        if ($block['isblock'] == $this->TRUE
            && $block['blockmodule'] === 'security'
            && $block['blockname'] === 'login') {
            continue;
        }
        if (!$editing && $block['visible'] != $this->TRUE) {
            continue;
        }
        $rendered = $renderPlacedBlock($block, $side);
        // Acquisition blocks deliberately become empty after login. Do not
        // retain an empty placement shell or its layout spacing for them.
        if (!$editing && trim((string) $rendered) === '') {
            continue;
        }
        $output .= '<div class="prelogin-placed-block">'
            . $rendered . '</div>';
    }
    return $output;
};

$renderAddControl = function ($side, $blocks) use ($escape) {
    $action = $this->uri(
        array('action' => 'addregisteredblock'),
        'prelogin'
    );
    $label = $this->objLanguage->languageText(
        'mod_prelogin_chooseblock',
        'prelogin'
    );
    $button = $this->objLanguage->languageText(
        'mod_prelogin_addblock',
        'prelogin'
    );
    $output = '<form class="prelogin-add-block" method="post" action="'
        . $action . '"><input type="hidden" name="side" value="'
        . $escape($side) . '"><label>' . $escape($label)
        . '<select name="blockid" required><option value="">'
        . $escape($label) . '</option>';
    foreach ($blocks as $block) {
        // Registry labels can contain legacy HTML entities. Decode them once
        // before escaping the option text for safe, normal presentation.
        $displayTitle = html_entity_decode(
            (string) $block['displaytitle'],
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        $output .= '<option value="' . $escape($block['descriptor']) . '">'
            . $escape($displayTitle) . ' ('
            . $escape($block['moduleid']) . ')</option>';
    }
    $output .= '</select></label><button type="submit">'
        . $escape($button) . '</button></form>';
    return $output;
};

$loginContent = $this->objBlocks->showBlock('login', 'security');
$fixedLabel = $editing ? '<p class="prelogin-fixed-block__label">'
    . $icons->render('lock-keyhole', array('decorative' => true)) . '<span>'
    . $escape($this->objLanguage->languageText('mod_prelogin_builtinlogin', 'prelogin'))
    . '</span></p>' : '';
$leftContent = '<div class="prelogin-fixed-block">' . $fixedLabel . $loginContent . '</div>'
    . $renderColumn('left');
$middleContent = $renderColumn('middle');
$rightContent = $renderColumn('right');

if ($editing) {
    $leftContent .= $renderAddControl('left', $smallBlocks);
    $middleContent .= $renderAddControl('middle', $wideBlocks);
    $rightContent .= $renderAddControl('right', $smallBlocks);
}

$editControl = '';
if ($canEdit) {
    if ($editing) {
        $editUrl = $this->uri(NULL, 'prelogin');
        $editKey = 'mod_context_turneditingoff';
    } else {
        $editUrl = $this->uri(array('action' => 'admin'), 'prelogin');
        $editKey = 'mod_context_turneditingon';
    }
    $editControl = '<div id="editmode" class="prelogin-edit-control">'
        . '<div id="modeswitch_wrapper" class="'
        . ($editing ? 'editing_on' : 'editing_off') . '">'
        . '<a class="button chisimba-button-secondary" href="' . $editUrl . '">'
        . $icons->render($editing ? 'eye' : 'settings', array('decorative' => true))
        . '<span>'
        . $escape($this->objLanguage->languageText($editKey, 'context'))
        . '</span></a></div></div>';
}

if ($editing) {
    $deletePrompt = $escape(
        $this->objLanguage->languageText('phrase_delete')
    );
    $script = '<script>(function(){document.addEventListener("click",function(event){'
        . 'var link=event.target.closest("[data-prelogin-delete]");'
        . 'if(link&&!window.confirm("' . addslashes($deletePrompt) . '"))'
        . '{event.preventDefault();}});})();</script>';
    $this->appendArrayVar('headerParams', $script);
}

$cssLayout = $this->newObject('csslayout', 'htmlelements');
$cssLayout->setNumColumns(3);
$cssLayout->putThreeColumnFixInHeader();
$cssLayout->setLeftColumnContent($leftContent);
$cssLayout->setMiddleColumnContent($middleContent);
$cssLayout->setRightColumnContent($rightContent);

echo $editControl . $cssLayout->show();
?>
