<?php
/** Executable retirement boundary regression. @author Derek Keats */
$GLOBALS['kewl_entry_point_run'] = true;
class controller {
    public function getObject(...$args) { throw new RuntimeException('Retired route loaded a service'); }
    public function setPageTemplate($value) {}
    public function setLayoutTemplate($value) {}
    public function setVar($name, $value) {}
}
foreach (array('api','packages') as $name) {
    require dirname(__DIR__,2).'/app/core_modules/'.$name.'/controller.php';
    $module = new $name();
    $module->init();
    foreach (array(null,'serveapi','getmodule','upgrademodule','cleanupzips','test') as $action) {
        http_response_code(200);
        if ($module->dispatch($action) !== 'service_retired_tpl.php' || http_response_code() !== 410) {
            throw new RuntimeException('Retired action remains reachable');
        }
    }
}
echo "PASS: both controllers reject all legacy actions without loading services\n";
