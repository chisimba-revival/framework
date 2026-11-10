<?php
/** User preference editing through the native document service. */
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
class userparamsadmin extends controller
{
    public $objDbUserparamsadmin;
    public $objLanguage;
    public $objUser;
    private $csrf;
    public function init()
    {
        $this->objDbUserparamsadmin=$this->getObject('dbuserparamsadmin');
        $this->objLanguage=$this->getObject('language','language');
        $this->objUser=$this->getObject('user','security');
    }
    private function tokens()
    {
        if ($this->csrf === null) { $this->csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf']; }
        return $this->csrf;
    }
    public function dispatch()
    {
        if (!$this->objUser->isLoggedIn()) { return $this->nextAction(null,array(),'security'); }
        $this->setLayoutTemplate('preferences_layout_tpl.php');
        $action=$this->getParam('action','view');
        $this->setVar('preferencesError',null);
        $this->setVar('preferencesCsrf',$this->tokens()->issue('userparams_save'));
        if (in_array($action,array('save','delete'),true)) {
            $token=$this->getParam('preferences_csrf','');
            $revision=$this->getParam('preferences_revision','');
            $valid=($_SERVER['REQUEST_METHOD'] ?? '')==='POST' && is_string($token)
                && $this->tokens()->consume('userparams_save',$token)
                && is_string($revision) && preg_match('/^[a-f0-9]{64}$/D',$revision);
            $key=$this->getParam($action==='delete'?'key':'pname','');
            $value=$this->getParam('ptag','');
            $valid=$valid && is_string($key) && is_string($value);
            $saved=$valid && ($action==='delete'
                ? $this->objDbUserparamsadmin->delete($key,$revision)
                : $this->objDbUserparamsadmin->writeProperties($this->getParam('mode','edit'),$this->objUser->userId(),$key,$value,$revision));
            if ($saved) { return $this->nextAction('view',array(),'userparamsadmin'); }
            $this->setVar('preferencesError',$this->objLanguage->languageText('mod_userparamsadmin_savefailed','userparamsadmin',
                'The change could not be saved. Copy your entered value before reloading to check for newer settings, then try again.'));
            if ($action==='save') {
                $this->setVar('mode',$this->getParam('mode','edit')==='add'?'add':'edit');
                $this->setVar('keyEdit',is_string($key)?$key:'');
                $this->setVar('valueEdit',is_string($value)?$value:'');
                $this->setVar('preferencesRevision',is_string($revision)?$revision:'');
                return 'edit_tpl.php';
            }
        }
        $root=$this->objDbUserparamsadmin->readConfig();
        $this->setVar('ar',$root ? $root->toArray() : array('root'=>array('Settings'=>array())));
        $this->setVar('preferencesRevision',$root ? $this->objDbUserparamsadmin->getRevision() : '');
        if (!$root) {
            $this->setVar('preferencesError',$this->objLanguage->languageText('mod_userparamsadmin_cannotreadfile','userparamsadmin'));
            return 'main_tpl.php';
        }
        if ($action==='edit' || $action==='add') {
            $key=$this->getParam('key','');
            $key=is_string($key)?$key:'';
            $this->setVar('mode',$action);
            $this->setVar('keyEdit',$key);
            $current=$action==='edit' ? $this->objDbUserparamsadmin->getValue($key) : '';
            $suggested=$this->getParam('suggested_value',$current);
            $this->setVar('valueEdit',is_string($suggested)?$suggested:$current);
            return 'edit_tpl.php';
        }
        return 'main_tpl.php';
    }
}
