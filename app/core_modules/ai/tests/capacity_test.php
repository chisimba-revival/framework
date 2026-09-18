<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject{public function getObject($name,$module=null){return $GLOBALS['services'][$name];}}
require dirname(__DIR__).'/classes/aicapacity_class_inc.php';
$GLOBALS['services']['aiservice']=new class{public $model='gpt-5.6';public function providerStatus(){return ['provider'=>'openai','model'=>$this->model];}};
$GLOBALS['services']['dbsysconfig']=new class{public $values=[];public function getValue($key,$module,$default){return $this->values[$key]??$default;}};
$c=new aicapacity();$limits=$c->forTextGeneration();if($limits['sourceBytes']<89559||$limits['outputTokens']!==32000)throw new RuntimeException('Known model bounds');
$GLOBALS['services']['aiservice']->model='unknown';try{$c->forTextGeneration();throw new RuntimeException('Unknown model accepted');}catch(DomainException $e){}
$GLOBALS['services']['dbsysconfig']->values=['AI_CONTEXT_TOKENS'=>32000,'AI_MAX_OUTPUT_TOKENS'=>4096];$limits=$c->forTextGeneration();if($limits['sourceBytes']!==19712)throw new RuntimeException('Configured headroom');
echo "PASS known capacity, unknown model refusal, configured output and input headroom.\n";
