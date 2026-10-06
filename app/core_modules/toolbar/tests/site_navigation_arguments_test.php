<?php
/** Reconciled shared navigation must reject unsafe destination fields. */
class ChisimbaObject {}
require dirname(__DIR__).'/classes/navigationservice_class_inc.php';
if (navigationservice::destinationArguments('slug=privacy&id=page-1') !== ['slug'=>'privacy','id'=>'page-1']) throw new RuntimeException('Valid destination lost');
foreach (['module=security','action=logout','token=secret','slug=a&slug=b','slug=%3Cscript%3E','bad','slug=//evil.test','password=a'] as $query) {
    try { navigationservice::destinationArguments($query); } catch (InvalidArgumentException $e) { continue; }
    throw new RuntimeException('Unsafe destination accepted');
}
echo "PASS: shared site navigation accepts bounded fields and rejects reserved, duplicate and unsafe values\n";
