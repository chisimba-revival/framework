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

$renderPlacedBlock = function ($block, $side) use ($escape, $editing) {
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
    $links = array(
        '<a href="' . $this->uri(array(
            'action' => 'moveup',
            'id' => $id,
        ), 'prelogin') . '">'
            . $escape($this->objLanguage->languageText('phrase_moveup'))
            . '</a>',
        '<a href="' . $this->uri(array(
            'action' => 'movedown',
            'id' => $id,
        ), 'prelogin') . '">'
            . $escape($this->objLanguage->languageText('phrase_movedown'))
            . '</a>',
        '<a href="' . $this->uri(array(
            'action' => 'delete',
            'id' => $id,
        ), 'prelogin') . '" data-prelogin-delete="1">'
            . $escape($this->objLanguage->languageText('word_delete'))
            . '</a>',
    );
    return '<div class="prelogin-edit-block"><div class="prelogin-edit-block__tools">'
        . $escape($block['title'])
        . '<span>' . implode(' · ', $links) . '</span></div>'
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
        if (!$editing && $block['visible'] != $this->TRUE) {
            continue;
        }
        $output .= '<div class="prelogin-placed-block">'
            . $renderPlacedBlock($block, $side) . '</div>';
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
        $output .= '<option value="' . $escape($block['descriptor']) . '">'
            . $escape($block['displaytitle']) . ' ('
            . $escape($block['moduleid']) . ')</option>';
    }
    $output .= '</select></label><button type="submit">'
        . $escape($button) . '</button></form>';
    return $output;
};

$leftContent = $renderColumn('left');
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
        . '<a href="' . $editUrl . '">'
        . $escape($this->objLanguage->languageText($editKey, 'context'))
        . '</a></div></div>';
}

if ($editing) {
    $deletePrompt = $escape(
        $this->objLanguage->languageText('phrase_delete')
    );
    $style = <<<'CSS'
<style>
.prelogin-edit-block { position: relative; margin-bottom: .75rem; padding-top: 2.35rem; border: 2px solid var(--color-primary, #087bc1); border-radius: .65rem; overflow: hidden; }
.prelogin-edit-block__tools { position: absolute; inset: 0 0 auto; display: flex; justify-content: space-between; gap: .75rem; padding: .45rem .65rem; background: #e7f2fa; font-size: .85rem; }
.prelogin-add-block { display: grid; gap: .5rem; margin: .75rem 0; padding: .75rem; border: 1px dashed #8ea4b8; border-radius: .65rem; background: #f5f8fb; }
.prelogin-add-block label { display: grid; gap: .35rem; }
.prelogin-add-block select { width: 100%; min-width: 0; padding: .45rem; }
.prelogin-add-block button { justify-self: start; padding: .5rem .8rem; }
</style>
CSS;
    $this->appendArrayVar('headerParams', $style);
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
