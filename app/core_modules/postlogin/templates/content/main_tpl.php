<?php
$objIcon = $this->newObject('geticon', 'htmlelements');
$editor = $this->getObject('postloginblockeditor', 'postlogin');
$canEdit = $editor->canEdit();
if ($canEdit) {
    echo $this->getObject('buildcanvas', 'canvas')->getScriptValues();
    $attributes=['module'=>'postlogin','contextcode'=>'root',
        'csrf'=>$editor->csrf()->issue($editor->purpose('root')),
        'revision'=>$this->objContextBlocks->editingRevision('root'),
        'cancel'=>html_entity_decode($objLanguage->languageText('word_cancel'),ENT_QUOTES,'UTF-8')];
    foreach(['saving','saved','failed','stale','uncertain','denied','method','csrf-error'] as $key) {
        $attributes[$key]=html_entity_decode($objLanguage->languageText('mod_context_block_'.($key==='csrf-error'?'csrf':$key),'context'),ENT_QUOTES,'UTF-8');
    }
    echo '<div id="block-editor-settings"';
    foreach($attributes as $key=>$value) echo ' data-'.$key.'="'.htmlspecialchars($value,ENT_QUOTES,'UTF-8').'"';
    echo '><p id="block-editor-status" role="status" aria-live="polite"></p></div>';
    echo $this->getJavaScriptFile('contextblocks.js?v=2059', 'context');
}

