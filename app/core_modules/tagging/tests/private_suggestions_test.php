<?php
$GLOBALS['kewl_entry_point_run'] = true;
class dbTable {
    public $calls = [];
    public function selectPrepared($sql, $params = []) { $this->calls[] = [$sql, $params]; return []; }
}
require dirname(__DIR__) . '/classes/dbtags_class_inc.php';
function check($value, $message) { if (!$value) throw new RuntimeException($message); }
$db = new dbtags();
foreach ([null, '', 12] as $owner) $db->getSimilarTags('bird', $owner);
$db->getSimilarTags(str_repeat('a', 101), 'owner');
check(!$db->calls, 'Invalid scope and overlong input must not query');
$db->getSimilarTags("O'Reilly%_!", 'owner');
[$sql, $params] = $db->calls[0];
check(str_contains($sql, 'userid = ?') && str_contains($sql, 'LIMIT 20'), 'Owner and bounded results');
check($params === ['owner', 'tag', "O'Reilly!%!_!!%"], 'Bind values and escape LIKE metacharacters');
$db->getOwnTagCounts('owner');
check($db->calls[1][1] === ['owner', 'tag'], 'Cloud is owner scoped');
echo "PASS owner scope, bounds and prepared prefix semantics\n";
