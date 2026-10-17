<?php
/** Atomic, revision-checked course outcomes and plugin persistence through dbTable. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class coursewizardstore extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorCallback') { parent::init('tbl_context'); }
    public function snapshot($code, $step)
    {
        $course = $this->selectPrepared('SELECT contextcode,title,goals FROM tbl_context WHERE contextcode = ?', [$code]);
        if (!$course) throw new RuntimeException('missing');
        $rows = $step === 'outcomes'
            ? $this->selectPrepared('SELECT id,learningoutcome FROM tbl_context_learneroutcomes WHERE contextcode = ? ORDER BY id', [$code])
            : $this->selectPrepared('SELECT id,moduleid FROM tbl_contextmodules WHERE contextcode = ? ORDER BY moduleid,id', [$code]);
        return ['course' => $course[0], 'rows' => $rows, 'revision' => hash('sha256', json_encode([$step, $code, $rows, $step === 'outcomes' ? $course[0]['goals'] : null], JSON_THROW_ON_ERROR))];
    }
    public function save($code, $step, $revision, array $changes, array $allowed = [])
    {
        require_once __DIR__ . '/../../../classes/core/chisimbadatabaseschema.php';
        $schema = new ChisimbaDatabaseSchema($this);
        $schema->requireTransactional('tbl_context');
        $schema->requireTransactional($step === 'plugins' ? 'tbl_contextmodules' : 'tbl_context_learneroutcomes');
        return $this->transactionWithSavepoint(function () use ($code, $step, $revision, $changes, $allowed) {
            $locked = $this->selectPrepared('SELECT contextcode FROM tbl_context WHERE contextcode = ? FOR UPDATE', [$code]);
            if (!$locked) throw new RuntimeException('missing');
            if (!$this->getObject('contextsettingsaccess', 'context')->canEdit($code)) throw new RuntimeException('denied');
            $state = $this->snapshot($code, $step);
            if (!is_string($revision) || !hash_equals($state['revision'], $revision)) throw new RuntimeException('stale');
            if ($step === 'plugins') {
                foreach ($changes as $id) if (!is_string($id) || !in_array($id, $allowed, true)) throw new RuntimeException('invalid');
                $desired = array_values(array_unique($changes));
                $existing = array_column($state['rows'], 'moduleid');
                // Preserve unoffered legacy selections; this form cannot silently delete them.
                foreach ($state['rows'] as $row) {
                    if (in_array($row['moduleid'], $allowed, true) && !in_array($row['moduleid'], $desired, true)) {
                        $this->executePrepared('DELETE FROM tbl_contextmodules WHERE id = ? AND contextcode = ?', [$row['id'], $code]);
                    }
                }
                $repo = $this->getObject('dbcontextmodules', 'context');
                foreach ($desired as $id) if (!in_array($id, $existing, true) && !$repo->addModule($code, $id)) throw new RuntimeException('save');
            } else {
                $known = array_column($state['rows'], null, 'id');
                $updates = $changes['existing'] ?? []; $removed = $changes['remove'] ?? []; $new = $changes['new'] ?? [];
                if (!is_array($updates) || !is_array($removed) || !is_array($new) || count($new) > 20) throw new RuntimeException('invalid');
                if (array_diff(array_keys($updates), array_keys($known)) || array_diff(array_keys($known), array_keys($updates))) throw new RuntimeException('invalid');
                foreach ($removed as $id) if (!is_string($id) || !isset($known[$id])) throw new RuntimeException('invalid');
                foreach (array_merge(array_values($updates), $new) as $text) if (!is_string($text) || strlen($text) > 100000) throw new RuntimeException('invalid');
                $sanitiser = $this->getObject('richtextsanitizer', 'utilities');
                foreach ($updates as $id => $text) {
                    if (in_array($id, $removed, true)) {
                        // Linked outcomes must be unlinked by their owning module first.
                        $links = $this->selectPrepared('SELECT id FROM tbl_context_learneroutcomes_dependencies WHERE learningoutcomeid = ? LIMIT 1', [$id]);
                        require_once __DIR__ . '/../../../classes/core/chisimbadatabaseschema.php';
                        if ((new ChisimbaDatabaseSchema($this))->tableExists('tbl_assignment_learningoutcomes')) {
                            $links = array_merge($links, $this->selectPrepared('SELECT id FROM tbl_assignment_learningoutcomes WHERE learningoutcome_id = ? LIMIT 1', [$id]));
                        }
                        if ($links) throw new RuntimeException('linked');
                        $this->executePrepared('DELETE FROM tbl_context_learneroutcomes WHERE id = ? AND contextcode = ?', [$id, $code]);
                    } else {
                        $clean = $sanitiser->cleanHtml($text);
                        if (trim(strip_tags($clean)) === '' && !str_contains($clean, '<img ')) throw new RuntimeException('invalid');
                        $this->executePrepared('UPDATE tbl_context_learneroutcomes SET learningoutcome = ? WHERE id = ? AND contextcode = ?', [$clean, $id, $code]);
                    }
                }
                $repo = $this->getObject('dbcontext_learneroutcomes', 'context');
                foreach ($new as $text) {
                    $text = $sanitiser->cleanHtml($text);
                    if ((trim(strip_tags($text)) !== '' || str_contains($text, '<img ')) && !$repo->insertSingle($code, $text)) throw new RuntimeException('save');
                }
            }
            return true;
        });
    }
}
