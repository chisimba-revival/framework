<?php
/** Persistence boundary for shared classification. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class classificationstore extends dbTable
{
    private $schemaVerified = false;

    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorCallback')
    {
        parent::init('tbl_classification_vocabularies', $pearDb, $errorCallback);
    }

    public function vocabulary($id)
    {
        return $this->selectExactPrepared('SELECT * FROM tbl_classification_vocabularies WHERE id=?', [$id])[0] ?? null;
    }

    /** Fail closed if installation omitted transactional storage or unique IDs. */
    private function verifySchema()
    {
        if ($this->schemaVerified) return;
        foreach (['vocabularies','terms','links'] as $suffix) {
            $table = 'tbl_classification_'.$suffix;
            $status = $this->selectExactPrepared('SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?', [$table]);
            $indexes = $this->selectExactPrepared('SHOW INDEX FROM '.$table);
            $uniqueColumns = [];
            foreach ($indexes as $index) {
                $index = array_change_key_case($index, CASE_LOWER);
                if ($index['key_name']==='classification_id_unique' && !$index['non_unique']) {
                    $uniqueColumns[(int)$index['seq_in_index']] = strtolower($index['column_name']);
                }
            }
            if (strcasecmp($status[0]['engine'] ?? '', 'InnoDB') !== 0 || $uniqueColumns !== [1=>'id']) {
                throw new RuntimeException('classification_setup_required');
            }
        }
        $this->schemaVerified = true;
    }

    /** Serialise a vocabulary's writers while retaining the caller's transaction. */
    public function locked(array $vocabulary, callable $operation)
    {
        $this->verifySchema();
        return $this->transactionWithSavepoint(function () use ($vocabulary, $operation) {
            $this->executeExactPrepared('INSERT INTO tbl_classification_vocabularies (id,scope_type,scope_id,kind) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE id=id',
                [$vocabulary['id'],$vocabulary['scope_type'],$vocabulary['scope_id'],$vocabulary['kind']]);
            $this->selectExactPrepared('SELECT id FROM tbl_classification_vocabularies WHERE id=? FOR UPDATE', [$vocabulary['id']]);
            return $operation();
        });
    }

    public function terms($vocabulary)
    {
        return $this->normaliseTerms($this->selectExactPrepared('SELECT * FROM tbl_classification_terms WHERE vocabulary_id=? ORDER BY name,id', [$vocabulary]));
    }

    /** Historical NULL and empty parent IDs have one domain representation. */
    private function normaliseTerms(array $terms)
    {
        foreach ($terms as &$term) $term['parent_id'] = (string)($term['parent_id'] ?? '');
        return $terms;
    }

    public function saveTerm(array $term)
    {
        $this->executeExactPrepared('INSERT INTO tbl_classification_terms (id,vocabulary_id,name,slug,parent_id) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),slug=VALUES(slug),parent_id=VALUES(parent_id)',
            [$term['id'],$term['vocabulary_id'],$term['name'],$term['slug'],$term['parent_id']]);
    }

    /** Terms with children or assignments cannot be silently removed. */
    public function removeTerm($id)
    {
        $children = $this->selectExactPrepared('SELECT id FROM tbl_classification_terms WHERE parent_id=? LIMIT 1', [$id]);
        $links = $this->selectExactPrepared('SELECT id FROM tbl_classification_links WHERE term_id=? LIMIT 1', [$id]);
        if ($children || $links) throw new DomainException('classification_term_in_use');
        $this->executeExactPrepared('DELETE FROM tbl_classification_terms WHERE id=?', [$id]);
    }

    public function replaceLinks($vocabulary, $module, $item, array $terms)
    {
        $this->executeExactPrepared('DELETE FROM tbl_classification_links WHERE vocabulary_id=? AND module_id=? AND item_id=?', [$vocabulary,$module,$item]);
        foreach ($terms as $term) {
            $id = hash('sha256', json_encode([$vocabulary,$module,$item,$term]));
            $this->executeExactPrepared('INSERT INTO tbl_classification_links (id,vocabulary_id,term_id,module_id,item_id) VALUES (?,?,?,?,?)', [substr($id,0,32),$vocabulary,$term,$module,$item]);
        }
    }

    public function itemTerms($vocabulary, $module, $item)
    {
        return $this->normaliseTerms($this->selectExactPrepared('SELECT t.* FROM tbl_classification_terms t INNER JOIN tbl_classification_links l ON l.term_id=t.id WHERE l.vocabulary_id=? AND l.module_id=? AND l.item_id=? ORDER BY t.name,t.id', [$vocabulary,$module,$item]));
    }

    /** Candidates only: the service checks each record with its owning module. */
    public function candidates($vocabulary, $term, $after, $limit)
    {
        $limit = (int)$limit;
        if ($limit < 0) throw new InvalidArgumentException('Invalid classification limit.');
        return $this->selectExactPrepared('SELECT module_id,item_id,id FROM tbl_classification_links WHERE vocabulary_id=? AND term_id=? AND id>? ORDER BY id LIMIT '.$limit, [$vocabulary,$term,$after]);
    }
}
