<?php
/** Canonical capacity metadata; unknown models require explicit site configuration. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class aicapacity extends ChisimbaObject
{
 public function forTextGeneration(){
  $status=$this->getObject('aiservice','ai')->providerStatus();
  $config=$this->getObject('dbsysconfig','sysconfig');
  $known=($status['provider']??'')==='openai' && in_array($status['model']??'',['gpt-5.6','gpt-5.6-sol','gpt-5.6-terra','gpt-5.6-luna'],true);
  // https://developers.openai.com/api/docs/models/gpt-5.6-sol (2026-09-18).
  $context=$known?1050000:0;$output=$known?128000:0;
  $override=(int)$config->getValue('AI_CONTEXT_TOKENS','ai',0);
  if($override>0)$context=$override;
  $override=(int)$config->getValue('AI_MAX_OUTPUT_TOKENS','ai',0);
  if($override>0)$output=$override;
  if($context<12000||$output<2048)throw new DomainException('capacity_unknown');
  $reserve=min($output,32000,(int)floor($context/3));
  // UTF-8 byte length is a conservative BPE token upper bound, not a token count.
  $bytes=$context-$reserve-8192;
  if($bytes<1000)throw new DomainException('capacity_unknown');
  return ['sourceBytes'=>$bytes,'outputTokens'=>$reserve,'provider'=>$status['provider']??'','model'=>$status['model']??''];
 }
}
