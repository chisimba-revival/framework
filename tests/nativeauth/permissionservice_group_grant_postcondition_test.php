<?php
/**
 * Regression contract for canonical group-grant postcondition verification.
 *
 * A legacy database wrapper may return a false-like value even when it wrote
 * the row. The canonical service therefore verifies the required
 * (group_id, right_id) postcondition after attempting the insert.
 *
 * @author Derek Keats
 */

$servicePath = dirname(__DIR__, 2)
    . '/app/core_modules/security/classes/permissionservice_class_inc.php';
$source = file_get_contents($servicePath);

if ($source === false) {
    fwrite(STDERR, "Unable to read canonical permission service.\n");
    exit(1);
}

$methodStart = strpos($source, 'public function ensureGroupGrant($groupId, $rightId)');
$methodEnd = strpos(
    $source,
    'public function ensureAllDefinedRightsForGroup($groupId)',
    $methodStart
);
if ($methodStart === false || $methodEnd === false) {
    fwrite(STDERR, "Unable to isolate ensureGroupGrant().\n");
    exit(1);
}

$method = substr($source, $methodStart, $methodEnd - $methodStart);
$insertPosition = strpos($method, '$this->insertInto(');
$postInsertQueryPosition = strrpos(
    $method,
    "'SELECT id FROM tbl_perms_grouprights'"
);
$postcondition = 'return is_array($rows) && count($rows) === 1;';

if ($insertPosition === false
    || $postInsertQueryPosition === false
    || $postInsertQueryPosition <= $insertPosition
    || strpos($method, $postcondition, $postInsertQueryPosition) === false
    || strpos($method, 'return $this->insertInto(') !== false) {
    fwrite(STDERR, "Canonical group-grant postcondition contract failed.\n");
    exit(1);
}

echo "Canonical group-grant postcondition contract passed.\n";
