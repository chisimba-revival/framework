<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public static $objects=[];function getObject($name,$module=null){return self::$objects[$name];}}
require dirname(__DIR__).'/classes/communicationdeliverygate_class_inc.php';
class Config {public $value='derekkeats@gmail.com';function getValue(...$args){return $this->value;}}
class Catalogue{public $exists=true;function checkIfRegistered($m){return $this->exists;}}
class Policy{public $allow=true;function allows($meta){return $this->allow;}}
$c=new Config;$catalogue=new Catalogue;$policy=new Policy;ChisimbaObject::$objects=['dbsysconfig'=>$c,'modules'=>$catalogue,'communicationpolicy'=>$policy];$g=new communicationdeliverygate;$n=0;
function check($v){global $n;if(!$v)throw new RuntimeException('Gate check failed: '.($n+1));$n++;}
check($g->recipientAllowed('DEREKKEATS@gmail.com'));check(!$g->recipientAllowed('other@example.org'));check(!$g->allows(['recipient'=>'other@example.org']));
$m=['recipient'=>'derekkeats@gmail.com'];check($g->allows($m));$m['metadata_json']='{invalid';check(!$g->allows($m));$m['metadata_json']=json_encode(['policy_module'=>'../webinar']);check(!$g->allows($m));$m['metadata_json']=json_encode(['policy_module'=>'webinar']);check($g->allows($m));$policy->allow=false;check(!$g->allows($m));$policy->allow=true;$catalogue->exists=false;check(!$g->allows($m));$c->value='';check($g->recipientAllowed('other@example.org'));
echo "$n dispatch gate checks passed\n";
