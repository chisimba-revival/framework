<?php
/** Shared skin layout supplies the page's content region. */
$layout=$this->newObject('csslayout','htmlelements');
$layout->setNumColumns(1);
$layout->setMiddleColumnContent($this->getContent());
echo '<div id="onecolumn">' . $layout->show() . '</div>';
