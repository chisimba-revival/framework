<?php
/** Catalogue upgrades must never run the retired LiveUser identity conversion. */
class ChisimbaObject {
    public function __call($method, $args) {
        throw new RuntimeException('Unexpected persistence/service call: '.$method);
    }
}
require dirname(__DIR__).'/patches/installscripts_class_inc.php';
$hook = new groupadmin_installscripts();
$hook->init();
foreach ([null, '3.182', '3.183', '9.999'] as $version) {
    $hook->preinstall($version);
    $hook->postinstall($version);
}
echo "PASS group upgrades preserve existing identities without legacy conversion\n";
