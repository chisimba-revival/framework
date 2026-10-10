<?php
/** Existing language tables through the canonical connection; no private DSN.
 * @author Derek Keats <derek@dkeats.com>
 */
class translationstore extends ChisimbaObject
{
    private $db;
    private $available;
    private $generation = 0;
    public function init() { $this->db = $this->objEngine->getDbObj(); }
    public function revision() { return $this->generation; }
    private function checked($result)
    {
        if ($result === false || (class_exists('PEAR', false) && PEAR::isError($result))) {
            throw new RuntimeException('Language storage operation failed.');
        }
        return $result;
    }
    private function quote($value)
    {
        // MDB2 portability can turn an empty string into SQL NULL. Metadata and
        // page IDs distinguish those values, so preserve the explicit empty value.
        return $value === '' ? "''" : $this->checked($this->db->quote($value, 'text'));
    }
    private function identifier($value)
    {
        if (!is_string($value) || !preg_match('/^[a-z][a-z0-9_]{0,47}$/D', $value)) { throw new InvalidArgumentException('Invalid language identifier.'); }
        return $this->db->quoteIdentifier($value, true);
    }
    private function languageId($id)
    {
        if (!is_string($id) || !preg_match('/^[a-z]{2,3}(?:_[a-z0-9]{2,8})*$/D', $id) || strlen($id)>32) {
            throw new InvalidArgumentException('Invalid language code.');
        }
        return $id;
    }
    private function rows($sql)
    {
        $result = $this->checked($this->db->query($sql)); $rows = [];
        try { while ($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) { $rows[] = $this->checked($row); } }
        finally { $result->free(); }
        return $rows;
    }
    public function languages()
    {
        if ($this->available === null) {
            $available=[];
            foreach ($this->rows('SELECT id,name,meta,error_text,encoding FROM tbl_langs_avail') as $row) {
                $id=$this->languageId($row['id']);
                if (isset($available[$id])) { throw new RuntimeException('Duplicate language registration.'); }
                $available[$id]=$row;
            }
            $this->available=$available;
        }
        return $this->available;
    }
    private function table($id)
    {
        $id=$this->languageId($id);
        if ($id!=='en' && !isset($this->languages()[$id])) { throw new InvalidArgumentException('Language is not registered.'); }
        return $this->identifier('tbl_'.$id);
    }
    public function page($language,$page)
    {
        $table=$this->table($language);$column=$this->identifier($language);
        $where=$page===null?'pageID IS NULL':'pageID='.$this->quote($page);
        $result=[];
        foreach($this->rows('SELECT id,'.$column.' AS translation FROM '.$table.' WHERE '.$where) as $row) { $result[$row['id']]=$row['translation']; }
        return $result;
    }
    /** Update only submitted languages; never erase other translations first. */
    public function save($id,$page,array $translations)
    {
        return $this->locked(fn()=>$this->saveLocked($id,$page,$translations));
    }
    private function saveLocked($id,$page,array $translations)
    {
        if ($page!==null&&(!is_string($page)||strlen($page)>150)) { throw new InvalidArgumentException('Invalid language page.'); }
        if (!is_string($id)||$id===''||strlen($id)>255) { throw new InvalidArgumentException('Language item code is required.'); }
        $tables=[];
        foreach($translations as $language=>$text) {
            if (!is_string($text)||!preg_match('//u',$text)) { throw new InvalidArgumentException('Translation must be valid UTF-8 text.'); }
            $tables[$language]=$this->table($language);
            $status=$this->rows('SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='.$this->quote('tbl_'.$language));
            if (strcasecmp($status[0]['engine']??'', 'InnoDB')!==0) { throw new RuntimeException('Translation writes require transactional InnoDB tables.'); }
        }
        if (!$tables) { return true; }
        if (!empty($this->db->in_transaction)) { throw new RuntimeException('Language save requires its own transaction.'); }
        $this->checked($this->db->beginTransaction());
        try {
            $where='id='.$this->quote($id).' AND '.($page===null?'pageID IS NULL':'pageID='.$this->quote($page));
            foreach($tables as $language=>$table) {
                $column=$this->identifier($language);$text=$this->quote($translations[$language]);
                if ($this->rows('SELECT id FROM '.$table.' WHERE '.$where)) {
                    $this->checked($this->db->exec('UPDATE '.$table.' SET '.$column.'='.$text.' WHERE '.$where));
                } else {
                    $this->checked($this->db->exec('INSERT INTO '.$table.' (id,pageID,'.$column.') VALUES ('.$this->quote($id).','.$this->quote($page).','.$text.')'));
                }
            }
            $this->checked($this->db->commit());
        } catch(Throwable $e) { $this->db->rollback(); throw $e; }
        ++$this->generation;return true;
    }
    public function getPageNames()
    {
        $pages=[];
        foreach(array_unique(array_merge(['en'],array_keys($this->languages()))) as $id) {
            foreach($this->rows('SELECT DISTINCT pageID AS pageid FROM '.$this->table($id)) as $row) {$pages[]=$row['pageid'];}
        }
        return array_values(array_unique($pages));
    }

