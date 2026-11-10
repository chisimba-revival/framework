<?php
/**
* @package toolbar
*/

/**
* Layout template for the test module
*/
$cssLayout = $this->newObject('csslayout', 'htmlelements');
$cssLayout->setNumColumns(1);
$ret = '<div class="toolbar_main">' . $this->getContent() . '</div>';
$cssLayout->setMiddleColumnContent($ret);

echo $cssLayout->show();
?>
