<?php
/**
 * Compatibility entry point for sites which still name kenga-learn as their
 * skin. The UI implementation lives exclusively in chisimba-reborn; this
 * adapter selects its KengaLearn canvas.
 */
$canvas = 'kenga-learn';
require $objConfig->getSiteRootPath()
    . 'skins/chisimba-reborn/templates/page/page_template.php';
