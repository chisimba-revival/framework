<?php
/** Contract for bounded data deletion in module updates. @author Derek Keats */
$patch=file_get_contents(dirname(__DIR__).'/classes/patch_class_inc.php');
$checks=array(
    'deleteRows operation is explicit'=>str_contains($patch,"case 'deleteRows'"),
    'empty conditions are rejected'=>str_contains($patch,'deleteRows requires conditions'),
    'table and field names are bounded'=>substr_count($patch,"preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/")>=2,
    'conditions are joined with AND'=>str_contains($patch,"implode(' AND ', \$where)"),
    'database deletion failure stops the update'=>str_contains($patch,'PEAR::isError($deleteResult)'),
    'applied deletion is recorded'=>str_contains($patch,"\$this->objModule->insert(\$patch, 'tbl_module_patches')"),
);
$failed=array_keys(array_filter($checks,static fn($passed)=>!$passed));
if($failed){fwrite(STDERR,'Failed: '.implode(', ',$failed).PHP_EOL);exit(1);}
echo "Module update row-deletion contract passed.\n";
