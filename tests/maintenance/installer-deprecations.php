<?php
/** Read-only installed-runtime regression for installer services. @author Derek Keats */
if (PHP_SAPI !== 'cli' || !getenv('CHISIMBA_RUNTIME_ROOT')) {
    fwrite(STDERR, "Run with CHISIMBA_RUNTIME_ROOT pointing to an installed local site.\n");
    exit(64);
}
chdir(getenv('CHISIMBA_RUNTIME_ROOT'));
$GLOBALS['kewl_entry_point_run']=true;
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost';
$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';
$engine=new engine();
// Replace the legacy handler so suppression cannot turn a warning into a pass.
set_error_handler(static function($severity,$message,$file,$line){
    throw new ErrorException($message,0,$severity,$file,$line);
});
try {
    $admin=$engine->getObject('modulesadmin','modulecatalogue');
    $patch=$engine->getObject('patch','modulecatalogue');
    $registration=$engine->getObject('register','toolbar');
    if (!is_array($admin->listDbTables()) || !is_array($patch->checkModules())) {
        throw new RuntimeException('Installer discovery failed.');
    }
    // Newly assigned properties must be declared, including update-only services.
    foreach ([$admin,$patch,$registration] as $object) {
        $reflection=new ReflectionObject($object);
        foreach ($reflection->getProperties() as $property) {
            if (!$property->isDefault()) throw new RuntimeException('Undeclared installer property: '.$property->getName());
        }
    }
    echo "PASS installer service initialisation and update discovery with strict diagnostics; no database changes.\n";
} finally { restore_error_handler(); }
