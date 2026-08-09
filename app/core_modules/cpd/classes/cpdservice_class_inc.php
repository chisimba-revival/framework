<?php
/**
 * Canonical CPD service: schemes, recognition and append-only manual ledger.
 *
 * @author  Derek Keats
 * @package cpd
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class cpdservice extends ChisimbaObject
{
    private $schemes;
    private $categories;
    private $recognitions;
    private $ledger;

    public function init()
    {
        $this->schemes = $this->getObject('cpdschemes', 'cpd');
        $this->categories = $this->getObject('cpdcategories', 'cpd');
        $this->recognitions = $this->getObject('cpdrecognitions', 'cpd');
        $this->ledger = $this->getObject('cpdledger', 'cpd');
    }

    public function createScheme(array $input)
    {
        $key = $this->key($input['schemeKey'] ?? '');
        $name = trim((string) ($input['name'] ?? ''));
        $actor = $this->userId($input['actorUserId'] ?? '');
        if ($key === '' || $name === '' || $actor === '') { return $this->result(false, 'invalid_scheme'); }
        $existing = $this->schemes->getRow('scheme_key', $key);
        if (is_array($existing) && !empty($existing['id'])) { return $this->result(true, 'scheme_exists', $existing['id']); }
        $now = date('Y-m-d H:i:s');
        $id = $this->newId();
        $ok = $this->schemes->insert(array('id' => $id, 'scheme_key' => $key, 'name' => $name,
            'description' => trim((string) ($input['description'] ?? '')), 'status' => 'active',
            'created_by' => $actor, 'date_created' => $now, 'date_updated' => $now));
        return $ok === false ? $this->result(false, 'scheme_create_failed') : $this->result(true, 'scheme_created', $id);
    }

    public function createCategory(array $input)
    {
        $schemeId = $this->id($input['schemeId'] ?? '');
        $key = $this->key($input['categoryKey'] ?? '');
        $name = trim((string) ($input['name'] ?? ''));
        $actor = $this->userId($input['actorUserId'] ?? '');
        if ($schemeId === '' || $key === '' || $name === '' || $actor === '' || !$this->row($this->schemes, $schemeId)) {
            return $this->result(false, 'invalid_category');
        }
        $rows = $this->categories->getAll("WHERE scheme_id = '" . $schemeId . "' AND category_key = '" . $key . "'");
        if (!empty($rows[0]['id'])) { return $this->result(true, 'category_exists', $rows[0]['id']); }
        $now = date('Y-m-d H:i:s'); $id = $this->newId();
        $ok = $this->categories->insert(array('id' => $id, 'scheme_id' => $schemeId, 'category_key' => $key,
            'name' => $name, 'description' => trim((string) ($input['description'] ?? '')), 'status' => 'active',
            'created_by' => $actor, 'date_created' => $now, 'date_updated' => $now));
        return $ok === false ? $this->result(false, 'category_create_failed') : $this->result(true, 'category_created', $id);
    }

    public function recogniseContext(array $input)
    {
        $context = $this->contextCode($input['contextCode'] ?? '');
        $schemeId = $this->id($input['schemeId'] ?? '');
        $categoryId = $this->id($input['categoryId'] ?? '');
        $actor = $this->userId($input['actorUserId'] ?? '');
        $reason = trim((string) ($input['reason'] ?? ''));
        $points = $this->points($input['points'] ?? null, false);
        if ($context === '' || $schemeId === '' || $categoryId === '' || $actor === '' || $reason === '' || $points === null) {
            return $this->result(false, 'invalid_recognition');
        }
        $category = $this->row($this->categories, $categoryId);
        if (!$this->row($this->schemes, $schemeId) || !$category || $category['scheme_id'] !== $schemeId) {
            return $this->result(false, 'recognition_reference_mismatch');
        }
        $rows = $this->recognitions->getAll("WHERE context_code = '" . $context . "' AND scheme_id = '" . $schemeId . "' ORDER BY version_number DESC LIMIT 1");
        $version = empty($rows) ? 1 : ((int) $rows[0]['version_number'] + 1);
        $id = $this->newId();
        $ok = $this->recognitions->insert(array('id' => $id, 'context_code' => $context, 'scheme_id' => $schemeId,
            'category_id' => $categoryId, 'version_number' => $version, 'points' => $points,
            'valid_from' => $this->dateOrNull($input['validFrom'] ?? null), 'valid_until' => $this->dateOrNull($input['validUntil'] ?? null),
            'status' => 'active', 'reason' => $reason, 'created_by' => $actor, 'date_created' => date('Y-m-d H:i:s')));
        return $ok === false ? $this->result(false, 'recognition_create_failed') : $this->result(true, 'context_recognised', $id, array('version' => $version));
    }

    public function allocateManual(array $input)
    {
        return $this->appendTransaction('allocation', $input, null);
    }

    public function correct(array $input)
    {
        $related = $this->id($input['relatedTransactionId'] ?? '');
        if ($related === '' || !$this->row($this->ledger, $related)) { return $this->result(false, 'invalid_related_transaction'); }
        return $this->appendTransaction('correction', $input, $related);
    }

    public function reverse(array $input)
    {
        $related = $this->id($input['relatedTransactionId'] ?? '');
        $original = $related === '' ? null : $this->row($this->ledger, $related);
        if (!$original) { return $this->result(false, 'invalid_related_transaction'); }
        $input['identityUserId'] = $original['identity_user_id'];
        $input['contextCode'] = $original['context_code'];
        $input['schemeId'] = $original['scheme_id'];
        $input['categoryId'] = $original['category_id'];
        $input['recognitionId'] = $original['recognition_id'];
        $input['points'] = -1 * (float) $original['points_delta'];
        $input['completionBasis'] = $original['completion_basis'];
        return $this->appendTransaction('reversal', $input, $related);
    }

    public function historyForIdentity($userId, $schemeId = null)
    {
        $user = $this->userId($userId); if ($user === '') { return array(); }
        $where = "WHERE identity_user_id = '" . $user . "'";
        $scheme = $this->id($schemeId); if ($scheme !== '') { $where .= " AND scheme_id = '" . $scheme . "'"; }
        return $this->ledger->getAll($where . ' ORDER BY effective_date ASC, date_created ASC');
    }

    public function listSchemes()
    {
        return $this->schemes->getAll("WHERE status = 'active' ORDER BY name ASC");
    }

    public function listCategories($schemeId)
    {
        $scheme = $this->id($schemeId);
        if ($scheme === '') { return array(); }
        return $this->categories->getAll(
            "WHERE scheme_id = '" . $scheme . "' AND status = 'active' ORDER BY name ASC"
        );
    }

    public function currentRecognition($contextCode, $schemeId = null)
    {
        $context = $this->contextCode($contextCode);
        if ($context === '') { return null; }
        $where = "WHERE context_code = '" . $context . "'";
        $scheme = $this->id($schemeId);
        if ($scheme !== '') { $where .= " AND scheme_id = '" . $scheme . "'"; }
        $rows = $this->recognitions->getAll(
            $where . " AND status = 'active' ORDER BY version_number DESC, date_created DESC"
        );
        return empty($rows) ? null : $rows[0];
    }

    public function historyForContext($contextCode)
    {
        $context = $this->contextCode($contextCode);
        if ($context === '') { return array(); }
        return $this->ledger->getAll(
            "WHERE context_code = '" . $context . "' ORDER BY effective_date DESC, date_created DESC"
        );
    }

    private function appendTransaction($type, array $input, $related)
    {
        $user = $this->userId($input['identityUserId'] ?? ''); $context = $this->contextCode($input['contextCode'] ?? '');
        $scheme = $this->id($input['schemeId'] ?? ''); $category = $this->id($input['categoryId'] ?? '');
        $recognition = $this->id($input['recognitionId'] ?? ''); $actor = $this->userId($input['actorUserId'] ?? '');
        $reason = trim((string) ($input['reason'] ?? '')); $basis = trim((string) ($input['completionBasis'] ?? ''));
        $key = trim((string) ($input['idempotencyKey'] ?? '')); $points = $this->points($input['points'] ?? null, true);
        $effective = $this->dateOrNull($input['effectiveDate'] ?? date('Y-m-d'));
        if ($user === '' || $context === '' || $scheme === '' || $category === '' || $actor === '' || $reason === '' ||
            $basis === '' || $key === '' || strlen($key) > 191 || $points === null || $points == 0 || $effective === null) {
            return $this->result(false, 'invalid_transaction');
        }
        $existing = $this->ledger->getRow('idempotency_key', $key);
        if (is_array($existing) && !empty($existing['id'])) { return $this->result(true, 'transaction_exists', $existing['id']); }
        $cat = $this->row($this->categories, $category);
        if (!$this->row($this->schemes, $scheme) || !$cat || $cat['scheme_id'] !== $scheme) {
            return $this->result(false, 'transaction_reference_mismatch');
        }
        if ($recognition !== '' && !$this->row($this->recognitions, $recognition)) { return $this->result(false, 'invalid_recognition_reference'); }
        $id = $this->newId();
        $ok = $this->ledger->insert(array('id' => $id, 'identity_user_id' => $user, 'context_code' => $context,
            'scheme_id' => $scheme, 'category_id' => $category, 'recognition_id' => $recognition === '' ? null : $recognition,
            'transaction_type' => $type, 'points_delta' => $points, 'related_transaction_id' => $related,
            'idempotency_key' => $key, 'completion_basis' => $basis, 'reason' => $reason,
            'effective_date' => $effective, 'allocated_by' => $actor, 'date_created' => date('Y-m-d H:i:s')));
        return $ok === false ? $this->result(false, 'transaction_failed') : $this->result(true, 'transaction_recorded', $id);
    }

    private function row($table, $id) { $row = $table->getRow('id', $id); return is_array($row) && !empty($row['id']) ? $row : null; }
    private function newId() { return bin2hex(random_bytes(16)); }
    private function id($value) { $v = strtolower(trim((string) $value)); return preg_match('/^[a-f0-9]{32}$/', $v) ? $v : ''; }
    private function userId($value) { $v = trim((string) $value); return preg_match('/^[A-Za-z0-9._:@-]{1,255}$/', $v) ? $v : ''; }
    private function key($value) { $v = strtolower(trim((string) $value)); return preg_match('/^[a-z0-9][a-z0-9_-]{1,99}$/', $v) ? $v : ''; }
    private function contextCode($value) { $v = trim((string) $value); return $v !== '' && strlen($v) <= 255 && preg_match('/^[A-Za-z0-9_.-]+$/', $v) ? $v : ''; }
    private function points($value, $signed) { if (!is_numeric($value)) { return null; } $v = round((float) $value, 3); if (!$signed && $v < 0) { return null; } return abs($v) > 1000000 ? null : $v; }
    private function dateOrNull($value) { if ($value === null || $value === '') { return null; } $v = (string) $value; $d = DateTime::createFromFormat('!Y-m-d', $v); return $d && $d->format('Y-m-d') === $v ? $v : null; }
    private function result($ok, $code, $id = null, array $extra = array()) { return array_merge(array('ok' => (bool) $ok, 'code' => (string) $code, 'id' => $id), $extra); }
}
?>
