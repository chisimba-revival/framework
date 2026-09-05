<?php
$source = file_get_contents(dirname(__FILE__) . '/../classes/modulesadmin_class_inc.php');
$clear = strpos($source, '$this->objModuleBlocks->deleteModuleBlocks($moduleId);');
$normal = strpos($source, "isset(\$registerdata['BLOCK'])");
$wide = strpos($source, "isset(\$registerdata['WIDEBLOCK'])");
if ($clear === false || $normal === false || $wide === false
    || $clear > $normal || $clear > $wide) {
    fwrite(STDERR, "Module block declarations are not replaced before registration\n");
    exit(1);
}
fwrite(STDOUT, "PASS: module block registry follows the current manifest\n");