    /** Explicit language-administration operation; never called during rendering. */
    public function addLang(array $data)
    {
        return $this->locked(fn()=>$this->addLangLocked($data));
    }
    private function addLangLocked(array $data)
    {
        $id=$this->languageId($data['lang_id']??null);
        if (($data['table_name']??'tbl_'.$id)!=='tbl_'.$id) { throw new InvalidArgumentException('Unexpected language table.'); }
        if (isset($this->languages()[$id])) { throw new RuntimeException('Language already registered.'); }
        $metadata=$this->metadata($data);
        $this->checked($this->db->loadModule('Manager'));
        $tables=$this->checked($this->db->manager->listTables());
        if (!in_array('tbl_'.$id,$tables,true)) {
            $this->checked($this->db->manager->createTable('tbl_'.$id,[
                'id'=>['type'=>'text','length'=>255,'notnull'=>true],
                'pageID'=>['type'=>'text','length'=>150],
                $id=>['type'=>'text'],
            ],['charset'=>'utf8mb4','type'=>'InnoDB']));
        } else {
            // A retained table may be re-registered, but never alter/drop its data.
            $fields=$this->checked($this->db->manager->listTableFields('tbl_'.$id));
            foreach(['id','pageid',$id] as $field) {
                if (!in_array($field,array_map('strtolower',$fields),true)) { throw new RuntimeException('Retained language table is incompatible.'); }
            }
        }
        $this->checked($this->db->exec('INSERT INTO tbl_langs_avail (id,name,meta,error_text,encoding) VALUES ('.implode(',',array_map(fn($v)=>$this->quote($v),array_merge([$id],array_values($metadata)))).')'));
        $this->invalidate();return true;
    }
    private function metadata(array $data)
    {
        $result=[];
        foreach(['name','meta','error_text','encoding'] as $key) {
            $value=$data[$key]??($key==='encoding'?'UTF-8':'');
            if (!is_string($value)||!preg_match('//u',$value)||preg_match_all('/./us',$value)>($key==='encoding'?255:100)) { throw new InvalidArgumentException('Invalid language metadata.'); }
            $result[$key]=$value;
        }
        if ($result['name']==='' || strcasecmp($result['encoding'],'UTF-8')!==0) { throw new InvalidArgumentException('A name and UTF-8 encoding are required.'); }
        return $result;
    }
    public function updateLang(array $data)
    {
        return $this->locked(fn()=>$this->updateLangLocked($data));
    }
    private function updateLangLocked(array $data)
    {
        $id=$this->languageId($data['lang_id']??null);$existing=$this->languages()[$id]??null;
        if (!$existing) { throw new InvalidArgumentException('Language is not registered.'); }
        $metadata=$this->metadata(array_merge($existing,$data));$sets=[];
        foreach($metadata as $key=>$value) { $sets[]=$this->identifier($key).'='.$this->quote($value); }
        $this->checked($this->db->exec('UPDATE tbl_langs_avail SET '.implode(',',$sets).' WHERE id='.$this->quote($id)));
        $this->invalidate();return true;
    }
    /** By default unregister only; retained translations can be re-registered. */
    public function removeLang($id,$destroy=false)
    {
        return $this->locked(fn()=>$this->removeLangLocked($id,$destroy));
    }
    private function removeLangLocked($id,$destroy=false)
    {
        $id=$this->languageId($id);$table=$this->table($id);
        if ($id==='en') { throw new InvalidArgumentException('The English fallback cannot be removed.'); }
        if ($destroy) {
            $this->checked($this->db->loadModule('Manager'));
            $this->checked($this->db->manager->dropTable('tbl_'.$id));
        }
        $this->checked($this->db->exec('DELETE FROM tbl_langs_avail WHERE id='.$this->quote($id)));
        $this->invalidate();return true;
    }
    /** Current supported MariaDB/MySQL adapter: serialize all native language writes.
     * The database migration must supply an equivalent lock on its new adapter.
     * Catalogue registration still has its own installation/maintenance boundary.
     */
    private function locked($operation)
    {
        if (!empty($this->db->in_transaction)) { throw new RuntimeException('Language writes require their own transaction boundary.'); }
        if(!in_array($this->db->phptype,['mysql','mysqli'],true))throw new RuntimeException('Language writes require a validated database adapter.');
        $name="CONCAT('chisimba-lang:',LEFT(SHA2(DATABASE(),256),40))";
        $rows=$this->rows('SELECT GET_LOCK('.$name.',5) AS acquired');
        if((int)($rows[0]['acquired']??0)!==1)throw new RuntimeException('Language data is busy; retry the operation.');
        // Another worker may have changed registration while this request waited.
        $this->available=null;
        try{return $operation();}
        finally {
            $released=$this->rows('SELECT RELEASE_LOCK('.$name.') AS released');
            if((int)($released[0]['released']??0)!==1)throw new RuntimeException('Language lock release failed.');
        }
    }
    private function invalidate() { $this->available=null; ++$this->generation; }
}
