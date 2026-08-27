<?php
/** Course cards must open the modern control panel and offer no casual delete. */
$source=file_get_contents(dirname(__DIR__).'/classes/displaycontext_class_inc.php');
$checks=array(
    'management enters the selected course'=>str_contains($source,"'action' => 'joincontext'")&&str_contains($source,"'contextcode' => \$context ['contextcode']"),
    'management opens the modern control panel'=>str_contains($source,"'contextaction' => 'controlpanel'"),
    'course cards have no delete route'=>!str_contains($source,"'action' => 'delete', 'contextcode'"),
);
foreach($checks as $name=>$ok){if(!$ok){fwrite(STDERR,"FAIL: {$name}\n");exit(1);}echo "PASS: {$name}\n";}
