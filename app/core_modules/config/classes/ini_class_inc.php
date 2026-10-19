<?php
/** General configuration helper, backed by native documents rather than PEAR.
 * Retains the named helper operations; executable PHP configuration is unsupported.
 * GPL-2.0-or-later. Original author: Prince Mbekwa. Migration: Derek Keats.
 */
require_once __DIR__ . '/configurationdocument.php';
class ini extends ChisimbaObject
{
    private $document;
    private $destination;
    private $format;
    private $lastError;
    public $objConfig;
    public function init() { $this->objConfig=$this->getObject('altconfig','config'); }
    public function getLastWriteError() { return $this->lastError; }
    private function open($path, $file, $format = 'IniFile', $allowMissing = false, $rootName = 'Settings')
    {
        $format=strtolower($format);
        if (!in_array($format,array('inifile','xml'),true)) { throw new RuntimeException('Unsupported configuration format.'); }
        $destination=$file !== null ? ($path ? rtrim($path,'/').'/' : '') . $file : $this->destination;
        if (!$destination) { throw new RuntimeException('Configuration destination is required.'); }
        if ($this->document === null || $destination !== $this->destination || $format !== $this->format) {
            $this->document=null; $this->destination=$destination; $this->format=$format;
            $this->document=new ChisimbaConfigurationDocument($destination,$format==='xml'?$rootName:'root',$allowMissing,null,$format==='xml'?'XML':'INI');
        }
        return $this->document;
    }
    public function createConfig($config_container = false, $settings = array(), $iniPath = false, $iniName = null)
    {
        try {
            $doc=$this->open($iniPath,$iniName,'IniFile',true);
            if (file_exists($this->destination)) { throw new RuntimeException('Configuration already exists.'); }
            return $doc->save($doc->fromArray(array($config_container ?: 'Settings'=>$settings)));
        } catch (RuntimeException $e) { $this->lastError=$e->getMessage(); return false; }
    }
    public function readConfig($config = false, $property = 'PHPArray', $Path = false, $FileName = null)
    {
        try { return $this->open($Path,$FileName,strtolower($property)==='phparray'?'IniFile':$property)->root; }
        catch (RuntimeException $e) { $this->lastError=$e->getMessage(); return false; }
    }
    public function getAll() { return $this->document ? $this->document->root->toArray() : false; }
    public function writeConfig($config_container = false, $values = array(), $property = 'IniFile', $Path = false, $FileName = null)
    {
        try {
            $doc=$this->open($Path,$FileName,$property,true,$config_container ?: 'Settings');
            $values=$values['root'] ?? $values;
            if (strtolower($property)==='inifile' && $config_container) { $values=array($config_container=>$values); }
            return $doc->save($doc->fromArray($values));
        } catch (RuntimeException $e) { $this->lastError=$e->getMessage(); return false; }
    }
    public function getItem($pname, $pvalue = null, $Directive = 'Settings')
    {
        if (!$this->document) { return false; }
        $section=$this->document->root->getItem('section',$Directive);
        $item=$section ? $section->getItem('directive',$pname ?? $pvalue) : false;
        return $item ? $item->getContent() : false;
    }
    public function setItem($pname, $pvalue, $Directive = 'Settings')
    {
        try {
            if (!$this->document) { return false; }
            $root=$this->document->copy(); $section=$root->getItem('section',$Directive);
            if (!$section) { return false; }
            $item=$section->getItem('directive',$pname);
            if ($item) { $item->setContent($pvalue); } else { $section->createDirective($pname,$pvalue); }
            return $this->document->save($root);
        } catch (RuntimeException $e) { $this->lastError=$e->getMessage(); return false; }
    }
    public function delete($values, $index)
    {
        try {
            if (!$this->document) { return false; }
            $root=$this->document->copy(); $section=$root->getItem('section','Settings');
            if (!$section) { return false; }
            while ($item=$section->getItem('directive',$index)) { $item->removeItem(); }
            return $this->document->save($root);
        } catch (RuntimeException $e) { $this->lastError=$e->getMessage(); return false; }
    }
    public function createAdmConfig($servarray)
    {
        try {
            $directory=rtrim($this->objConfig->getcontentBasePath(),'/').'/adm';
            (new ChisimbaConfigurationFile())->ensureDirectory($directory);
            $doc=new ChisimbaConfigurationDocument($directory.'/adm.xml','adm',true);
            $root=$doc->copy(); $adm=$root->getItem('section','adm');
            while ($old=$adm->getItem('section',$servarray['name'])) { $old->removeItem(); }
            $server=$adm->createSection($servarray['name']);
            foreach (array('servername'=>$servarray['name'],'serverapiurl'=>$servarray['url'],'serveremail'=>$servarray['email'],'regtime'=>date('r')) as $key=>$value) { $server->createDirective($key,$value); }
            return $doc->save($root);
        } catch (RuntimeException $e) { $this->lastError=$e->getMessage(); return false; }
    }
}
