<?php
/** Exercise shared runtime state with every warning/deprecation treated as failure. */
error_reporting(E_ALL);
set_error_handler(static function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
$GLOBALS['kewl_entry_point_run']=true;
chdir($argv[1] ?? dirname(__DIR__,2).'/app');
require 'classes/core/object_class_inc.php';
require 'classes/core/dbtable_class_inc.php';
require 'classes/core/access_class_inc.php';
class WarningAuditConfig {
    public $layer='MDB2';
    public function serverName(){return 'testserver';}
    public function getenable_memcache(){return 'FALSE';}
    public function getenable_apc(){return 'FALSE';}
    public function geterror_reporting(){return 'production';}
    public function getenable_adm(){return 'FALSE';}
    public function getenable_dbabs(){return $this->layer;}
    public function getValue(...$arguments){return null;}
    public function getPrelogin(...$arguments){return 'prelogin';}
}
class WarningAuditEngine {
    public $_moduleName='probe',$luAdmin=null,$lu=null,$enableLogging=false,$eventDispatcher=null,$appid='probe';
    public $pdsn=['phptype'=>'pgsql'];
    public $config;
    public function __construct(){ $this->config=new WarningAuditConfig(); }
    public function getDbObj(){return (object)['phptype'=>'mysqli'];}
    public function getObject($name,$module){
        if($name==='user')return new class { public function userid(){return null;} };
        return $this->config;
    }
}
function verify($value,$message){if(!$value)throw new RuntimeException($message);}
$engine=new WarningAuditEngine();
$mysql=new dbTable($engine,'probe');
verify($mysql->dbType==='mysqli','MDB2 driver is available on the instance');
verify($mysql->cachePrefix==='testserver_probe_','Identifier/cache prefix is retained');
$engine->config->layer='PDO';
$postgres=new dbTable($engine,'probe');
verify($postgres->dbType==='pgsql','PDO driver is available on the instance');
verify($mysql->dbType==='mysqli','Initialising another connection does not alter existing driver state');
$_SERVER['SCRIPT_NAME']='/ch/index.php';
$object=new ChisimbaObject();
$expected=substr(md5('/ch/index.php'),0,5).'~';
verify($object->sessionKey()===$expected && $object->sessionKey()===$expected,'Session namespace stays compatible and cached');
$access=new access($engine,'probe');
$flag=(new ReflectionClass($access))->getProperty('logoutdestroy');
verify($flag->getValue($access)===false,'Missing logout setting keeps its existing false semantics');
echo "PASS: strict MDB2/PDO instance state, cache prefix, session namespace and missing access settings\n";
