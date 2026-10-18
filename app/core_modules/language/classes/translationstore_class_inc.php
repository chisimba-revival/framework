<?php
/** Existing language tables through the canonical connection; no private DSN.
 * @author Derek Keats <derek@dkeats.com>
 */
class translationstore extends dbTable
{
    private $schema;
    private $available;
    private $generation = 0;
    public function init($tableName = null, $pearDb = null, $errorCallback = "globalPearErrorCallback")
    {
        parent::init('tbl_langs_avail', $pearDb, $errorCallback);
        require_once __DIR__ . '/../../../classes/core/chisimbadatabaseschema.php';
        $this->schema = new ChisimbaDatabaseSchema($this);
    }
    public function revision() { return $this->generation; }
    private function identifier($value)
    {
        if (!is_string($value) || !preg_match('/^[a-z][a-z0-9_]{0,47}$/D', $value)) { throw new InvalidArgumentException('Invalid language identifier.'); }
        return ChisimbaDatabaseSchema::identifier($value);
    }
    private function languageId($id)
    {
        if (!is_string($id) || !preg_match('/^[a-z]{2,3}(?:_[a-z0-9]{2,8})*$/D', $id) || strlen($id)>32) {
            throw new InvalidArgumentException('Invalid language code.');
        }
        return $id;
    }
    private function rows($sql, array $parameters = [])
    {
        return $this->selectExactPrepared($sql, $parameters);
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
        $where=$page===null?'pageID IS NULL':'pageID=?';
        $result=[];
        foreach($this->rows('SELECT id,'.$column.' AS translation FROM '.$table.' WHERE '.$where, $page===null?[]:[$page]) as $row) { $result[$row['id']]=$row['translation']; }
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
            $this->schema->requireTransactional('tbl_'.$language);
        }
        if (!$tables) { return true; }
        $this->transaction(function () use ($id, $page, $translations, $tables) {
            $where='id=? AND '.($page===null?'pageID IS NULL':'pageID=?');
            $parameters=$page===null?[$id]:[$id,$page];
            foreach($tables as $language=>$table) {
                $column=$this->identifier($language);
                if ($this->rows('SELECT id FROM '.$table.' WHERE '.$where, $parameters)) {
                    $this->executeExactPrepared('UPDATE '.$table.' SET '.$column.'=? WHERE '.$where, array_merge([$translations[$language]],$parameters));
                } else {
                    $this->executeExactPrepared('INSERT INTO '.$table.' (id,pageID,'.$column.') VALUES (?,?,?)', [$id,$page,$translations[$language]]);
                }
            }
        });
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
        if (!$this->schema->tableExists('tbl_'.$id)) {
            $this->schema->createTextTable('tbl_'.$id,[
                'id'=>['length'=>255,'notnull'=>true],
                'pageID'=>['length'=>150],
                $id=>[],
            ]);
        } else {
            // Re-registration retains the existing table and all its text.
            $fields=array_map('strtolower',$this->schema->columns('tbl_'.$id));
            foreach(['id','pageid',$id] as $field) {
                if (!in_array($field,$fields,true)) { throw new RuntimeException('Retained language table is incompatible.'); }
            }
        }
        $this->executeExactPrepared('INSERT INTO tbl_langs_avail (id,name,meta,error_text,encoding) VALUES (?,?,?,?,?)', array_merge([$id],array_values($metadata)));
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
        foreach($metadata as $key=>$value) { $sets[]=$this->identifier($key).'=?'; }
        $this->executeExactPrepared('UPDATE tbl_langs_avail SET '.implode(',',$sets).' WHERE id=?', array_merge(array_values($metadata),[$id]));
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
            $this->schema->dropTable('tbl_'.$id);
        }
        $this->executeExactPrepared('DELETE FROM tbl_langs_avail WHERE id=?', [$id]);
        $this->invalidate();return true;
    }
    /** Serialise native language writes, including inserts into tables without keys. */
    private function locked($operation)
    {
        if ($this->hasActiveTransaction()) { throw new RuntimeException('Language writes require their own transaction boundary.'); }
        return $this->withDatabaseLock('chisimba-lang:', function () use ($operation) {
            // Another worker may have changed registration while this request waited.
            $this->available=null;
            return $operation();
        });
    }
    private function invalidate() { $this->available=null; ++$this->generation; }
}
