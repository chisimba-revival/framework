<?php
/** Canonical metadata uses configured identity, never an untrusted Host header. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    public function getObject($name,$module=null){return $GLOBALS['objects'][$name];}
}
require dirname(__DIR__).'/classes/pagemetadata_class_inc.php';
$_SERVER['HTTP_HOST']='attacker.invalid';
$GLOBALS['objects']=[
    'altconfig'=>new class {
        public function getSiteRoot(){return 'https://example.invalid/ch/';}
        public function getSiteName(){return 'Fixture';}
    },
    'dbsysconfig'=>new class {public function getValue($key,$module,$default){return $default;}}
];
$metadata=(new pagemetadata())->build('<b>Title</b>','<p>Visible summary</p>',['module'=>'simpleblog','action'=>'view','id'=>'safe']);
if($metadata['pageCanonical']!=='https://example.invalid/ch/index.php?module=simpleblog&action=view&id=safe')throw new RuntimeException('Untrusted canonical root');
if($metadata['og_title']!=='Title'||$metadata['og_content']!=='Visible summary')throw new RuntimeException('Metadata not plain text');
echo "PASS: configured canonical root and plain-text metadata\n";