$this->loadClass('dropdown', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('htmlheading', 'htmlelements');

$objCssLayout = $this->getObject('csslayout', 'htmlelements');
$objCssLayout->setNumColumns(3);
$this->dbSysConfig = $this->getObject('dbsysconfig', 'sysconfig');
$showAdminShortcutBlock = $this->dbSysConfig->getValue('SHOW_SHORTCUTS_BLOCK', 'context');

$utillink = "";
if (strtoupper($showAdminShortcutBlock) == "TRUE") {
    $objIcon->setIcon('plus');
    $plusIcon = $objIcon->show();
    $allactivitylink = new link($this->uri(array('action' => 'selectcontextsactivitydates'), 'context'));
    $allactivitylink->link = $plusIcon . '&nbsp;' . $this->objLanguage->code2Txt('mod_context_allcoursesacitivity', 'context', NULL, 'All [-contexts-] activity');
    $objFeatureBox = $this->newObject('featurebox', 'navigation');
    $content = $allactivitylink->show();

    $block = "shortcuts";
    $hidden = 'default';
    $showToggle = false;
    $showTitle = false;
    $cssClass = "xfeaturebox";
    $utillink = $objFeatureBox->show(
            $this->objLanguage->languageText('mod_contextcontent_shortcuts', 'contextcontent', 'Shortcuts'), $content, $block, $hidden, $showToggle, $showTitle, $cssClass, '');
}

if ($canEdit) {

    $rightBlocksDropDown = new dropdown('rightblocks');
    $rightBlocksDropDown->cssId = 'ddrightblocks';
    $rightBlocksDropDown->addOption('', $objLanguage->languageText('phrase_selectone', 'context', 'Select One') . '...');

    $leftBlocksDropDown = new dropdown('leftblocks');
    $leftBlocksDropDown->cssId = 'ddleftblocks';
    $leftBlocksDropDown->addOption('', $objLanguage->languageText('phrase_selectone', 'context', 'Select One') . '...');

    foreach ($smallDynamicBlocks as $smallBlock) {
        $title = htmlentities(trim($smallBlock['title']));
        if ($title !== "" && $title !== NULL) {
            $title .= "(" . $smallBlock['module'] . ")";
        }
        $rightBlocksDropDown->addOption('dynamicblock|'
                . $smallBlock['id'] . '|' . $smallBlock['module'], $title);
        $leftBlocksDropDown->addOption('dynamicblock|' . $smallBlock['id']
                . '|' . $smallBlock['module'], $title);
    }

    foreach ($smallBlocks as $smallBlock) {
        //Do not include content blocks as they are directly added on right n left options.
        if ($smallBlock["moduleid"] != "contentblocks") {
            $block = $this->newObject('block_' . $smallBlock['blockname'], $smallBlock['moduleid']);
            $moduleId = $smallBlock['moduleid'];
            $title = trim($block->title);
            if ($title !== "" && $title !== NULL) {
                $title .= "(" . $smallBlock['moduleid'] . ")";
            }
            $rightBlocksDropDown->addOption('block|' . $smallBlock['blockname']
                    . '|' . $smallBlock['moduleid'], htmlentities($title));
            $leftBlocksDropDown->addOption('block|' . $smallBlock['blockname']
                    . '|' . $smallBlock['moduleid'], htmlentities($title));
        }
    }

    $wideBlocksDropDown = new dropdown('middleblocks');
    $wideBlocksDropDown->cssId = 'ddmiddleblocks';
    $wideBlocksDropDown->addOption('', $objLanguage->languageText('phrase_selectone', 'context', 'Select One') . '...');

    foreach ($wideDynamicBlocks as $wideBlock) {
        $title = htmlentities($wideBlock['title']);
        if ($title !== "" && $title !== NULL) {
            $title .= "(" . $wideBlock['module'] . ")";
        }
        $wideBlocksDropDown->addOption('dynamicblock|'
                . $wideBlock['id'] . '|' . $wideBlock['module'], $title);
    }

    foreach ($wideBlocks as $wideBlock) {
        if ($wideBlock["moduleid"] != "contentblocks") {
            $block = $this->newObject('block_' . $wideBlock['blockname'], $wideBlock['moduleid']);
            $title = htmlentities($block->title);
            if ($title !== "" && $title !== NULL) {
                $title .= "(" . $wideBlock['moduleid'] . ")";
            }
            $wideBlocksDropDown->addOption('block|' . $wideBlock['blockname'] . '|' . $wideBlock['moduleid'], $title);
        } else {
            // fetch contentblock data
            $block = $this->objBlocks->showBlock($wideBlock["id"], "contentblocks");
            $bData = $this->objBlocksContent->getBlockById($wideBlock["id"]);
            if (!empty($bData)) {
                $bData = $bData[0];
                $title = $bData["title"];
                //parse some abstractions
                $title = $this->objLanguage->abstractText($title);
                if ($title == '') {
                    $title = $wideBlock['blockname'] . ' | ';
                }
                $title .= " (" . $wideBlock['moduleid'] . ")";
                $wideBlocksDropDown->addOption('block|' . $bData["id"] . '|' . "contentblocks", $bData["title"] . ' (contentblocks)');
            }
        }
    }
    //Content Wide Blocks
    /* $contentWdBlks = "";
      foreach ($contentWideBlocks as $contentWideBlock) {
      $contentWdBlks .= $this->objBlocks->showBlock($contentWideBlock["id"],"contentblocks");
      } */
    //Add content wideblocks to options
    if (!empty($contentWideBlocks)) {
        foreach ($contentWideBlocks as $contentWideBlock) {
            $block = $this->objBlocks->showBlock($contentWideBlock["id"], "contentblocks");
            $wideBlocksDropDown->addOption('block|' . $contentWideBlock["id"] . '|' . "contentblocks", htmlentities($contentWideBlock["title"]) . '(contentblocks)');
        }
    }
    //Add content sideblocks to options
    if (!empty($contentSmallBlocks)) {
        foreach ($contentSmallBlocks as $contentSmallBlock) {
            $block = $this->objBlocks->showBlock($contentSmallBlock["id"], "contentblocks");
            $rightBlocksDropDown->addOption('block|' . $contentSmallBlock["id"] . '|' . "contentblocks", htmlentities($contentSmallBlock["title"]) . '(contentblocks)');
            $leftBlocksDropDown->addOption('block|' . $contentSmallBlock["id"] . '|' . "contentblocks", htmlentities($contentSmallBlock["title"]) . '(contentblocks)');
        }
    }
    $rightBlocks = $rightBlocksDropDown->show();
    $leftBlocks = $leftBlocksDropDown->show();

    $button = new button('addrightblock', $objLanguage->languageText('mod_prelogin_addblock', 'system', 'Add Block'));
    $button->cssId = 'rightbutton';

    $rightButton = $button->show();

    $button = new button('addleftblock', $objLanguage->languageText('mod_prelogin_addblock', 'system', 'Add Block'));
    $button->cssId = 'leftbutton';

    $leftButton = $button->show();


    $value = $objLanguage->languageText('mod_context_turneditingon', 'context', 'Turn Editing On');
    $editBut = '<div id="modeswitch_wrapper" class="editing_off"><button type="button" id="editmodeswitchbutton" aria-pressed="false" onclick="switchEditMode()">'.htmlspecialchars($value,ENT_QUOTES,'UTF-8').'</button></div>';
    $editBut .= $this->getObject('contextualhelp','help')->show('postlogin','editing');
}

$header = new htmlheading();
$header->type = 3;
$header->str = $objLanguage->languageText('mod_context_addablock', 'context', 'Add a Block');


$objSysConfig = $this->getObject('dbsysconfig', 'sysconfig');
$postLoginSideMenu = $objSysConfig->getValue('SIDEMENU', 'postlogin');
switch (strtolower($postLoginSideMenu)) {
    case 'elearnpostlogin':
        $elearnPostLoginMenu = $this->newObject('postloginmenu_elearn', 'toolbar');
        $objCssLayout->setLeftColumnContent($elearnPostLoginMenu->show());
        break;
    default:
        $postLoginMenu = $this->newObject('postloginmenu', 'toolbar');
        $objCssLayout->setLeftColumnContent($postLoginMenu->show());
        break;
}



$objCssLayout->leftColumnContent .= '<div id="leftblocks">' . $leftBlocksStr . '</div>';

if ($canEdit) {
    $objCssLayout->leftColumnContent .= '<div id="leftaddblock">' . $header->show() . $leftBlocks;
    $objCssLayout->leftColumnContent .= '<div id="leftpreview"><div id="leftpreviewcontent"></div> ' . $leftButton . ' </div>';
    $objCssLayout->leftColumnContent .= '</div>';
}

$objCssLayout->rightColumnContent = '';

if ($canEdit) {
    $objCssLayout->rightColumnContent .= '<div id="editmode">' . $editBut . $utillink . '</div>';
}
$objCssLayout->rightColumnContent .= '<div id="rightblocks">' . $rightBlocksStr . '</div>';

if ($canEdit) {
    $objCssLayout->rightColumnContent .= '<div id="rightaddblock">' . $header->show() . $rightBlocks;
    $objCssLayout->rightColumnContent .= '<div id="rightpreview"><div id="rightpreviewcontent"></div> ' . $rightButton . ' </div>';
    $objCssLayout->rightColumnContent .= '</div>';
}

$button = new button('addmiddleblock', $objLanguage->languageText('mod_prelogin_addblock', 'system', 'Add Block'));
$button->cssId = 'middlebutton';

$objCssLayout->middleColumnContent = '<div id="middleblocks">' . $middleBlocksStr . '</div>';

if ($canEdit) {
    $objCssLayout->middleColumnContent .= '<div id="middleaddblock">' . $header->show() . $wideBlocksDropDown->show();
    $objCssLayout->middleColumnContent .= '<div id="middlepreview"><div id="middlepreviewcontent"></div> ' . $button->show() . ' </div>';
    $objCssLayout->middleColumnContent .= '</div>';
}

echo $objCssLayout->show();
