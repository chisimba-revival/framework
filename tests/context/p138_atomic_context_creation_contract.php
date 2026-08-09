<?php
function p138Assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
    echo "PASS: $message\n";
}

$context = file_get_contents(
    __DIR__.'/../../app/core_modules/context/classes/dbcontext_class_inc.php'
);
$groups = file_get_contents(
    __DIR__.'/../../app/core_modules/contextgroups/classes/managegroups_class_inc.php'
);
$start = strpos($context, 'public function createContext(');
$end = strpos($context, 'Method to update a context', $start);
$method = substr($context, $start, $end - $start);

p138Assert($start !== false && $end !== false,
    'Context creation method is present');
p138Assert(strpos($method, 'if ($manageTransaction)') !== false,
    'Context creation permits a wider orchestration transaction');
p138Assert(substr_count($method, '$this->beginTransaction();') === 1,
    'standalone Context creation begins one shared database transaction');
p138Assert(substr_count($method, '$this->commitTransaction();') === 1,
    'Context creation has exactly one commit boundary');
p138Assert(substr_count($method, '$this->rollbackTransaction();') === 1,
    'Context creation has exactly one rollback boundary');
p138Assert(strpos($method, 'catch (Throwable $failure)') !== false,
    'all PHP failures trigger rollback and propagate');
p138Assert(strpos($method, 'if (!$contextGroups->createGroups') !== false,
    'Context creation requires explicit canonical provisioning success');

$begin = strpos($method, '$this->beginTransaction();');
$insert = strpos($method, '$this->insert($data);');
$groupsCall = strpos($method, '$contextGroups->createGroups');
$join = strpos($method, '$this->joinContext($contextCode);');
$commit = strpos($method, '$this->commitTransaction();');
$index = strpos($method, '$this->indexCreatedContext($contextCode);');
p138Assert($begin < $insert && $insert < $groupsCall
    && $groupsCall < $join && $join < $commit,
    'row, groups, membership, and grants complete before commit');
p138Assert($index !== false && $index > $commit,
    'standalone non-transactional search indexing occurs only after commit');
p138Assert(strpos($groups, "return true;\n    } // End createGroups") !== false,
    'canonical Context group provisioning reports explicit success');

echo "ALL P138 ATOMIC CONTEXT CREATION CONTRACTS PASSED\n";
