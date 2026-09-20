<?php
$GLOBALS['kewl_entry_point_run']=true;
class dbtable{}
require __DIR__.'/../patches/installscripts_class_inc.php';
$installer=(new ReflectionClass('systext_installscripts'))->newInstanceWithoutConstructor();
// No dependencies initialised: any legacy delete path would fail this test.
$installer->postinstall('1.916');
$xml=simplexml_load_file(__DIR__.'/../sql/systextdata.xml');
foreach(['webinar','webinars','speaker','speakers'] as $term){$rows=$xml->xpath("tbl_systext_text[textinfo='$term']");if(count($rows)!==1)throw new RuntimeException('Missing or duplicate seed '.$term);$maps=$xml->xpath("tbl_systext_abstract[textId='init_$term']");if(count($maps)!==8)throw new RuntimeException('Missing default system mappings');}
echo "PASS modern upgrade skips destructive legacy cleanup; singular/plural seeds for all default systems.\n";
