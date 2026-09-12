<?php
/** Persistence boundary for shared classification. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class classificationstore extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorCallback')
    {
        parent::init('tbl_classification_vocabularies', $pearDb, $errorCallback);
    }

    /** Quote values through the active framework database driver. */
    private function quote($value)
    {
        // MDB2 portability may quote an empty string as NULL; these keys use empty strings.
        if ((string)$value === '') return "''";
        $db = $this->objEngine->getDbObj();
        return method_exists($db, 'quoteSmart') ? $db->quoteSmart((string)$value) : $db->quote((string)$value);
    }

    /** Internal SQL errors must abort the operation, never look like an empty result. */
    private function execute($sql)
    {
        $result = $this->query($sql);
        if ($result === false) throw new RuntimeException('classification_storage_failed');
        return $result;
    }

    public function vocabulary($id)
    {
        $rows = $this->execute('SELECT * FROM tbl_classification_vocabularies WHERE id='.$this->quote($id));
        return $rows[0] ?? null;
    }

    private $schemaVerified = false;

    /** Fail closed if installation omitted the constraints needed for transactional identity. */
    private function verifySchema()
    {
        if ($this->schemaVerified) return;
        foreach (['vocabularies','terms','links'] as $suffix) {
            $table = 'tbl_classification_'.$suffix;
            $status = $this->execute('SHOW TABLE STATUS WHERE Name='.$this->quote($table));
            $status = array_change_key_case($status[0] ?? [], CASE_LOWER);
            $indexes = $this->execute('SHOW INDEX FROM '.$table);
            $unique = false;
            foreach ($indexes as $index) {
                $index = array_change_key_case($index, CASE_LOWER);
                if ($index['key_name']==='classification_id_unique' && !$index['non_unique']) $unique=true;
            }
            if (($status['engine'] ?? '')!=='InnoDB' || !$unique) {
                throw new RuntimeException('classification_setup_required');
            }
        }
        $this->schemaVerified = true;
    }

    /** Serialise all writers in a vocabulary; savepoints compose with a caller transaction. */
    public function locked(array $vocabulary, callable $operation)
    {
        $this->verifySchema();
        $state = $this->execute('SELECT @@in_transaction AS active');
        $nested = !empty($state[0]['active']);
        $savepoint = 'classification_'.bin2hex(random_bytes(6));
        $this->execute($nested ? 'SAVEPOINT '.$savepoint : 'START TRANSACTION');
        try {
            $this->execute('INSERT INTO tbl_classification_vocabularies (id,scope_type,scope_id,kind) VALUES ('
                .implode(',', array_map(fn($key) => $this->quote($vocabulary[$key]), ['id','scope_type','scope_id','kind']))
                .') ON DUPLICATE KEY UPDATE id=id');
            $this->execute('SELECT id FROM tbl_classification_vocabularies WHERE id='.$this->quote($vocabulary['id']).' FOR UPDATE');
            $result = $operation();
            $this->execute($nested ? 'RELEASE SAVEPOINT '.$savepoint : 'COMMIT');
            return $result;
        } catch (Throwable $error) {
            $this->execute($nested ? 'ROLLBACK TO SAVEPOINT '.$savepoint : 'ROLLBACK');
            throw $error;
        }
    }

    public function terms($vocabulary)
    {
        return $this->normaliseTerms($this->execute('SELECT * FROM tbl_classification_terms WHERE vocabulary_id='.$this->quote($vocabulary).' ORDER BY name,id'));
    }

    /** MDB2 may return empty strings as null; parentless terms have one public representation. */
    private function normaliseTerms(array $terms)
    {
        foreach ($terms as &$term) $term['parent_id'] = (string)($term['parent_id'] ?? '');
        return $terms;
    }

    public function saveTerm(array $term)
    {
        $columns = ['id','vocabulary_id','name','slug','parent_id'];
        $this->execute('INSERT INTO tbl_classification_terms ('.implode(',', $columns).') VALUES ('
            .implode(',', array_map(fn($key) => $this->quote($term[$key]), $columns))
            .') ON DUPLICATE KEY UPDATE name=VALUES(name),slug=VALUES(slug),parent_id=VALUES(parent_id)');
    }

    /** Terms with children or assignments cannot be silently removed. */
    public function removeTerm($id)
    {
        $children = $this->execute('SELECT id FROM tbl_classification_terms WHERE parent_id='.$this->quote($id).' LIMIT 1');
        $links = $this->execute('SELECT id FROM tbl_classification_links WHERE term_id='.$this->quote($id).' LIMIT 1');
        if ($children || $links) throw new DomainException('classification_term_in_use');
        $this->execute('DELETE FROM tbl_classification_terms WHERE id='.$this->quote($id));
    }

    public function replaceLinks($vocabulary, $module, $item, array $terms)
    {
        $this->execute('DELETE FROM tbl_classification_links WHERE vocabulary_id='.$this->quote($vocabulary)
            .' AND module_id='.$this->quote($module).' AND item_id='.$this->quote($item));
        foreach ($terms as $term) {
            $id = hash('sha256', json_encode([$vocabulary,$module,$item,$term]));
            $this->execute('INSERT INTO tbl_classification_links (id,vocabulary_id,term_id,module_id,item_id) VALUES ('
                .implode(',', array_map(fn($v) => $this->quote($v), [substr($id,0,32),$vocabulary,$term,$module,$item])).')');
        }
    }

    public function itemTerms($vocabulary, $module, $item)
    {
        return $this->normaliseTerms($this->execute('SELECT t.* FROM tbl_classification_terms t INNER JOIN tbl_classification_links l ON l.term_id=t.id'
            .' WHERE l.vocabulary_id='.$this->quote($vocabulary).' AND l.module_id='.$this->quote($module)
            .' AND l.item_id='.$this->quote($item).' ORDER BY t.name,t.id'));
    }

    /** Candidates only: the service must check each record with its owning module. */
    public function candidates($vocabulary, $term, $after, $limit)
    {
        return $this->execute('SELECT module_id,item_id,id FROM tbl_classification_links WHERE vocabulary_id='.$this->quote($vocabulary)
            .' AND term_id='.$this->quote($term).' AND id>'.$this->quote($after).' ORDER BY id LIMIT '.(int)$limit);
    }
}
