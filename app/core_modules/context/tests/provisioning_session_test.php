<?php
/** Course provisioning can leave the current editor session untouched. @author Derek Keats */
$GLOBALS['kewl_entry_point_run']=true;
class dbTable {
    public $objUser;
    public $commits=0;
    public function valueExists(...$args){return false;}
    public function insert($data){return 'created';}
    public function beginTransaction(){}
    public function commitTransaction(){$this->commits++;}
    public function rollbackTransaction(){}
    public function getObject(...$args){return new class {public function createGroups(...$args){return true;}};}
}
require dirname(__DIR__).'/classes/dbcontext_class_inc.php';
class FixtureContext extends dbcontext {
    public $switches=0,$indexes=0;
    public function joinContext($code=''){$this->switches++;return true;}
    public function indexCreatedContext($code){$this->indexes++;}
}
$c=new FixtureContext();$c->objUser=new class {public function userId(){return 'fixture';}};
$c->createContext('normal','Normal');
if($c->switches!==1 || $c->commits!==1 || $c->indexes!==1)throw new RuntimeException('Default provisioning changed');
$c->createContext('outline','Outline','Unpublished','Private','',false,0,'0','','standard','free',false,'private',null,false,false);
if($c->switches!==1 || $c->commits!==1 || $c->indexes!==1)throw new RuntimeException('Orchestrated provisioning switched session, committed or indexed');
echo "PASS: default creation remains compatible; orchestrated creation leaves session, indexing and transaction ownership to its caller\n";
