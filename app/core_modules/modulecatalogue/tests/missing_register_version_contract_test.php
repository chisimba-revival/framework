<?php
/**
 * Prevent missing module source from crashing the PHP 8.5 update checker.
 *
 * @author Derek Keats
 * @package modulecatalogue
 */
$source=file_get_contents(dirname(__DIR__).'/classes/patch_class_inc.php');
$checks=array(
    'register path is validated before file access'=>str_contains($source,'$registerFile === \'\'')&&str_contains($source,'is_file($registerFile)'),
    'failed file reads return safely'=>str_contains($source,'if ($regdata === false) return FALSE;')
);
foreach($checks as $name=>$ok){if(!$ok){fwrite(STDERR,"FAIL: $name\n");exit(1);}echo "PASS: $name\n";}
?>
