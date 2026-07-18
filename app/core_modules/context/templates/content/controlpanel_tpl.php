<?php

$this->loadClass('htmlheading', 'htmlelements');
$header = new htmlheading();
$header->type = 1;
$header->str = $contextTitle.': '.ucwords($objLanguage->languageText('phrase_controlpanel', 'system', 'Control panel'));
$ret = $header->show();
$cpBlocks = array();
$objBlocks = $this->getObject('blocks', 'blocks');
$cpBlocks[] = $objBlocks->showBlock('contextsettings', 'context', NULL, 20, TRUE, FALSE);
$cpBlocks[] = $objBlocks->showBlock('sasiwebserver', 'sasicontext', NULL, 20, TRUE, FALSE);
$cpBlocks[] = $objBlocks->showBlock('contextmembers', 'contextgroups', NULL, 20, TRUE, FALSE);
$cpBlocks[] = $objBlocks->showBlock('contextmodules', 'context', NULL, 20, TRUE, FALSE);
//$cpBlocks[] = $objBlocks->showBlock('contextstats', 'context', NULL, 20, TRUE, FALSE);
$left = array();
$right = array();
$counter = 0;
foreach ($cpBlocks as $block)
{
    $counter++;
    if ($counter % 2 == 1) {
        $left[] = $block;
    } else {
        $right[] = $block;
    }
}
if ((is_countable($left) ? count($left) : 0) > 0) {
    $ret .= '<div class="context_cp_left">';
    foreach ($left as $block) {
        $ret .= $block;
    }
    $ret .= '</div>';
}
if ((is_countable($right) ? count($right) : 0) > 0) {
    $ret .= '<div class="context_cp_right">';
    foreach ($right as $block)
    {
        $ret .= $block;
    }
    $ret .= '</div>';
}
$ret .= '<br clear="all" />';
echo $ret;
?>
