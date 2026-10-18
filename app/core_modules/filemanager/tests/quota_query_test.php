<?php
/** SQL-boundary regression; PDO integration runs against a disposable installed site. */
class dbTable {
    public $query; public $params;
    public function getSubstring($field, $offset) { return "SUBSTRING($field, $offset)"; }
    public function selectPrepared($sql, $params) { $this->query=$sql; $this->params=$params; return [['thecount'=>21]]; }
}
require __DIR__.'/../classes/dbquotas_class_inc.php';
function check($ok) { if (!$ok) throw new RuntimeException('Quota query regression'); }
$q = new dbquotas();
$q->getResults('users', 'surname; DROP TABLE tbl_users', "' OR 1=1 --", 'SLEEP(5)', 0, -4);
check(!str_contains($q->query,'DROP') && !str_contains($q->query,'SLEEP') && !str_contains($q->query,'OR 1=1'));
check($q->params === ['users/%', "' OR 1=1 --%"]);
check(str_contains($q->query,'LIMIT 1 OFFSET 0'));
$q->getResults('context','contextcode','ABC','title',999,1);
check($q->params === ['context/%','ABC%']);
check(str_contains($q->query, 'contextcode LIKE ?') && str_contains($q->query, 'title ASC') && str_contains($q->query,'LIMIT 100 OFFSET 100'));
check($q->getNumPages('users', [], [], [], 0) === 21);
check(!str_contains($q->query,'ORDER BY'));
echo "PASS bound search values, identifier allowlists, stable ordering and bounded pagination\n";
