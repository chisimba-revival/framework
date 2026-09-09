<?php
/** Exercise the real catalogue version comparison against installed legacy versions.
 * @author Derek Keats
 * @package communications
 */
class dbtable {
    public $installed;
    public function getAll() { return array(array('module_id'=>'communications','module_version'=>$this->installed)); }
}
require dirname(__DIR__, 2) . '/modulecatalogue/classes/patch_class_inc.php';
class communicationsPatchFixture extends patch { public $objModFile; }
$patch = new communicationsPatchFixture();
$patch->objModFile = new class {
    public function findRegisterFile($module) { return dirname(__DIR__) . '/register.conf'; }
    public function findSqlXML($module) { return false; }
};
foreach (array('0.1','0.1.1','0.1.2','0.1.3','0.1.4','0.1.5') as $old) {
    $patch->installed=$old;
    $updates=$patch->checkModules();
    if(count($updates)!==1||$updates[0]['module_id']!=='communications')throw new RuntimeException('Update hidden from '.$old);
    echo 'PASS: Communications update visible from '.$old.PHP_EOL;
}
$patch->installed='0.106';
if($patch->checkModules()!==array())throw new RuntimeException('Already-current update offered again');
echo "PASS: current Communications version is not offered again\n";
