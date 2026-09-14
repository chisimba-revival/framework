<?php
/** Shared dispatch gate for recipient restrictions and module-owned delivery policy. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class communicationdeliverygate extends ChisimbaObject
{
    public function init(){}
    public function recipientAllowed($email)
    {
        $configured=trim((string)$this->getObject('dbsysconfig','sysconfig')->getValue('COMMUNICATION_ALLOWED_RECIPIENTS','communications',''));
        if($configured==='')return true;
        $allowed=array_map('strtolower',preg_split('/[\s,;]+/',$configured,-1,PREG_SPLIT_NO_EMPTY));
        return in_array(strtolower(trim((string)$email)),$allowed,true);
    }
    public function allows(array $message)
    {
        if(!$this->recipientAllowed($message['recipient']??''))return false;
        $raw=$message['metadata_json']??null;
        $meta=$raw?json_decode($raw,true):[];
        if(!is_array($meta))return false;
        if(!isset($meta['policy_module']))return true;
        $module=$meta['policy_module'];
        if(!is_string($module)||!preg_match('/^[a-z][a-z0-9_]{0,49}$/D',$module))return false;
        try {
            if(!$this->getObject('modules','modulecatalogue')->checkIfRegistered($module))return false;
            return $this->getObject('communicationpolicy',$module)->allows($meta)===true;
        } catch(Throwable $error) { return false; }
    }
}
