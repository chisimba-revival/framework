<?php
function p137Assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
    echo "PASS: $message\n";
}

$groupSource = file_get_contents(
    __DIR__.'/../../app/core_modules/groupadmin/classes/groupservice_class_inc.php'
);
$start = strpos($groupSource, 'private function addBootstrapMemberRecord');
$end = strpos($groupSource, 'private function removeBootstrapMemberRecord', $start);
$method = substr($groupSource, $start, $end - $start);

$identity = strpos($method, 'permissionUserIdForUser');
$exists = strpos($method, 'membershipExists');
$available = strpos($method, 'getAvailableUsers');
p137Assert($start !== false && $end !== false,
    'bootstrap membership method is present');
p137Assert($identity !== false && $exists !== false && $available !== false,
    'identity, membership, and availability checks remain present');
p137Assert($identity < $exists && $exists < $available,
    'existing membership is classified before availability admission');
p137Assert(strpos($method, "'already_member'") !== false,
    'existing membership retains its explicit idempotent result');

$service = file_get_contents(
    __DIR__.'/../../app/core_modules/security/classes/'
    .'initialadminprovisioningservice_class_inc.php'
);
p137Assert(strpos(
    $service,
    "'site_admin_membership_failed:' . \$membershipCode"
) !== false, 'provisioning preserves the underlying membership failure code');

echo "ALL P137 BOOTSTRAP MEMBERSHIP CONTRACTS PASSED\n";
