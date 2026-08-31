<?php
/**
 * Contract for the Active Knowledge Map content token.
 *
 * @author Derek Keats
 * @package filters
 */
$source=file_get_contents(dirname(__DIR__).'/classes/parse4knowmap_class_inc.php');
$checks=array(
    'canonical token is recognised'=>str_contains($source,'knowmap\\s+id'),
    'identifier is strictly bounded'=>str_contains($source,'[a-f0-9]{32}'),
    'rendering belongs to module service'=>str_contains($source,"getObject('knowmapembedservice','knowledgemap')")
);
foreach($checks as $name=>$ok){if(!$ok){fwrite(STDERR,"FAIL: $name\n");exit(1);}echo "PASS: $name\n";}
