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

foreach (json_decode(file_get_contents(dirname(__DIR__).'/dependencies/retired-rpc-paths.json'),true,512,JSON_THROW_ON_ERROR) as $path) {
    if (file_exists(dirname(__DIR__,2).'/app/'.$path)) throw new RuntimeException('Retired RPC implementation returned: '.$path);
}
echo "PASS retired RPC implementation classes absent; Trac client remains separately supported\n";
