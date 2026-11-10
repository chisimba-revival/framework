<?php
/** User preference documents, scoped to the authenticated owner or administrator.
 * Native INI parsing and the shared checked file writer preserve unrelated keys.
 * GPL-2.0-or-later. Original author: Prince Mbekwa. Migration: Derek Keats.
 */
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
require_once dirname(__DIR__, 2) . '/config/classes/configurationdocument.php';
class dbuserparamsadmin extends ChisimbaObject
{
    public $uid;
    public $objConfig;
    public $objLanguage;
    protected $objUser;
    private $document;
    private $destination;
    private $lastError;

    public function init()
    {
        $this->objConfig=$this->getObject('altconfig','config');
        $this->objLanguage=$this->getObject('language','language');
        $this->objUser=$this->getObject('user','security');
        $this->uid=$this->objUser->userId();
    }
    public function setUserId($userId) { $this->uid=$userId; $this->document=null; $this->destination=null; }
    public function setUid($un = null, $id = null) { $this->setUserId($id ?? $this->objUser->getUserId($un)); }
    public function getLastWriteError() { return $this->lastError; }

    private function document()
    {
        if (!$this->objUser->isLoggedIn() || !is_scalar($this->uid)
            || !preg_match('/^[A-Za-z0-9_-]+$/D',(string)$this->uid)
            || ((string)$this->uid !== (string)$this->objUser->userId() && !$this->objUser->isAdmin())) {
            throw new RuntimeException('User preferences are not accessible.');
        }
        $path=rtrim($this->objConfig->getcontentBasePath(),'/').'/users/'.$this->uid.'/userconfig_properties.ini';
        if ($this->document === null || $path !== $this->destination) {
            $this->document=null; $this->destination=$path;
            $doc=new ChisimbaConfigurationDocument($path,'root',true,null,'INI');
            if (!$doc->root->getItem('section','Settings')) {
                $settings=$doc->root->createSection('Settings');
                foreach (array('Google API key','ICQ number','Yahoo ID','Skype ID','MSN ID') as $key) { $settings->createDirective($key,''); }
            }
            $this->document=$doc;
        }
        return $this->document;
    }
    public function readConfig($config = false, $property = 'PHPArray')
    {
        try { return $this->document()->root; }
        catch (RuntimeException $e) { $this->lastError=$e->getMessage(); return false; }
    }
    public function getRevision() { return $this->document()->revision(); }
    public function getValue($pname)
    {
        $root=$this->readConfig();
        if (!$root) { return false; }
        $item=$root->getItem('section','Settings')->getItem('directive',$pname);
        return $item ? $item->getContent() : null;
    }
    public function getItem($pname, $pvalue = null) { return $this->getValue($pname ?? $pvalue); }

    private function save($change, $revision = null)
    {
        $this->lastError=null;
        try {
            $doc=$this->document();
            if ($revision !== null && (!is_string($revision) || !hash_equals($doc->revision(),$revision))) {
                throw new RuntimeException('User preferences changed; reload before saving.');
            }
            $candidate=$doc->copy();
            $change($candidate->getItem('section','Settings'));
            $directory=dirname($this->destination);
            (new ChisimbaConfigurationFile())->ensureDirectory($directory,true);
            return $doc->save($candidate);
        } catch (RuntimeException $e) { $this->lastError=$e->getMessage(); return false; }
    }
    public function setItem($pname, $pvalue, $revision = null)
    {
        return $this->save(static function ($settings) use ($pname,$pvalue) {
            while ($old=$settings->getItem('directive',$pname)) { $old->removeItem(); }
            $settings->createDirective($pname,$pvalue);
        },$revision);
    }
    /** Merge, rather than replace, a user's preference set. */
    public function writeConfig($values, $property = 'IniFile')
    {
        if (!is_array($values) || strtolower($property) !== 'inifile') { return false; }
        return $this->save(static function ($settings) use ($values) {
            foreach ($values as $key=>$value) {
                while ($old=$settings->getItem('directive',(string)$key)) { $old->removeItem(); }
                $settings->createDirective((string)$key,$value);
            }
        });
    }
    public function writeProperties($mode, $userId, $pname, $ptag, $revision = null)
    {
        if (!in_array($mode,array('add','edit'),true) || (string)$userId !== (string)$this->uid) { return false; }
        return $this->setItem($pname,$ptag,$revision);
    }
    public function delete($pname, $revision = null)
    {
        return $this->save(static function ($settings) use ($pname) {
            while ($item=$settings->getItem('directive',$pname)) { $item->removeItem(); }
        },$revision);
    }
    /** Retained for callers creating their own INI; never overwrites an existing file. */
    public function createConfig($config_container, $settings, $iniPath, $iniName)
    {
        // This user service must not be used as an arbitrary-path writer.
        try { $this->document(); }
        catch (RuntimeException $e) { $this->lastError=$e->getMessage(); return false; }
        if (rtrim($iniPath,'/').'/'.$iniName !== $this->destination || file_exists($this->destination)) { return false; }
        return $this->writeConfig($settings);
    }
    public function checkIfSet($pname, $userId = null)
    {
        if ($userId !== null && (string)$userId !== (string)$this->uid) { return false; }
        return $this->getValue($pname) !== null && $this->getValue($pname) !== false;
    }
}
