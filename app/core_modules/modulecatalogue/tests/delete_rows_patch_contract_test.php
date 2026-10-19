<?php
/** Contract for bounded data deletion in module updates. @author Derek Keats */
$patch=file_get_contents(dirname(__DIR__).'/classes/patch_class_inc.php');
$checks=array(
    'deleteRows operation is explicit'=>str_contains($patch,"case 'deleteRows'"),
    'empty conditions are rejected'=>str_contains($patch,'requires conditions or values'),
    'identifiers are validated'=>str_contains($patch,'private function patchIdentifier'),
    'conditions are joined with AND'=>str_contains($patch,"implode(' AND ', \$where)"),
    'deletion binds values'=>str_contains($patch,"\$this->executePrepared('DELETE FROM"),
    'receipt is JSON'=>str_contains($patch,"'state'=>\$state"),
);

$failed=array_keys(array_filter($checks,static fn($passed)=>!$passed));
if($failed){fwrite(STDERR,'Failed: '.implode(', ',$failed).PHP_EOL);exit(1);}
echo "Module update row-deletion contract passed.\n";
