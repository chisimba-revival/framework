<?php
error_reporting(E_ALL);
set_error_handler(static function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
$GLOBALS['kewl_entry_point_run']=true;
class dbtable { public $dbType='mysqli'; }
require dirname(__DIR__).'/classes/dbsysconfig_class_inc.php';
class ParameterCacheProbe extends dbsysconfig {
    public $reads=0;
    public function getAll($where=null,$fields=null){
        $this->reads++;
        $value=str_contains($where,"pmodule='first'")?'First':'Second';
        return [ ['pname'=>'NAME','pvalue'=>$value],['pname'=>'ZERO','pvalue'=>'0'],['pname'=>'EMPTY','pvalue'=>''],['pname'=>'NULL','pvalue'=>null],['pname'=>'dbType','pvalue'=>'configuration value'] ];
    }
}
function checkCache($condition,$message){if(!$condition)throw new RuntimeException($message);}
$c=new ParameterCacheProbe();
checkCache($c->getValue('NAME','first')==='First','First module value');
checkCache($c->getValue('NAME','second')==='Second','Module names must isolate parameters');
$reads=$c->reads;
checkCache($c->getValue('NAME','first')==='First' && $c->reads===$reads,'Cached reads retain module identity');
checkCache($c->getValue('ZERO','first','fallback')==='0','Zero must not become a default');
checkCache($c->getValue('EMPTY','first','fallback')==='','Empty must not become a default');
checkCache($c->getValue('NULL','first','fallback')==='fallback','Null retains existing fallback semantics');
checkCache($c->getValue('MISSING','first','fallback')==='fallback','Missing retains default');
checkCache($c->getValue('dbType','first')==='configuration value' && $c->dbType==='mysqli','Parameter names must not overwrite service state');
echo "PASS: strict configuration cache, module isolation, defaults and service-property collisions\n";
